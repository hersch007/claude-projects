import "server-only";
import { PrismaClient } from "@prisma/client";

/**
 * Singleton Prisma client. The `globalThis` guard prevents exhausting the DB
 * connection pool during dev hot-reloads (each reload would otherwise spawn a
 * new client). In production a single instance is created per process.
 *
 * SECURITY: This raw client is intentionally NOT exported widely. Application
 * code should go through the tenant-scoped helpers in `src/lib/tenant.ts`, which
 * guarantee every query is filtered by accountId. Direct use of `db` is allowed
 * only in infrastructure code (auth adapter, webhooks) that legitimately spans
 * tenants — and such use is reviewed carefully.
 */
const g = globalThis as unknown as { __prisma?: PrismaClient };

export const db =
  g.__prisma ??
  new PrismaClient({
    log:
      process.env.NODE_ENV === "development"
        ? ["warn", "error"]
        : ["error"],
  });

if (process.env.NODE_ENV !== "production") g.__prisma = db;
