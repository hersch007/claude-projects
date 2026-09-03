import "server-only";
import { headers } from "next/headers";
import { cache } from "react";
import { auth } from "@/lib/auth";
import { db } from "@/lib/db";
import { getDek } from "@/lib/crypto/key-store";
import type { Role } from "@prisma/client";

/**
 * =============================================================================
 *  TENANT CONTEXT & ISOLATION
 * =============================================================================
 *
 *  This module is the chokepoint through which every authenticated request must
 *  pass. It resolves the current user, their tenant (accountId), role, and
 *  (when unlocked) the in-memory DEK. Application code NEVER reads accountId
 *  from the client — it is always derived from the server session, which is the
 *  only trustworthy source. This is what prevents IDOR / cross-tenant access:
 *  even if a user forges an accountId in a request body, we ignore it.
 */

export class AuthError extends Error {
  constructor(
    message: string,
    public readonly code:
      | "UNAUTHENTICATED"
      | "FORBIDDEN"
      | "LOCKED"
      | "NOT_FOUND" = "UNAUTHENTICATED"
  ) {
    super(message);
    this.name = "AuthError";
  }
}

export interface TenantContext {
  userId: string;
  accountId: string;
  role: Role;
  email: string;
  sessionToken: string;
  keyVersion: number;
}

/**
 * Resolve the session + tenant for the current request. Cached per-request via
 * React `cache()` so multiple callers in one render share a single lookup.
 * Throws AuthError("UNAUTHENTICATED") if there is no valid session.
 */
export const getTenantContext = cache(async (): Promise<TenantContext> => {
  const hdrs = await headers();
  const session = await auth.api.getSession({ headers: hdrs });

  if (!session?.user || !session.session) {
    throw new AuthError("Not authenticated", "UNAUTHENTICATED");
  }

  // The user row carries the tenant boundary. We re-read role/accountId/version
  // from the DB rather than trusting cookie-cached values for authorization.
  const user = await db.user.findUnique({
    where: { id: session.user.id },
    select: {
      id: true,
      email: true,
      role: true,
      accountId: true,
      account: { select: { keyVersion: true } },
    },
  });

  if (!user) throw new AuthError("User not found", "UNAUTHENTICATED");

  return {
    userId: user.id,
    accountId: user.accountId,
    role: user.role,
    email: user.email,
    sessionToken: session.session.token,
    keyVersion: user.account.keyVersion,
  };
});

/** Like getTenantContext but returns null instead of throwing. */
export async function tryGetTenantContext(): Promise<TenantContext | null> {
  try {
    return await getTenantContext();
  } catch {
    return null;
  }
}

/** Require a specific role (ADMIN-only operations). */
export async function requireRole(role: Role): Promise<TenantContext> {
  const ctx = await getTenantContext();
  if (role === "ADMIN" && ctx.role !== "ADMIN") {
    throw new AuthError("Admin privileges required", "FORBIDDEN");
  }
  return ctx;
}

/**
 * Return the current session's unwrapped DEK, or throw "LOCKED" if the user has
 * not entered their master passphrase this session. Any code path that touches
 * encrypted data MUST obtain the key through here.
 */
export function requireDek(ctx: TenantContext): Buffer {
  const dek = getDek(ctx.sessionToken, ctx.accountId, ctx.keyVersion);
  if (!dek) {
    throw new AuthError("Vault is locked — enter master passphrase", "LOCKED");
  }
  return dek;
}

export function isUnlocked(ctx: TenantContext): boolean {
  return getDek(ctx.sessionToken, ctx.accountId, ctx.keyVersion) !== null;
}

/**
 * =============================================================================
 *  Tenant-scoped data accessors
 * =============================================================================
 *  Thin wrappers that ALWAYS inject `accountId` (and exclude soft-deleted rows
 *  by default). Prefer these over touching `db` directly so that forgetting a
 *  tenant filter is structurally hard. The `accountId` is taken from the
 *  trusted context, never from caller input.
 */
export function tenantDb(ctx: TenantContext) {
  const { accountId } = ctx;
  return {
    accountId,

    license: {
      findManyActive: (args?: { where?: object; orderBy?: object; take?: number; skip?: number }) =>
        db.license.findMany({
          ...args,
          where: { accountId, deletedAt: null, ...(args?.where ?? {}) },
        }),

      findByIdActive: (id: string) =>
        db.license.findFirst({ where: { id, accountId, deletedAt: null } }),

      count: (where?: object) =>
        db.license.count({ where: { accountId, deletedAt: null, ...(where ?? {}) } }),
    },

    attachment: {
      findByIdActive: (id: string) =>
        db.attachment.findFirst({ where: { id, accountId, deletedAt: null } }),
    },

    account: {
      get: () => db.account.findUniqueOrThrow({ where: { id: accountId } }),
    },
  };
}
