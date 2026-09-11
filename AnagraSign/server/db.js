import { DatabaseSync } from 'node:sqlite';
import crypto from 'node:crypto';
import { config } from './config.js';

export const db = new DatabaseSync(config.dbPath);
db.exec('PRAGMA journal_mode = WAL');
db.exec('PRAGMA foreign_keys = ON');

db.exec(`
CREATE TABLE IF NOT EXISTS envelopes (
  id TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  message TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'draft',
  signing_order TEXT NOT NULL DEFAULT 'sequential',
  auth_method TEXT NOT NULL DEFAULT 'none',
  original_name TEXT NOT NULL,
  page_count INTEGER NOT NULL DEFAULT 0,
  original_sha256 TEXT NOT NULL,
  completed_sha256 TEXT,
  created_at TEXT NOT NULL,
  sent_at TEXT,
  completed_at TEXT
);
CREATE TABLE IF NOT EXISTS signers (
  id TEXT PRIMARY KEY,
  envelope_id TEXT NOT NULL REFERENCES envelopes(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  order_index INTEGER NOT NULL DEFAULT 0,
  token TEXT UNIQUE,
  status TEXT NOT NULL DEFAULT 'pending',
  notified_at TEXT,
  consent_at TEXT,
  viewed_at TEXT,
  signed_at TEXT,
  decline_reason TEXT,
  ip TEXT,
  user_agent TEXT,
  access_code TEXT,
  otp_hash TEXT,
  otp_expires_at TEXT,
  otp_sent_at TEXT,
  otp_attempts INTEGER NOT NULL DEFAULT 0,
  locked_until TEXT,
  verified_at TEXT,
  verified_method TEXT
);
CREATE TABLE IF NOT EXISTS fields (
  id TEXT PRIMARY KEY,
  envelope_id TEXT NOT NULL REFERENCES envelopes(id) ON DELETE CASCADE,
  signer_id TEXT NOT NULL REFERENCES signers(id) ON DELETE CASCADE,
  type TEXT NOT NULL,
  page INTEGER NOT NULL,
  x REAL NOT NULL, y REAL NOT NULL, w REAL NOT NULL, h REAL NOT NULL,
  required INTEGER NOT NULL DEFAULT 1,
  label TEXT NOT NULL DEFAULT '',
  value TEXT,
  filled_at TEXT
);
CREATE TABLE IF NOT EXISTS events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  envelope_id TEXT NOT NULL REFERENCES envelopes(id) ON DELETE CASCADE,
  signer_id TEXT,
  type TEXT NOT NULL,
  detail TEXT NOT NULL DEFAULT '',
  ip TEXT,
  user_agent TEXT,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_signers_env ON signers(envelope_id, order_index);
CREATE INDEX IF NOT EXISTS idx_fields_env ON fields(envelope_id);
CREATE INDEX IF NOT EXISTS idx_events_env ON events(envelope_id, id);
CREATE TABLE IF NOT EXISTS app_auth (
  role TEXT PRIMARY KEY,
  password_hash TEXT NOT NULL,
  updated_at TEXT NOT NULL
);
`);

// Migrations for databases created before a column existed
function ensureColumn(table, col, decl) {
  const cols = db.prepare(`PRAGMA table_info(${table})`).all().map((c) => c.name);
  if (!cols.includes(col)) db.exec(`ALTER TABLE ${table} ADD COLUMN ${col} ${decl}`);
}
ensureColumn('envelopes', 'auth_method', "TEXT NOT NULL DEFAULT 'none'");
for (const [col, decl] of [
  ['imported_source', 'TEXT'], ['imported_signed_date', 'TEXT'], ['imported_note', 'TEXT'],
]) ensureColumn('envelopes', col, decl);
for (const [col, decl] of [
  ['access_code', 'TEXT'], ['otp_hash', 'TEXT'], ['otp_expires_at', 'TEXT'], ['otp_sent_at', 'TEXT'],
  ['otp_attempts', 'INTEGER NOT NULL DEFAULT 0'], ['locked_until', 'TEXT'], ['verified_at', 'TEXT'], ['verified_method', 'TEXT'],
]) ensureColumn('signers', col, decl);

// Statuses
//   app_auth.role: 'admin' | 'user' — a shared password per role (not per-person accounts).
//     Seeded from env vars at startup by auth.js if the row doesn't exist yet; once someone
//     changes a password through the app, the DB row wins from then on.
//   envelopes.status: draft | sent | completed | declined | voided | imported
//     "imported" = uploaded already-signed from elsewhere, for record-keeping only. It never
//     goes through draft/sent/signing; original_sha256/completed_sha256 both apply to it (see
//     services/workflow.js importEnvelope). imported_source/imported_signed_date/imported_note
//     are what the importer told us — not verified, not witnessed by this app.
//   envelopes.auth_method: none | email_code | access_code
//   signers.status:   pending | viewed | signed | declined
//   fields.type:      signature | initials | date | name | text | checkbox
//   fields x/y/w/h:   fractions (0..1) of page width/height, top-left origin

export const nowIso = () => new Date().toISOString();
export const newId = () => crypto.randomUUID();
export const newToken = () => crypto.randomBytes(24).toString('base64url');

export function logEvent(envelopeId, type, detail = '', { signerId = null, ip = null, userAgent = null } = {}) {
  db.prepare(
    'INSERT INTO events (envelope_id, signer_id, type, detail, ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
  ).run(envelopeId, signerId, type, detail, ip, userAgent ? String(userAgent).slice(0, 500) : null, nowIso());
}

export const q = {
  authRole: db.prepare('SELECT * FROM app_auth WHERE role = ?'),
  envelope: db.prepare('SELECT * FROM envelopes WHERE id = ?'),
  envelopes: db.prepare('SELECT * FROM envelopes ORDER BY created_at DESC'),
  signers: db.prepare('SELECT * FROM signers WHERE envelope_id = ? ORDER BY order_index'),
  signerByToken: db.prepare('SELECT * FROM signers WHERE token = ?'),
  signer: db.prepare('SELECT * FROM signers WHERE id = ?'),
  fields: db.prepare('SELECT * FROM fields WHERE envelope_id = ? ORDER BY page, y, x'),
  fieldsForSigner: db.prepare('SELECT * FROM fields WHERE signer_id = ?'),
  events: db.prepare('SELECT * FROM events WHERE envelope_id = ? ORDER BY id'),
};

export function loadEnvelope(id) {
  const envelope = q.envelope.get(id);
  if (!envelope) return null;
  return {
    ...envelope,
    signers: q.signers.all(id),
    fields: q.fields.all(id),
    events: q.events.all(id),
  };
}
