import crypto from 'node:crypto';
import { config } from './config.js';
import { db, q, nowIso } from './db.js';

const COOKIE = 'ss_session';
export const ROLES = ['admin', 'user'];

function hmac(data) {
  return crypto.createHmac('sha256', config.sessionSecret).update(data).digest('base64url');
}

export function parseCookies(header = '') {
  const out = {};
  for (const part of header.split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

// ---- Session tokens now carry a role (admin | user) ----

export function makeSessionToken(role) {
  const payload = `${role}.${Date.now() + config.sessionTtlMs}`;
  return `${payload}.${hmac(payload)}`;
}

/** Returns the role ('admin' | 'user') if the token is valid and unexpired, else null. */
export function verifySessionToken(token) {
  if (!token) return null;
  const parts = token.split('.');
  if (parts.length !== 3) return null;
  const [role, exp, sig] = parts;
  if (!ROLES.includes(role)) return null;
  const expected = hmac(`${role}.${exp}`);
  if (sig.length !== expected.length) return null;
  if (!crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return null;
  return Number(exp) > Date.now() ? role : null;
}

export function setSessionCookie(res, token) {
  const attrs = [`${COOKIE}=${token}`, 'Path=/', 'HttpOnly', 'SameSite=Lax', `Max-Age=${Math.floor(config.sessionTtlMs / 1000)}`];
  if (config.secureCookies) attrs.push('Secure');
  res.setHeader('Set-Cookie', attrs.join('; '));
}

export function clearSessionCookie(res) {
  res.setHeader('Set-Cookie', `${COOKIE}=; Path=/; HttpOnly; Max-Age=0`);
}

/** The caller's role ('admin' | 'user'), or null if not signed in. */
export function getRole(req) {
  return verifySessionToken(parseCookies(req.headers.cookie)[COOKIE]);
}

/** Any signed-in role — everyday document work. */
export function requireAuth(req, res, next) {
  const role = getRole(req);
  if (role) { req.role = role; return next(); }
  res.status(401).json({ error: 'Not signed in' });
}

/** Admin only — destructive actions and credential management. */
export function requireAdmin(req, res, next) {
  const role = getRole(req);
  if (role === 'admin') { req.role = role; return next(); }
  res.status(role ? 403 : 401).json({ error: role ? 'Admins only' : 'Not signed in' });
}

// ---- Password hashing (scrypt, no extra dependency) ----

function hashPassword(password) {
  const salt = crypto.randomBytes(16);
  const derived = crypto.scryptSync(String(password), salt, 64);
  return `${salt.toString('hex')}:${derived.toString('hex')}`;
}

function verifyPassword(password, stored) {
  const [saltHex, hashHex] = String(stored || '').split(':');
  if (!saltHex || !hashHex) return false;
  const salt = Buffer.from(saltHex, 'hex');
  const expected = Buffer.from(hashHex, 'hex');
  const actual = crypto.scryptSync(String(password), salt, expected.length);
  return actual.length === expected.length && crypto.timingSafeEqual(actual, expected);
}

/** Seed app_auth from env vars on startup. Never overwrites a password already set via the app —
 * UNLESS RESET_ADMIN_PASSWORD=1, a deliberate recovery escape hatch if the admin password is lost. */
export function seedAuthFromEnv() {
  const adminRow = q.authRole.get('admin');
  if (!adminRow) {
    if (!config.adminPassword) return; // assertConfig() already refuses to start without it
    setRolePassword('admin', config.adminPassword);
    console.log('[auth] seeded initial admin password from ADMIN_PASSWORD env var');
  } else if (config.resetAdminPassword && config.adminPassword) {
    setRolePassword('admin', config.adminPassword);
    console.log('[auth] RESET_ADMIN_PASSWORD was set — admin password reset from ADMIN_PASSWORD env var. Remove that env var once you have logged in.');
  }
  // The "user" role stays disabled (no row) until an admin sets one from the app — no env var
  // needed for it, since an admin can always log in and create it from the Settings panel.
}

export function roleEnabled(role) {
  return !!q.authRole.get(role);
}

export function checkRolePassword(role, candidate) {
  const row = q.authRole.get(role);
  if (!row) return false;
  return verifyPassword(candidate, row.password_hash);
}

export function setRolePassword(role, newPassword) {
  const hash = hashPassword(newPassword);
  db.prepare(
    'INSERT INTO app_auth (role, password_hash, updated_at) VALUES (?, ?, ?) ' +
    'ON CONFLICT(role) DO UPDATE SET password_hash = excluded.password_hash, updated_at = excluded.updated_at'
  ).run(role, hash, nowIso());
}

// ---- Signer verification cookie (set after an access code / email code check passes) ----
// Unrelated to the admin/user session above — this is per-signer, scoped to one envelope's
// signer row, set by routes/sign.js after a correct access/email code (see requireVerified there).
const SIGNER_TTL_MS = 2 * 60 * 60 * 1000;
export const signerCookieName = (signerId) => `sv_${String(signerId).replace(/-/g, '')}`;

export function makeSignerToken(signerId) {
  const payload = `sv.${signerId}.${Date.now() + SIGNER_TTL_MS}`;
  return `${payload}.${hmac(payload)}`;
}

export function verifySignerToken(token, signerId) {
  if (!token) return false;
  const parts = token.split('.');
  if (parts.length !== 4) return false;
  const [tag, id, exp, sig] = parts;
  const expected = hmac(`${tag}.${id}.${exp}`);
  if (sig.length !== expected.length) return false;
  if (!crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return false;
  return tag === 'sv' && id === signerId && Number(exp) > Date.now();
}

export function setSignerCookie(res, signerId) {
  const attrs = [`${signerCookieName(signerId)}=${makeSignerToken(signerId)}`, 'Path=/', 'HttpOnly', 'SameSite=Lax', `Max-Age=${Math.floor(SIGNER_TTL_MS / 1000)}`];
  if (config.secureCookies) attrs.push('Secure');
  res.append('Set-Cookie', attrs.join('; '));
}

export function isSignerVerified(req, signerId) {
  return verifySignerToken(parseCookies(req.headers.cookie)[signerCookieName(signerId)], signerId);
}
