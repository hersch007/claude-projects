import crypto from 'node:crypto';
import express from 'express';
import { config } from '../config.js';
import { db, q, nowIso, logEvent, loadEnvelope } from '../db.js';
import { isSignerVerified, setSignerCookie } from '../auth.js';
import { sendMail, verificationCodeEmail } from '../services/mail.js';
import {
  WorkflowError, isSignersTurn, afterSignerSigned, declineEnvelope, originalPath, completedPath,
} from '../services/workflow.js';

export const signRouter = express.Router();

const OTP_TTL_MS = 15 * 60 * 1000;
const OTP_RESEND_MS = 60 * 1000;
const MAX_ATTEMPTS = 5;
const LOCK_MS = 15 * 60 * 1000;

const ctx = (req) => ({ ip: req.ip, userAgent: req.headers['user-agent'] });
const hash = (s) => crypto.createHash('sha256').update(String(s)).digest('hex');
const safeEq = (a, b) => { const x = Buffer.from(hash(a)); const y = Buffer.from(hash(b)); return crypto.timingSafeEqual(x, y); };
const maskEmail = (e) => { const [u, d] = String(e).split('@'); return `${u.slice(0, 1)}${'*'.repeat(Math.max(2, u.length - 1))}@${d || ''}`; };

/** Resolve a signer token to { env, signer } or throw. */
function resolve(token) {
  const signer = q.signerByToken.get(String(token || ''));
  if (!signer) throw new WorkflowError('This signing link is not valid.', 404);
  const env = loadEnvelope(signer.envelope_id);
  return { env, signer };
}

const needsAuth = (env) => env.auth_method && env.auth_method !== 'none';
const isLocked = (signer) => signer.locked_until && new Date(signer.locked_until).getTime() > Date.now();

/** Throws unless verification is not required or this browser has passed it. */
function requireVerified(req, env, signer) {
  if (needsAuth(env) && !isSignerVerified(req, signer.id)) {
    const err = new WorkflowError('Please verify your identity first.', 403);
    err.code = 'verification_required';
    throw err;
  }
}

function authInfo(req, env, signer) {
  if (!needsAuth(env)) return null;
  return {
    method: env.auth_method,
    verified: isSignerVerified(req, signer.id),
    email_masked: maskEmail(signer.email),
    locked_until: isLocked(signer) ? signer.locked_until : null,
    code_sent_at: signer.otp_sent_at,
  };
}

function formatDate(d = new Date()) {
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${mm}/${dd}/${d.getFullYear()}`;
}

signRouter.get('/:token', (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    const auth = authInfo(req, env, signer);
    const base = {
      envelope: { id: env.id, title: env.title, status: env.status, page_count: env.page_count, signing_order: env.signing_order, auth_method: env.auth_method },
      signer: { id: signer.id, name: signer.name, status: signer.status },
      auth,
      sender: config.mailFrom,
    };
    if (auth && !auth.verified) return res.json(base); // gated: no document details until verified

    const turn = isSignersTurn(env, env.signers, signer);
    if (signer.status === 'pending' && env.status === 'sent' && turn) {
      db.prepare('UPDATE signers SET status = ?, viewed_at = ? WHERE id = ?').run('viewed', nowIso(), signer.id);
      logEvent(env.id, 'viewed', '', { signerId: signer.id, ...ctx(req) });
      signer.status = 'viewed';
    }
    const signerById = new Map(env.signers.map((s) => [s.id, s]));
    res.json({
      ...base,
      envelope: { ...base.envelope, message: env.message, completed_sha256: env.completed_sha256 },
      signer: { ...base.signer, email: signer.email, status: signer.status, consent_at: signer.consent_at, signed_at: signer.signed_at },
      turn,
      waiting_on: turn ? null : env.signers.filter((s) => s.order_index < signer.order_index && s.status !== 'signed').map((s) => s.name),
      fields: env.fields.map((f) => ({
        id: f.id, type: f.type, page: f.page, x: f.x, y: f.y, w: f.w, h: f.h, required: !!f.required, label: f.label,
        mine: f.signer_id === signer.id,
        signer_name: signerById.get(f.signer_id)?.name || '',
        value: f.value,
      })),
    });
  } catch (err) { next(err); }
});

// ---- identity verification ----
signRouter.post('/:token/request-code', async (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    if (env.auth_method !== 'email_code') throw new WorkflowError('This document does not use email codes.');
    if (isLocked(signer)) throw new WorkflowError('Too many incorrect attempts. Try again later.', 423);
    if (signer.otp_sent_at && Date.now() - new Date(signer.otp_sent_at).getTime() < OTP_RESEND_MS) {
      throw new WorkflowError('A code was just sent. Wait a minute before requesting another.', 429);
    }
    const code = String(crypto.randomInt(0, 1_000_000)).padStart(6, '0');
    const now = nowIso();
    db.prepare('UPDATE signers SET otp_hash = ?, otp_expires_at = ?, otp_sent_at = ?, otp_attempts = 0 WHERE id = ?')
      .run(hash(code), new Date(Date.now() + OTP_TTL_MS).toISOString(), now, signer.id);
    const mail = await sendMail({ to: signer.email, ...verificationCodeEmail({ envelope: env, signer, code }) });
    logEvent(env.id, 'code_sent', `One-time code emailed to ${signer.email}${mail.sent ? '' : ' (SMTP not configured - see outbox)'}`, { signerId: signer.id, ...ctx(req) });
    res.json({ ok: true, sent_to: maskEmail(signer.email), expires_in: OTP_TTL_MS / 1000 });
  } catch (err) { next(err); }
});

signRouter.post('/:token/verify', (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    if (!needsAuth(env)) return res.json({ ok: true, verified: true });
    if (isLocked(signer)) throw new WorkflowError('Too many incorrect attempts. Try again in 15 minutes.', 423);
    const code = String(req.body?.code || '').trim();
    if (!code) throw new WorkflowError('Enter the code.');

    let good = false;
    if (env.auth_method === 'access_code') {
      good = !!signer.access_code && safeEq(code.toLowerCase(), String(signer.access_code).trim().toLowerCase());
    } else if (env.auth_method === 'email_code') {
      const fresh = signer.otp_hash && signer.otp_expires_at && new Date(signer.otp_expires_at).getTime() > Date.now();
      if (!fresh) throw new WorkflowError('That code has expired. Request a new one.', 410);
      good = safeEq(hash(code), signer.otp_hash);
    }

    if (!good) {
      const attempts = (signer.otp_attempts || 0) + 1;
      if (attempts >= MAX_ATTEMPTS) {
        db.prepare('UPDATE signers SET otp_attempts = 0, locked_until = ?, otp_hash = NULL WHERE id = ?').run(new Date(Date.now() + LOCK_MS).toISOString(), signer.id);
        logEvent(env.id, 'verification_locked', `${MAX_ATTEMPTS} incorrect ${env.auth_method === 'access_code' ? 'access code' : 'one-time code'} attempts`, { signerId: signer.id, ...ctx(req) });
        throw new WorkflowError('Too many incorrect attempts. Try again in 15 minutes.', 423);
      }
      db.prepare('UPDATE signers SET otp_attempts = ? WHERE id = ?').run(attempts, signer.id);
      logEvent(env.id, 'verification_failed', `Incorrect ${env.auth_method === 'access_code' ? 'access code' : 'one-time code'} (attempt ${attempts})`, { signerId: signer.id, ...ctx(req) });
      throw new WorkflowError(`Incorrect code. ${MAX_ATTEMPTS - attempts} attempt(s) left.`, 401);
    }

    db.prepare('UPDATE signers SET verified_at = COALESCE(verified_at, ?), verified_method = ?, otp_hash = NULL, otp_attempts = 0, locked_until = NULL WHERE id = ?')
      .run(nowIso(), env.auth_method, signer.id);
    logEvent(env.id, 'verified', env.auth_method === 'access_code' ? 'Access code accepted' : 'One-time email code accepted', { signerId: signer.id, ...ctx(req) });
    setSignerCookie(res, signer.id);
    res.json({ ok: true, verified: true });
  } catch (err) { next(err); }
});

// ---- document access (gated) ----
signRouter.get('/:token/pdf', (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    requireVerified(req, env, signer);
    res.type('application/pdf').sendFile(originalPath(env.id));
  } catch (err) { next(err); }
});

signRouter.get('/:token/completed', (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    requireVerified(req, env, signer);
    if (env.status !== 'completed') throw new WorkflowError('The document is not completed yet.', 404);
    res.type('application/pdf');
    if (req.query.download) res.attachment(`${env.title.replace(/[^\w.-]+/g, '_')}-signed.pdf`);
    res.sendFile(completedPath(env.id));
  } catch (err) { next(err); }
});

signRouter.post('/:token/consent', (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    requireVerified(req, env, signer);
    if (!signer.consent_at) {
      db.prepare('UPDATE signers SET consent_at = ? WHERE id = ?').run(nowIso(), signer.id);
      logEvent(env.id, 'consented', 'Agreed to use electronic records and signatures', { signerId: signer.id, ...ctx(req) });
    }
    res.json({ ok: true });
  } catch (err) { next(err); }
});

signRouter.post('/:token/submit', async (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    requireVerified(req, env, signer);
    if (env.status !== 'sent') throw new WorkflowError(`This document is ${env.status} and can no longer be signed.`, 409);
    if (signer.status === 'signed') throw new WorkflowError('You have already signed this document.', 409);
    if (signer.status === 'declined') throw new WorkflowError('You declined this document.', 409);
    if (!isSignersTurn(env, env.signers, signer)) throw new WorkflowError('It is not your turn to sign yet.', 409);
    if (req.body?.consent !== true && !signer.consent_at) throw new WorkflowError('You must agree to sign electronically.');

    const submitted = new Map((Array.isArray(req.body?.fields) ? req.body.fields : []).map((f) => [String(f.id), f.value]));
    const mine = env.fields.filter((f) => f.signer_id === signer.id);
    const ts = nowIso();
    const updates = [];

    for (const f of mine) {
      let value = submitted.get(f.id);
      switch (f.type) {
        case 'signature':
        case 'initials': {
          if (typeof value === 'string' && value) {
            if (!/^data:image\/(png|jpe?g);base64,[A-Za-z0-9+/=]+$/.test(value)) throw new WorkflowError('Signature image is not valid.');
            if (value.length > config.maxSignatureBytes * 1.4) throw new WorkflowError('Signature image is too large.');
          } else value = null;
          break;
        }
        case 'checkbox':
          value = value === true || value === '1' || value === 1 ? '1' : '0';
          break;
        case 'text':
          value = typeof value === 'string' ? value.trim().slice(0, 500) : '';
          break;
        case 'date':
          value = formatDate();
          break;
        case 'name':
          value = signer.name;
          break;
      }
      const empty = value == null || value === '' || (f.type === 'checkbox' && value === '0');
      if (f.required && empty) throw new WorkflowError(`Please complete the required ${f.type} field${f.label ? ` "${f.label}"` : ''} on page ${f.page}.`);
      updates.push([empty ? null : value, empty ? null : ts, f.id]);
    }

    db.exec('BEGIN');
    try {
      const up = db.prepare('UPDATE fields SET value = ?, filled_at = ? WHERE id = ?');
      for (const u of updates) up.run(...u);
      db.prepare('UPDATE signers SET status = ?, signed_at = ?, consent_at = COALESCE(consent_at, ?), ip = ?, user_agent = ? WHERE id = ?')
        .run('signed', ts, ts, req.ip, String(req.headers['user-agent'] || '').slice(0, 500), signer.id);
      logEvent(env.id, 'signed', `Completed ${mine.length} field(s)`, { signerId: signer.id, ...ctx(req) });
      db.exec('COMMIT');
    } catch (e) { db.exec('ROLLBACK'); throw e; }

    const result = await afterSignerSigned(env.id);
    const after = q.envelope.get(env.id);
    res.json({ ok: true, status: after.status, completed: after.status === 'completed', result });
  } catch (err) { next(err); }
});

signRouter.post('/:token/decline', async (req, res, next) => {
  try {
    const { env, signer } = resolve(req.params.token);
    requireVerified(req, env, signer);
    if (env.status !== 'sent') throw new WorkflowError(`This document is ${env.status}.`, 409);
    if (signer.status === 'signed') throw new WorkflowError('You have already signed this document.', 409);
    await declineEnvelope(env.id, signer, String(req.body?.reason || '').slice(0, 500), ctx(req));
    res.json({ ok: true });
  } catch (err) { next(err); }
});
