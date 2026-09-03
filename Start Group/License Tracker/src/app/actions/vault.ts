"use server";

import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { getTenantContext, requireRole } from "@/lib/tenant";
import {
  initializeAccountKeys,
  unlockDek,
  rewrapDek,
} from "@/lib/crypto/encryption";
import { putDek, dropDek, dropAccount } from "@/lib/crypto/key-store";
import { audit } from "@/lib/audit";
import { RateLimits, clientIp } from "@/lib/rate-limit";
import {
  setPassphraseSchema,
  unlockSchema,
  changePassphraseSchema,
} from "@/lib/validations";

type ActionResult = { ok: true } | { ok: false; error: string };

async function reqMeta() {
  const h = await headers();
  return { ip: clientIp(h), ua: h.get("user-agent") };
}

/**
 * Admin sets the account master passphrase during onboarding. Generates the
 * account's random DEK, wraps it, and immediately unlocks the current session.
 * SECURITY: only an ADMIN can set it, and only once (idempotency guarded).
 */
export async function setMasterPassphrase(
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  const ctx = await requireRole("ADMIN");
  const parsed = setPassphraseSchema.safeParse({
    passphrase: formData.get("passphrase"),
    confirm: formData.get("confirm"),
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Invalid input" };
  }

  const account = await db.account.findUniqueOrThrow({
    where: { id: ctx.accountId },
    select: { passphraseSet: true },
  });
  if (account.passphraseSet) {
    return { ok: false, error: "Master passphrase is already set." };
  }

  const material = await initializeAccountKeys(parsed.data.passphrase);
  const updated = await db.account.update({
    where: { id: ctx.accountId },
    data: {
      masterPassphraseHash: material.masterPassphraseHash,
      kdfSalt: material.kdfSalt,
      dekWrapped: material.dekWrapped,
      dekWrapIv: material.dekWrapIv,
      dekWrapTag: material.dekWrapTag,
      passphraseSet: true,
    },
    select: { keyVersion: true },
  });

  // Unlock the current session immediately so onboarding flows continue.
  const dek = await unlockDek(parsed.data.passphrase, {
    masterPassphraseHash: material.masterPassphraseHash,
    kdfSalt: material.kdfSalt,
    dekWrapped: material.dekWrapped,
    dekWrapIv: material.dekWrapIv,
    dekWrapTag: material.dekWrapTag,
  });
  if (dek) putDek(ctx.sessionToken, dek, ctx.accountId, updated.keyVersion);

  const meta = await reqMeta();
  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "passphrase.set",
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });

  revalidatePath("/dashboard");
  return { ok: true };
}

/**
 * Unlock the vault for this session by entering the master passphrase. The
 * derived DEK is held only in memory (key-store). Rate-limited per account+ip.
 */
export async function unlockVault(
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  const ctx = await getTenantContext();
  const meta = await reqMeta();

  // Throttle guesses (per-account and per-ip).
  const rl = RateLimits.unlock(`${ctx.accountId}:${meta.ip}`);
  if (!rl.success) {
    return {
      ok: false,
      error: `Too many attempts. Try again in ${Math.ceil(rl.resetMs / 1000)}s.`,
    };
  }

  const parsed = unlockSchema.safeParse({ passphrase: formData.get("passphrase") });
  if (!parsed.success) return { ok: false, error: "Enter your master passphrase" };

  const account = await db.account.findUniqueOrThrow({
    where: { id: ctx.accountId },
  });
  if (
    !account.passphraseSet ||
    !account.masterPassphraseHash ||
    !account.kdfSalt ||
    !account.dekWrapped ||
    !account.dekWrapIv ||
    !account.dekWrapTag
  ) {
    return { ok: false, error: "No master passphrase has been set yet." };
  }

  const dek = await unlockDek(parsed.data.passphrase, {
    masterPassphraseHash: account.masterPassphraseHash,
    kdfSalt: Buffer.from(account.kdfSalt),
    dekWrapped: Buffer.from(account.dekWrapped),
    dekWrapIv: Buffer.from(account.dekWrapIv),
    dekWrapTag: Buffer.from(account.dekWrapTag),
  });

  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: dek ? "vault.unlock" : "vault.unlock_failed",
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });

  if (!dek) {
    // Generic message — do not reveal whether the passphrase was "close".
    return { ok: false, error: "Incorrect master passphrase." };
  }

  putDek(ctx.sessionToken, dek, ctx.accountId, account.keyVersion);
  revalidatePath("/dashboard");
  return { ok: true };
}

/** Explicitly lock the vault (wipe the in-memory key for this session). */
export async function lockVault(): Promise<ActionResult> {
  const ctx = await getTenantContext();
  dropDek(ctx.sessionToken);
  const meta = await reqMeta();
  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "vault.lock",
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });
  revalidatePath("/dashboard");
  return { ok: true };
}

/**
 * Change the master passphrase. Uses envelope re-wrapping: the same DEK is
 * re-encrypted under the new passphrase — NO data is re-encrypted. We bump
 * keyVersion to evict every stale in-memory key across all sessions.
 */
export async function changeMasterPassphrase(
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  const ctx = await requireRole("ADMIN");
  const parsed = changePassphraseSchema.safeParse({
    current: formData.get("current"),
    next: formData.get("next"),
    confirm: formData.get("confirm"),
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Invalid input" };
  }

  const account = await db.account.findUniqueOrThrow({ where: { id: ctx.accountId } });
  if (!account.masterPassphraseHash || !account.kdfSalt || !account.dekWrapped) {
    return { ok: false, error: "No passphrase set." };
  }

  const material = await rewrapDek(parsed.data.current, parsed.data.next, {
    masterPassphraseHash: account.masterPassphraseHash,
    kdfSalt: Buffer.from(account.kdfSalt),
    dekWrapped: Buffer.from(account.dekWrapped),
    dekWrapIv: Buffer.from(account.dekWrapIv!),
    dekWrapTag: Buffer.from(account.dekWrapTag!),
  });
  if (!material) return { ok: false, error: "Current passphrase is incorrect." };

  await db.account.update({
    where: { id: ctx.accountId },
    data: {
      masterPassphraseHash: material.masterPassphraseHash,
      kdfSalt: material.kdfSalt,
      dekWrapped: material.dekWrapped,
      dekWrapIv: material.dekWrapIv,
      dekWrapTag: material.dekWrapTag,
      keyVersion: { increment: 1 }, // invalidates all unlocked sessions
    },
  });

  // Force everyone (including this session) to re-unlock with the new phrase.
  dropAccount(ctx.accountId);

  const meta = await reqMeta();
  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "passphrase.change",
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });

  return { ok: true };
}
