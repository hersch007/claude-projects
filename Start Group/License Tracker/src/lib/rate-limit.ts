import "server-only";

/**
 * Token-bucket rate limiting.
 *
 * Two scopes are supported by convention via the `key` you pass:
 *   - per-tenant:  `tenant:<accountId>:<bucket>`
 *   - per-ip:      `ip:<address>:<bucket>`
 *   - per-user:    `user:<userId>:<bucket>`
 *
 * The default backend is in-memory (single instance). For multi-instance
 * deployments, swap `limit()` to a Redis/Upstash implementation using the same
 * signature — call sites do not change. The interface is intentionally tiny.
 *
 * SECURITY: rate limiting blunts credential-stuffing, brute force of the master
 * passphrase, and "reveal"-endpoint scraping. The passphrase-unlock and reveal
 * endpoints use the strictest buckets.
 */

interface Bucket {
  tokens: number;
  updatedAt: number;
}

const g = globalThis as unknown as { __rlBuckets?: Map<string, Bucket> };
const buckets = g.__rlBuckets ?? new Map<string, Bucket>();
g.__rlBuckets = buckets;

export interface RateLimitResult {
  success: boolean;
  remaining: number;
  resetMs: number;
}

/**
 * @param key      unique bucket key (include scope + action)
 * @param limit    max requests per window
 * @param windowMs window length in ms
 */
export function rateLimit(
  key: string,
  limit: number,
  windowMs: number
): RateLimitResult {
  const now = Date.now();
  const refillRate = limit / windowMs; // tokens per ms
  const existing = buckets.get(key);

  let tokens: number;
  if (!existing) {
    tokens = limit;
  } else {
    const elapsed = now - existing.updatedAt;
    tokens = Math.min(limit, existing.tokens + elapsed * refillRate);
  }

  if (tokens < 1) {
    const resetMs = Math.ceil((1 - tokens) / refillRate);
    buckets.set(key, { tokens, updatedAt: now });
    return { success: false, remaining: 0, resetMs };
  }

  tokens -= 1;
  buckets.set(key, { tokens, updatedAt: now });
  return { success: true, remaining: Math.floor(tokens), resetMs: 0 };
}

// Preset policies used across the app.
export const RateLimits = {
  // Strict: passphrase unlock — slows offline-style online guessing.
  unlock: (id: string) => rateLimit(`unlock:${id}`, 5, 15 * 60 * 1000),
  // Strict: reveal a license key.
  reveal: (id: string) => rateLimit(`reveal:${id}`, 30, 60 * 1000),
  // Auth: login / signup attempts per IP.
  auth: (ip: string) => rateLimit(`auth:${ip}`, 10, 15 * 60 * 1000),
  // General API per tenant.
  api: (accountId: string) => rateLimit(`api:${accountId}`, 300, 60 * 1000),
  // Global per IP backstop.
  global: (ip: string) => rateLimit(`global:${ip}`, 600, 60 * 1000),
} as const;

/** Best-effort client IP from proxy headers (configure your reverse proxy!). */
export function clientIp(headers: Headers): string {
  const xff = headers.get("x-forwarded-for");
  if (xff) return xff.split(",")[0]!.trim();
  return headers.get("x-real-ip") ?? "unknown";
}
