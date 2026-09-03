import { z } from "zod";

/**
 * Zod schemas — the single validation source for forms, server actions, and API
 * routes. Every untrusted input is parsed through one of these before it ever
 * reaches the database. NOTE: `accountId` is deliberately ABSENT from all input
 * schemas — it is always taken from the server session, never the client.
 */

// ---- Master passphrase ------------------------------------------------------
// Strong requirements: long passphrases beat complex-but-short passwords.
export const passphraseSchema = z
  .string()
  .min(12, "Master passphrase must be at least 12 characters")
  .max(256);

export const setPassphraseSchema = z
  .object({
    passphrase: passphraseSchema,
    confirm: z.string(),
  })
  .refine((d) => d.passphrase === d.confirm, {
    message: "Passphrases do not match",
    path: ["confirm"],
  });

export const unlockSchema = z.object({
  passphrase: z.string().min(1, "Enter your master passphrase"),
});

export const changePassphraseSchema = z
  .object({
    current: z.string().min(1),
    next: passphraseSchema,
    confirm: z.string(),
  })
  .refine((d) => d.next === d.confirm, {
    message: "Passphrases do not match",
    path: ["confirm"],
  });

// ---- Auth -------------------------------------------------------------------
export const signupSchema = z.object({
  organizationName: z.string().min(2).max(120),
  name: z.string().min(1).max(120),
  email: z.string().email().max(254),
  password: z.string().min(12, "Use at least 12 characters").max(128),
});

export const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

// ---- License ----------------------------------------------------------------
export const assigneeSchema = z.object({
  name: z.string().max(200).optional(),
  email: z.string().email().max(254).optional().or(z.literal("")),
  device: z.string().max(200).optional(),
});

export const licenseTypeEnum = z.enum([
  "PERPETUAL",
  "SUBSCRIPTION",
  "VOLUME",
  "OEM",
  "TRIAL",
  "OPEN_SOURCE",
  "OTHER",
]);

export const billingCycleEnum = z.enum([
  "NONE",
  "MONTHLY",
  "QUARTERLY",
  "ANNUAL",
  "BIENNIAL",
  "CUSTOM",
]);

// Accept ISO date strings or empty; transform to Date|null.
const optionalDate = z
  .union([z.string().datetime({ offset: true }), z.string().date(), z.literal("")])
  .optional()
  .transform((v) => (v ? new Date(v) : null));

export const licenseInputSchema = z.object({
  productName: z.string().min(1, "Product name is required").max(200),
  vendor: z.string().max(200).optional().nullable(),
  licenseType: licenseTypeEnum.default("SUBSCRIPTION"),

  // The secret. Optional on update (absent = leave unchanged).
  licenseKey: z.string().max(10_000).optional(),

  purchaseDate: optionalDate,
  startDate: optionalDate,
  expirationDate: optionalDate,
  nextRenewalDate: optionalDate,

  cost: z
    .number()
    .nonnegative()
    .max(100_000_000)
    .optional()
    .nullable(), // major units in the form; converted to cents server-side
  currency: z.string().length(3).default("USD"),

  quantity: z.number().int().min(1).max(1_000_000).default(1),
  billingCycle: billingCycleEnum.default("NONE"),
  autoRenew: z.boolean().default(false),

  renewalUrl: z.string().url().max(2000).optional().or(z.literal("")).nullable(),
  assignedTo: z.array(assigneeSchema).max(1000).default([]),
  tags: z.array(z.string().max(40)).max(50).default([]),

  notes: z.string().max(20_000).optional().nullable(),
  notesSensitive: z.boolean().default(false),

  invoiceReference: z.string().max(200).optional().nullable(),
  vendorSupportContact: z.string().max(500).optional().nullable(),
  customFields: z.record(z.string(), z.unknown()).default({}),
});

export type LicenseInput = z.infer<typeof licenseInputSchema>;

// ---- Members / invitations --------------------------------------------------
export const inviteSchema = z.object({
  email: z.string().email().max(254),
  role: z.enum(["ADMIN", "MEMBER"]).default("MEMBER"),
});

// ---- Listing / filtering ----------------------------------------------------
export const licenseQuerySchema = z.object({
  q: z.string().max(200).optional(),
  status: z.enum(["ACTIVE", "EXPIRING_SOON", "EXPIRED", "ARCHIVED"]).optional(),
  vendor: z.string().max(200).optional(),
  tag: z.string().max(40).optional(),
  sort: z
    .enum(["expirationDate", "productName", "costCents", "createdAt"])
    .default("expirationDate"),
  dir: z.enum(["asc", "desc"]).default("asc"),
  page: z.coerce.number().int().min(1).default(1),
  pageSize: z.coerce.number().int().min(1).max(100).default(25),
});
