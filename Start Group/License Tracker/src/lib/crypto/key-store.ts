import "server-only";

/**
 * =============================================================================
 *  IN-MEMORY DEK STORE
 * =============================================================================
 *
 *  Holds unwrapped Data Encryption Keys (DEKs) ONLY in process memory, keyed by
 *  session token, with an idle TTL. The DEK is never written to disk, the DB, a
 *  cookie, or a log. When the entry expires (or the user "locks"/logs out) the
 *  buffer is zeroed and removed — the user must re-enter the passphrase.
 *
 *  SCALING NOTE (important for self-hosting):
 *  Because keys live in memory, this assumes a SINGLE app instance OR sticky
 *  sessions. For horizontal scaling you have two safe options:
 *    (a) Sticky sessions at the load balancer (simplest), or
 *    (b) Replace this module with an encrypted, per-instance key cache backed by
 *        a KMS/HSM that re-wraps the DEK per node. Do NOT put raw DEKs in Redis.
 *  This indirection is intentionally isolated here so swapping it out is local.
 */

interface Entry {
  dek: Buffer;
  keyVersion: number;
  accountId: string;
  expiresAt: number; // epoch ms
}

// Module-level singleton. `globalThis` guard avoids duplicate maps across HMR
// reloads in dev (which would otherwise drop unlocked keys on every edit).
const g = globalThis as unknown as { __dekStore?: Map<string, Entry> };
const store: Map<string, Entry> = g.__dekStore ?? new Map();
if (process.env.NODE_ENV !== "production") g.__dekStore = store;

// Idle timeout: re-prompt for the passphrase after this much inactivity.
const TTL_MS = 30 * 60 * 1000; // 30 minutes

/** Store (or refresh) a session's DEK. Caller passes the unwrapped key. */
export function putDek(
  sessionToken: string,
  dek: Buffer,
  accountId: string,
  keyVersion: number
): void {
  store.set(sessionToken, {
    dek,
    accountId,
    keyVersion,
    expiresAt: Date.now() + TTL_MS,
  });
}

/**
 * Retrieve a session's DEK if present, not expired, and matching the account +
 * key version (a passphrase change bumps keyVersion, instantly invalidating
 * stale unlocked keys). Sliding expiration: each successful read extends TTL.
 */
export function getDek(
  sessionToken: string,
  accountId: string,
  keyVersion: number
): Buffer | null {
  const entry = store.get(sessionToken);
  if (!entry) return null;

  if (
    Date.now() > entry.expiresAt ||
    entry.accountId !== accountId ||
    entry.keyVersion !== keyVersion
  ) {
    dropDek(sessionToken);
    return null;
  }

  entry.expiresAt = Date.now() + TTL_MS; // sliding window
  return entry.dek;
}

export function hasDek(
  sessionToken: string,
  accountId: string,
  keyVersion: number
): boolean {
  return getDek(sessionToken, accountId, keyVersion) !== null;
}

/** Explicitly lock a session: zero the key material and remove the entry. */
export function dropDek(sessionToken: string): void {
  const entry = store.get(sessionToken);
  if (entry) {
    entry.dek.fill(0); // best-effort wipe
    store.delete(sessionToken);
  }
}

/** Drop every cached key for an account (e.g. after passphrase rotation). */
export function dropAccount(accountId: string): void {
  for (const [token, entry] of store.entries()) {
    if (entry.accountId === accountId) dropDek(token);
  }
}

// Periodic sweeper to evict expired entries even if never read again.
const g2 = globalThis as unknown as { __dekSweeper?: NodeJS.Timeout };
if (!g2.__dekSweeper) {
  g2.__dekSweeper = setInterval(() => {
    const now = Date.now();
    for (const [token, entry] of store.entries()) {
      if (now > entry.expiresAt) dropDek(token);
    }
  }, 60 * 1000);
  // Don't keep the event loop alive solely for the sweeper.
  g2.__dekSweeper.unref?.();
}
