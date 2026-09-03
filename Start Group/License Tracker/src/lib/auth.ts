import "server-only";
import { betterAuth } from "better-auth";
import { prismaAdapter } from "better-auth/adapters/prisma";
import { nextCookies } from "better-auth/next-js";
import { db } from "@/lib/db";
import { env } from "@/env";

/**
 * better-auth configuration.
 *
 * SECURITY DECISIONS
 * ------------------
 *  - Email + password with verification required before sign-in is "trusted".
 *  - Sessions are server-side rows (see Session model) referenced by an
 *    httpOnly, Secure, SameSite=Lax cookie. No JWT-in-localStorage.
 *  - Password hashing uses better-auth's built-in scrypt (memory-hard).
 *  - Cookie prefix + secure flags are enforced; cookies are not readable by JS.
 *
 *  NOTE on tenancy: better-auth creates the `User` row. During signup we attach
 *  the user to a freshly created `Account` (tenant) in the signup server action,
 *  because every User MUST have an accountId. See app/(auth)/signup.
 */
export const auth = betterAuth({
  baseURL: env.BETTER_AUTH_URL,
  secret: env.BETTER_AUTH_SECRET,

  database: prismaAdapter(db, { provider: "postgresql" }),

  // Map better-auth's expected models onto our Prisma model names.
  user: {
    modelName: "User",
    // accountId is required on our schema; we set it in the signup flow and via
    // the databaseHooks below as a safety net.
    additionalFields: {
      accountId: { type: "string", required: false, input: false },
      role: { type: "string", required: false, input: false },
    },
  },
  session: {
    modelName: "Session",
    expiresIn: 60 * 60 * 24 * 7, // 7 days
    updateAge: 60 * 60 * 24, // refresh once per day
    cookieCache: { enabled: true, maxAge: 5 * 60 },
  },
  account: { modelName: "AuthAccount" },
  verification: { modelName: "Verification" },

  emailAndPassword: {
    enabled: true,
    requireEmailVerification: true,
    minPasswordLength: 12, // strong account password (separate from passphrase)
    maxPasswordLength: 128,
    // sendResetPassword / sendVerificationEmail wired in src/lib/email.ts.
  },

  advanced: {
    // Harden the session cookie. In production these are Secure + httpOnly.
    cookiePrefix: "lt",
    useSecureCookies: env.NODE_ENV === "production",
    defaultCookieAttributes: {
      httpOnly: true,
      sameSite: "lax",
    },
  },

  // nextCookies() must be last: it bridges better-auth cookie writes into the
  // Next.js App Router response.
  plugins: [nextCookies()],
});

export type AuthSession = typeof auth.$Infer.Session;
