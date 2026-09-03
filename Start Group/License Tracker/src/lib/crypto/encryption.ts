import "server-only";
import {
  randomBytes,
  createCipheriv,
  createDecipheriv,
  hkdfSync,
  timingSafeEqual,
  createHash,
} from "node:crypto";
import { hash as argon2Hash, verify as argon2Verify } from "@node-rs/argon2";
import { env } from "@/env";

/**
 * =============================================================================
 *  ENVELOPE ENCRYPTION
 * =============================================================================
 *
 *  Design (why it is built this way):
 *
 *   passphrase ──Argon2id(salt, pepper)──▶ master key (MK)
 *                                            │
 *                            ┌───────────────┴───────────────┐
 *                       HKDF "verifier"                  HKDF "kek"
 *                            │                                │
 *                     stored verifier              KEK (key-encryption key)
 *                     (confirms passphrase)               │
 *                                                  unwraps ▼
 *                                              DEK (data-encryption key)  ← random, per account
 *                                                         │
 *                                          AES-256-GCM ▼  encrypts every secret field
 *
 *  KEY SECURITY DECISIONS
 *  ----------------------
 *  1. Argon2id (memory-hard) defeats GPU/ASIC brute force of the passphrase.
 *  2. A server-side PEPPER is mixed in via `associatedData`, so a stolen DB
 *     alone is not enough to attack passphrases offline — the attacker also
 *     needs the app environment secret.
 *  3. The DATA key (DEK) is random and independent of the passphrase. Changing
 *     the passphrase only re-wraps the DEK (cheap), never re-encrypts the data.
 *  4. The DEK is NEVER persisted in plaintext and NEVER leaves the server. It
 *     lives only in a short-lived in-memory store (see key-store.ts).
 *  5. AES-256-GCM gives confidentiality AND integrity (auth tag). Tampering
 *     with ciphertext is detected on decrypt.
 *  6. A fresh random 96-bit IV per encryption — never reused with the same key.
 */

// ---- Tunables ---------------------------------------------------------------
const KEY_LEN = 32; // 256-bit keys
const IV_LEN = 12; // 96-bit nonce, recommended for GCM
const GCM_TAG_LEN = 16; // 128-bit auth tag
const SALT_LEN = 16;

// Argon2id parameters. OWASP-recommended baseline (m=19MiB, t=2, p=1) raised
// for a security-first product. Tune for your hardware; higher = safer/slower.
const ARGON2_OPTS = {
  memoryCost: 1 << 16, // 64 MiB
  timeCost: 3,
  parallelism: 1,
  // `secret` is the server pepper — folded into the hash by Argon2 itself.
  secret: Buffer.from(env.ENCRYPTION_PEPPER, "utf8"),
} as const;

// ---- Types ------------------------------------------------------------------
// Stored fields are typed as `Uint8Array` (ArrayBuffer-backed) so they assign
// cleanly to Prisma `Bytes` columns. Node's `Buffer` is now generic over
// `ArrayBufferLike` (which includes SharedArrayBuffer) and TS will not narrow it
// to Prisma's `Uint8Array<ArrayBuffer>` — so we copy through `toStorable()`.
// The concrete byte type Prisma `Bytes` columns expect. Node's `Buffer` and a
// default `Uint8Array` are generic over `ArrayBufferLike` (incl. SharedArrayBuffer)
// and won't narrow to this — so stored values pass through `toStorable()`.
export type StoredBytes = Uint8Array<ArrayBuffer>;

export interface WrappedDek {
  dekWrapped: StoredBytes;
  dekWrapIv: StoredBytes;
  dekWrapTag: StoredBytes;
}

export interface AccountKeyMaterial extends WrappedDek {
  masterPassphraseHash: string; // verifier (hex)
  kdfSalt: StoredBytes;
}

/**
 * Copy any Buffer/Uint8Array into a fresh, non-shared, ArrayBuffer-backed
 * Uint8Array. This produces the concrete `Uint8Array<ArrayBuffer>` type that
 * Prisma `Bytes` columns expect, avoiding the `Buffer<ArrayBufferLike>` mismatch.
 */
export function toStorable(b: Uint8Array): StoredBytes {
  const out = new Uint8Array(b.byteLength);
  out.set(b);
  return out;
}

// =============================================================================
//  Master key derivation
// =============================================================================

/**
 * Derive the master key (MK) from a passphrase + per-account salt using
 * Argon2id (with the server pepper as the Argon2 secret). Returns raw 32 bytes.
 *
 * NOTE: `@node-rs/argon2`'s `hash()` returns a PHC-encoded string
 * (`$argon2id$v=19$m=...$<saltB64>$<hashB64>`), not raw bytes. Because we pass a
 * FIXED salt, the output is deterministic, so we decode the trailing hash
 * segment to recover the raw `outputLen` bytes and use THAT as keying material.
 * The encoded string itself is never stored — only HKDF-derived sub-keys are.
 */
async function deriveMasterKey(passphrase: string, salt: Uint8Array): Promise<Buffer> {
  const encoded = await argon2Hash(passphrase, {
    ...ARGON2_OPTS,
    salt: Buffer.from(salt),
    outputLen: KEY_LEN,
    algorithm: 2, // 2 = Argon2id
  });

  // Extract the raw hash bytes from the final PHC field (base64, no padding).
  const lastField = encoded.split("$").pop();
  if (!lastField) throw new Error("Unexpected Argon2 output format");
  const raw = Buffer.from(lastField, "base64");
  if (raw.length !== KEY_LEN) {
    throw new Error(`Argon2 produced ${raw.length} bytes, expected ${KEY_LEN}`);
  }
  return raw;
}

/** Split MK into independent sub-keys via HKDF-SHA256 (domain separation). */
function hkdfExpand(masterKey: Buffer, info: string, len = KEY_LEN): Buffer {
  // Empty salt is fine here: the MK is already a high-entropy, salted secret.
  const out = hkdfSync("sha256", masterKey, Buffer.alloc(0), Buffer.from(info), len);
  return Buffer.from(out);
}

// =============================================================================
//  AES-256-GCM primitives
// =============================================================================

/** Encrypt arbitrary bytes with a 32-byte key. Returns iv, tag, ciphertext. */
export function gcmEncrypt(
  plaintext: Uint8Array,
  key: Uint8Array
): { iv: Buffer; tag: Buffer; ciphertext: Buffer } {
  if (key.length !== KEY_LEN) throw new Error("Invalid key length");
  const iv = randomBytes(IV_LEN);
  const cipher = createCipheriv("aes-256-gcm", key, iv, {
    authTagLength: GCM_TAG_LEN,
  });
  const ciphertext = Buffer.concat([cipher.update(plaintext), cipher.final()]);
  const tag = cipher.getAuthTag();
  return { iv, tag, ciphertext };
}

/** Decrypt. Throws if the auth tag fails (tampering or wrong key). */
export function gcmDecrypt(
  ciphertext: Uint8Array,
  key: Uint8Array,
  iv: Uint8Array,
  tag: Uint8Array
): Buffer {
  const decipher = createDecipheriv("aes-256-gcm", key, iv, {
    authTagLength: GCM_TAG_LEN,
  });
  decipher.setAuthTag(tag);
  return Buffer.concat([decipher.update(ciphertext), decipher.final()]);
}

/**
 * Self-describing single-blob format for DB columns that hold one secret:
 *   [ IV (12) | TAG (16) | CIPHERTEXT (...) ]
 * This keeps the schema simple (one Bytes column) while remaining unambiguous.
 */
export function sealField(plaintext: string | Buffer, dek: Buffer): StoredBytes {
  const data = typeof plaintext === "string" ? Buffer.from(plaintext, "utf8") : plaintext;
  const { iv, tag, ciphertext } = gcmEncrypt(data, dek);
  return toStorable(Buffer.concat([iv, tag, ciphertext]));
}

export function openField(blob: Buffer, dek: Buffer): Buffer {
  if (blob.length < IV_LEN + GCM_TAG_LEN) throw new Error("Ciphertext too short");
  const iv = blob.subarray(0, IV_LEN);
  const tag = blob.subarray(IV_LEN, IV_LEN + GCM_TAG_LEN);
  const ciphertext = blob.subarray(IV_LEN + GCM_TAG_LEN);
  return gcmDecrypt(ciphertext, dek, iv, tag);
}

export function openFieldString(blob: Buffer, dek: Buffer): string {
  return openField(blob, dek).toString("utf8");
}

// =============================================================================
//  Account key lifecycle
// =============================================================================

/**
 * Initialize all crypto material for a new account from its master passphrase.
 * Generates a fresh random DEK and wraps it. Call this when an admin first sets
 * the passphrase during onboarding.
 */
export async function initializeAccountKeys(
  passphrase: string
): Promise<AccountKeyMaterial> {
  const kdfSalt = randomBytes(SALT_LEN);
  const mk = await deriveMasterKey(passphrase, kdfSalt);
  const verifier = hkdfExpand(mk, "verifier");
  const kek = hkdfExpand(mk, "kek");

  // The actual data key: random, high-entropy, independent of the passphrase.
  const dek = randomBytes(KEY_LEN);
  const { iv, tag, ciphertext } = gcmEncrypt(dek, kek);

  // Wipe transient secrets from memory ASAP (best-effort; GC still applies).
  mk.fill(0);
  kek.fill(0);
  dek.fill(0);

  return {
    masterPassphraseHash: verifier.toString("hex"),
    kdfSalt: toStorable(kdfSalt),
    dekWrapped: toStorable(ciphertext),
    dekWrapIv: toStorable(iv),
    dekWrapTag: toStorable(tag),
  };
}

/**
 * Verify a passphrase and, on success, unwrap and return the DEK.
 * Returns null on an incorrect passphrase (constant-time verifier compare).
 */
export async function unlockDek(
  passphrase: string,
  material: {
    masterPassphraseHash: string;
    kdfSalt: Uint8Array;
    dekWrapped: Uint8Array;
    dekWrapIv: Uint8Array;
    dekWrapTag: Uint8Array;
  }
): Promise<Buffer | null> {
  const mk = await deriveMasterKey(passphrase, material.kdfSalt);
  const verifier = hkdfExpand(mk, "verifier");

  const expected = Buffer.from(material.masterPassphraseHash, "hex");
  // Constant-time comparison defeats timing oracles.
  if (
    verifier.length !== expected.length ||
    !timingSafeEqual(verifier, expected)
  ) {
    mk.fill(0);
    verifier.fill(0);
    return null;
  }

  const kek = hkdfExpand(mk, "kek");
  try {
    const dek = gcmDecrypt(
      material.dekWrapped,
      kek,
      material.dekWrapIv,
      material.dekWrapTag
    );
    return dek;
  } catch {
    // Auth-tag failure: corrupted/tampered wrapped DEK.
    return null;
  } finally {
    mk.fill(0);
    kek.fill(0);
    verifier.fill(0);
  }
}

/**
 * Rotate the master passphrase WITHOUT re-encrypting any data.
 * We unwrap the existing DEK with the old passphrase, then re-wrap the SAME DEK
 * under a key derived from the new passphrase. This is O(1) regardless of how
 * many encrypted rows exist — the whole point of envelope encryption.
 */
export async function rewrapDek(
  currentPassphrase: string,
  newPassphrase: string,
  material: {
    masterPassphraseHash: string;
    kdfSalt: Uint8Array;
    dekWrapped: Uint8Array;
    dekWrapIv: Uint8Array;
    dekWrapTag: Uint8Array;
  }
): Promise<AccountKeyMaterial | null> {
  const dek = await unlockDek(currentPassphrase, material);
  if (!dek) return null;

  const kdfSalt = randomBytes(SALT_LEN);
  const mk = await deriveMasterKey(newPassphrase, kdfSalt);
  const verifier = hkdfExpand(mk, "verifier");
  const kek = hkdfExpand(mk, "kek");
  const { iv, tag, ciphertext } = gcmEncrypt(dek, kek);

  mk.fill(0);
  kek.fill(0);
  dek.fill(0);

  return {
    masterPassphraseHash: verifier.toString("hex"),
    kdfSalt: toStorable(kdfSalt),
    dekWrapped: toStorable(ciphertext),
    dekWrapIv: toStorable(iv),
    dekWrapTag: toStorable(tag),
  };
}

// =============================================================================
//  Misc helpers
// =============================================================================

/** SHA-256 hex of a buffer/string (used for invite tokens, file integrity). */
export function sha256Hex(input: Buffer | string): string {
  return createHash("sha256")
    .update(typeof input === "string" ? Buffer.from(input) : input)
    .digest("hex");
}

/** A cryptographically-strong URL-safe random token (e.g. invitations). */
export function generateToken(bytes = 32): string {
  return randomBytes(bytes).toString("base64url");
}

/** Re-export argon2 verify for any standalone password checks if needed. */
export { argon2Verify };
