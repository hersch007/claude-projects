import "server-only";
import { Prisma, type License } from "@prisma/client";
import { db } from "@/lib/db";
import { sealField, openFieldString } from "@/lib/crypto/encryption";
import { type TenantContext, requireDek, tenantDb } from "@/lib/tenant";
import { audit } from "@/lib/audit";
import type { LicenseInput } from "@/lib/validations";
import { PLANS } from "@/lib/plans";

/**
 * Domain service for licenses. Centralizes:
 *  - encryption of the license key / sensitive notes (DEK from session),
 *  - tenant-scoped persistence,
 *  - live status computation,
 *  - the *masked* DTO that is safe to send to the client by default.
 *
 * The plaintext license key NEVER appears in list/detail DTOs; it is only ever
 * returned by the dedicated, rate-limited "reveal" path.
 */

const EXPIRING_WINDOW_DAYS = 30;

export type LicenseStatusComputed =
  | "ACTIVE"
  | "EXPIRING_SOON"
  | "EXPIRED"
  | "ARCHIVED";

export function computeStatus(
  expirationDate: Date | null,
  archived: boolean
): LicenseStatusComputed {
  if (archived) return "ARCHIVED";
  if (!expirationDate) return "ACTIVE";
  const days = Math.ceil((expirationDate.getTime() - Date.now()) / 86_400_000);
  if (days < 0) return "EXPIRED";
  if (days <= EXPIRING_WINDOW_DAYS) return "EXPIRING_SOON";
  return "ACTIVE";
}

/** Build a non-secret hint like "••••AB12" from a raw key (last 4 chars). */
function makeHint(key: string): string {
  const tail = key.replace(/\s+/g, "").slice(-4);
  return tail ? `••••${tail}` : "••••••••";
}

/** Safe DTO returned to clients. Contains NO decrypted secret material. */
export interface LicenseDTO {
  id: string;
  productName: string;
  vendor: string | null;
  licenseType: License["licenseType"];
  hasKey: boolean;
  keyHint: string | null;
  purchaseDate: string | null;
  startDate: string | null;
  expirationDate: string | null;
  nextRenewalDate: string | null;
  costCents: number | null;
  currency: string;
  quantity: number;
  billingCycle: License["billingCycle"];
  autoRenew: boolean;
  renewalUrl: string | null;
  status: LicenseStatusComputed;
  assignedTo: unknown;
  tags: string[];
  notesSensitive: boolean;
  notes: string | null; // only non-sensitive notes; sensitive ones are withheld
  invoiceReference: string | null;
  vendorSupportContact: string | null;
  customFields: unknown;
  createdAt: string;
  updatedAt: string;
}

export function toDTO(l: License): LicenseDTO {
  return {
    id: l.id,
    productName: l.productName,
    vendor: l.vendor,
    licenseType: l.licenseType,
    hasKey: l.licenseKeyCipher != null,
    keyHint: l.licenseKeyHint,
    purchaseDate: l.purchaseDate?.toISOString() ?? null,
    startDate: l.startDate?.toISOString() ?? null,
    expirationDate: l.expirationDate?.toISOString() ?? null,
    nextRenewalDate: l.nextRenewalDate?.toISOString() ?? null,
    costCents: l.costCents,
    currency: l.currency,
    quantity: l.quantity,
    billingCycle: l.billingCycle,
    autoRenew: l.autoRenew,
    renewalUrl: l.renewalUrl,
    status: computeStatus(l.expirationDate, l.status === "ARCHIVED"),
    assignedTo: l.assignedTo,
    tags: l.tags,
    notesSensitive: l.notesSensitive,
    // Sensitive notes are encrypted and intentionally NOT decrypted here.
    notes: l.notesSensitive ? null : l.notesPlain,
    invoiceReference: l.invoiceReference,
    vendorSupportContact: l.vendorSupportContact,
    customFields: l.customFields,
    createdAt: l.createdAt.toISOString(),
    updatedAt: l.updatedAt.toISOString(),
  };
}

function inputToColumns(input: LicenseInput, dek: Buffer) {
  const status = computeStatus(input.expirationDate ?? null, false);

  // Encrypt the key only if one was provided.
  const keyFields =
    input.licenseKey && input.licenseKey.length > 0
      ? {
          licenseKeyCipher: sealField(input.licenseKey, dek),
          licenseKeyHint: makeHint(input.licenseKey),
        }
      : {};

  // Sensitive notes are encrypted; non-sensitive stored as plaintext.
  const noteFields =
    input.notes && input.notes.length > 0
      ? input.notesSensitive
        ? { notesCipher: sealField(input.notes, dek), notesPlain: null, notesSensitive: true }
        : { notesPlain: input.notes, notesCipher: null, notesSensitive: false }
      : { notesPlain: null, notesCipher: null, notesSensitive: false };

  return {
    productName: input.productName,
    vendor: input.vendor ?? null,
    licenseType: input.licenseType,
    purchaseDate: input.purchaseDate,
    startDate: input.startDate,
    expirationDate: input.expirationDate,
    nextRenewalDate: input.nextRenewalDate,
    costCents: input.cost != null ? Math.round(input.cost * 100) : null,
    currency: input.currency,
    quantity: input.quantity,
    billingCycle: input.billingCycle,
    autoRenew: input.autoRenew,
    renewalUrl: input.renewalUrl || null,
    status,
    // JSON columns: cast through Prisma's input type (Zod gives us plain objects/arrays).
    assignedTo: input.assignedTo as Prisma.InputJsonValue,
    tags: input.tags,
    invoiceReference: input.invoiceReference ?? null,
    vendorSupportContact: input.vendorSupportContact ?? null,
    customFields: input.customFields as Prisma.InputJsonValue,
    ...keyFields,
    ...noteFields,
  };
}

/** Create a license, enforcing the plan's license cap. */
export async function createLicense(
  ctx: TenantContext,
  input: LicenseInput,
  meta: { ip?: string | null; ua?: string | null }
): Promise<LicenseDTO> {
  const dek = requireDek(ctx); // throws LOCKED if vault not unlocked
  const account = await tenantDb(ctx).account.get();
  const cap = PLANS[account.plan].licenseLimit;
  if (cap != null) {
    const count = await tenantDb(ctx).license.count();
    if (count >= cap) {
      throw new Error(`Plan limit reached (${cap} licenses). Upgrade to add more.`);
    }
  }

  const created = await db.license.create({
    data: {
      accountId: ctx.accountId, // tenant boundary from trusted context
      createdById: ctx.userId,
      ...inputToColumns(input, dek),
    },
  });

  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "license.create",
    targetType: "License",
    targetId: created.id,
    metadata: { productName: created.productName, vendor: created.vendor },
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });

  return toDTO(created);
}

export async function updateLicense(
  ctx: TenantContext,
  id: string,
  input: LicenseInput,
  meta: { ip?: string | null; ua?: string | null }
): Promise<LicenseDTO> {
  const dek = requireDek(ctx);
  // Ownership check: findFirst scoped to accountId — cross-tenant id => null.
  const existing = await tenantDb(ctx).license.findByIdActive(id);
  if (!existing) throw new Error("License not found");

  const updated = await db.license.update({
    where: { id: existing.id },
    data: inputToColumns(input, dek),
  });

  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "license.update",
    targetType: "License",
    targetId: id,
    metadata: { productName: updated.productName },
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });

  return toDTO(updated);
}

/** Soft delete. */
export async function deleteLicense(
  ctx: TenantContext,
  id: string,
  meta: { ip?: string | null; ua?: string | null }
): Promise<void> {
  const existing = await tenantDb(ctx).license.findByIdActive(id);
  if (!existing) throw new Error("License not found");

  await db.license.update({
    where: { id: existing.id },
    data: { deletedAt: new Date() },
  });

  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "license.delete",
    targetType: "License",
    targetId: id,
    metadata: { productName: existing.productName },
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });
}

/**
 * Reveal the decrypted license key. This is the ONLY path that returns the
 * plaintext key. It is rate-limited at the route layer and always audited.
 */
export async function revealLicenseKey(
  ctx: TenantContext,
  id: string,
  meta: { ip?: string | null; ua?: string | null }
): Promise<string> {
  const dek = requireDek(ctx);
  const license = await tenantDb(ctx).license.findByIdActive(id);
  if (!license || !license.licenseKeyCipher) {
    throw new Error("License key not found");
  }

  const plaintext = openFieldString(Buffer.from(license.licenseKeyCipher), dek);

  // Reveals are security-relevant — always logged (without the key itself).
  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "license.reveal_key",
    targetType: "License",
    targetId: id,
    metadata: { productName: license.productName },
    ipAddress: meta.ip,
    userAgent: meta.ua,
  });

  return plaintext;
}
