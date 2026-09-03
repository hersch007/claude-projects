import { z } from "zod";

/**
 * Centralized, fail-fast environment validation.
 *
 * SECURITY: We validate every secret at boot. A misconfigured deployment
 * (e.g. a weak/blank ENCRYPTION_PEPPER or BETTER_AUTH_SECRET) should crash the
 * process immediately rather than silently run with broken crypto.
 */
const schema = z.object({
  NODE_ENV: z.enum(["development", "test", "production"]).default("development"),
  NEXT_PUBLIC_APP_URL: z.string().url(),

  DATABASE_URL: z.string().url(),

  BETTER_AUTH_SECRET: z
    .string()
    .min(32, "BETTER_AUTH_SECRET must be at least 32 characters"),
  BETTER_AUTH_URL: z.string().url(),

  // The server-side pepper mixed into Argon2id. Must be high-entropy.
  ENCRYPTION_PEPPER: z
    .string()
    .min(32, "ENCRYPTION_PEPPER must be at least 32 characters"),

  STRIPE_SECRET_KEY: z.string().min(1).optional(),
  STRIPE_WEBHOOK_SECRET: z.string().min(1).optional(),
  NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY: z.string().optional(),
  STRIPE_PRICE_PRO: z.string().optional(),
  STRIPE_PRICE_TEAM_BASE: z.string().optional(),
  STRIPE_PRICE_TEAM_PER_SEAT: z.string().optional(),

  RESEND_API_KEY: z.string().optional(),
  EMAIL_FROM: z.string().optional(),
  REDIS_URL: z.string().optional(),
});

// During `next build` some server-only secrets may be absent; we relax to a
// partial parse there. At runtime (and in production) we parse strictly.
const isBuildPhase = process.env.NEXT_PHASE === "phase-production-build";

function loadEnv() {
  const parsed = schema.safeParse(process.env);
  if (!parsed.success) {
    if (isBuildPhase) {
      // Don't block the build for missing runtime secrets.
      return process.env as unknown as z.infer<typeof schema>;
    }
    console.error(
      "❌ Invalid environment variables:",
      JSON.stringify(parsed.error.flatten().fieldErrors, null, 2)
    );
    throw new Error("Invalid environment configuration. See errors above.");
  }
  return parsed.data;
}

export const env = loadEnv();

export const stripeEnabled = Boolean(
  env.STRIPE_SECRET_KEY && env.STRIPE_WEBHOOK_SECRET
);
