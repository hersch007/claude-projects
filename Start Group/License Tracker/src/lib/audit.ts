import "server-only";
import { Prisma } from "@prisma/client";
import { db } from "@/lib/db";

/**
 * Append-only audit logging. Records WHO did WHAT, WHEN, from WHERE.
 *
 * SECURITY: `metadata` is sanitized — we maintain an allowlist mindset and the
 * callers never pass secret material (license keys, passphrases, DEKs, file
 * bytes). As a backstop, `scrub()` drops any key that looks sensitive.
 */
const SENSITIVE_KEY = /(pass|secret|token|key|dek|cipher|authorization|cookie)/i;

function scrub(meta: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(meta)) {
    if (SENSITIVE_KEY.test(k)) {
      out[k] = "[redacted]";
    } else if (typeof v === "string" && v.length > 500) {
      out[k] = v.slice(0, 500) + "…";
    } else {
      out[k] = v;
    }
  }
  return out;
}

export interface AuditInput {
  accountId: string;
  userId?: string | null;
  action: string;
  targetType?: string;
  targetId?: string;
  metadata?: Record<string, unknown>;
  ipAddress?: string | null;
  userAgent?: string | null;
}

export async function audit(input: AuditInput): Promise<void> {
  try {
    await db.auditLog.create({
      data: {
        accountId: input.accountId,
        userId: input.userId ?? null,
        action: input.action,
        targetType: input.targetType,
        targetId: input.targetId,
        metadata: scrub(input.metadata ?? {}) as Prisma.InputJsonValue,
        ipAddress: input.ipAddress ?? null,
        userAgent: input.userAgent ?? null,
      },
    });
  } catch (err) {
    // Auditing must never break the main flow, but failures should be visible.
    console.error("[audit] failed to write log entry", {
      action: input.action,
      error: err instanceof Error ? err.message : String(err),
    });
  }
}
