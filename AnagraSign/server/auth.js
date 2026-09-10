import crypto from 'node:crypto';
import { config } from './config.js';

const COOKIE = 'ss_session';

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

export function makeSessionToken() {
  const payload = `admin.${Date.now() + config.sessionTtlMs}`;
  return `${payload}.${hmac(payload)}`;
}

export function verifySessionToken(token) {
  if (!token) return false;
  const parts = token.split('.');
  if (parts.length !== 3) return false;
  const [role, exp, sig] = parts;
  const expected = hmac(`${role}.${exp}`);
  if (sig.length !== expected.length) return false;
  if (!crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return false;
  return role === 'admin' && Number(exp) > Date.now();
}

export function checkPassword(candidate) {
  const a = crypto.createHash('sha256').update(String(candidate || '')).digest();
  const b = crypto.createHash('sha256').update(config.adminPassword).digest();
  return crypto.timingSafeEqual(a, b);
}

export function setSessionCookie(res, token) {
  const attrs = [`${COOKIE}=${token}`, 'Path=/', 'HttpOnly', 'SameSite=Lax', `Max-Age=${Math.floor(config.sessionTtlMs / 1000)}`];
  if (config.secureCookies) attrs.push('Secure');
  res.setHeader('Set-Cookie', attrs.join('; '));
}

export function clearSessionCookie(res) {
  res.setHeader('Set-Cookie', `${COOKIE}=; Path=/; HttpOnly; Max-Age=0`);
}

// ---- Signer verification cookie (set after an access code / email code check passes) ----
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

export function isAdmin(req) {
  return verifySessionToken(parseCookies(req.headers.cookie)[COOKIE]);
}

export function requireAdmin(req, res, next) {
  if (isAdmin(req)) return next();
  res.status(401).json({ error: 'Not signed in' });
}
