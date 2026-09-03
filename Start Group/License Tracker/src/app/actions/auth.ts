"use server";

import { headers } from "next/headers";
import { auth } from "@/lib/auth";
import { db } from "@/lib/db";
import { signupSchema } from "@/lib/validations";
import { audit } from "@/lib/audit";
import { RateLimits, clientIp } from "@/lib/rate-limit";

type ActionResult = { ok: true } | { ok: false; error: string };

/**
 * Signup = create tenant (Account) + first ADMIN user atomically.
 *
 * Flow: create the Account first, then ask better-auth to create the credential
 * user, then stamp accountId + ADMIN role onto that user. The master passphrase
 * is set in a SEPARATE step after email verification (see /onboarding), so the
 * passphrase is never transmitted alongside the signup password.
 */
export async function signUp(
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  const h = await headers();
  const ip = clientIp(h);
  if (!RateLimits.auth(ip).success) {
    return { ok: false, error: "Too many attempts. Please wait and try again." };
  }

  const parsed = signupSchema.safeParse({
    organizationName: formData.get("organizationName"),
    name: formData.get("name"),
    email: formData.get("email"),
    password: formData.get("password"),
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Invalid input" };
  }
  const { organizationName, name, email, password } = parsed.data;

  const existing = await db.user.findUnique({ where: { email } });
  if (existing) {
    // Avoid user enumeration: same generic message either way.
    return { ok: false, error: "Could not create account with those details." };
  }

  // 1) Create the tenant.
  const account = await db.account.create({
    data: { name: organizationName, plan: "FREE", seatLimit: 1 },
  });

  try {
    // 2) Create the credential user via better-auth (handles hashing + verify email).
    const res = await auth.api.signUpEmail({
      body: { email, password, name },
      headers: h,
    });
    const userId = res.user?.id;
    if (!userId) throw new Error("signup failed");

    // 3) Attach to tenant as ADMIN (first user owns the account).
    await db.user.update({
      where: { id: userId },
      data: { accountId: account.id, role: "ADMIN" },
    });

    await audit({
      accountId: account.id,
      userId,
      action: "account.create",
      metadata: { organizationName },
      ipAddress: ip,
      userAgent: h.get("user-agent"),
    });

    return { ok: true };
  } catch (err) {
    // Roll back the orphaned tenant if user creation failed.
    await db.account.delete({ where: { id: account.id } }).catch(() => {});
    console.error("[signup] failed", err);
    return { ok: false, error: "Could not create account with those details." };
  }
}
