import fs from 'node:fs';
import path from 'node:path';
import { config } from '../config.js';
import { db, q, nowIso, newToken, logEvent, loadEnvelope } from '../db.js';
import { sendMail, signingRequestEmail, completedEmail, declinedEmail } from './mail.js';
import { buildCompletedPdf, sha256 } from './pdf.js';

export const originalPath = (envelopeId) => path.join(config.uploadsDir, `${envelopeId}.pdf`);
export const completedPath = (envelopeId) => path.join(config.completedDir, `${envelopeId}.pdf`);
export const signingLink = (signer) => `${config.baseUrl}/sign/${signer.token}`;
export const dashboardLink = (envelopeId) => `${config.baseUrl}/?envelope=${envelopeId}`;

export class WorkflowError extends Error {
  constructor(message, status = 400) { super(message); this.status = status; }
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** Whether this signer is allowed to sign right now (sequential order support). */
export function isSignersTurn(envelope, signers, signer) {
  if (envelope.signing_order === 'parallel') return true;
  return signers.filter((s) => s.order_index < signer.order_index).every((s) => s.status === 'signed');
}

function validateForSend(env) {
  if (env.status !== 'draft') throw new WorkflowError(`Envelope is already ${env.status}`);
  if (!env.signers.length) throw new WorkflowError('Add at least one signer');
  for (const s of env.signers) {
    if (!s.name.trim()) throw new WorkflowError('Every signer needs a name');
    if (!EMAIL_RE.test(s.email)) throw new WorkflowError(`Invalid email for ${s.name || 'a signer'}: ${s.email}`);
    if (!env.fields.some((f) => f.signer_id === s.id)) throw new WorkflowError(`${s.name} has no fields placed on the document`);
    if (env.auth_method === 'access_code' && String(s.access_code || '').length < 4) throw new WorkflowError(`${s.name} needs an access code of at least 4 characters`);
  }
  if (!env.fields.some((f) => f.type === 'signature')) throw new WorkflowError('Place at least one signature field');
}

/** Send a draft envelope: assign signer tokens, mark sent, notify whoever is up first. */
export async function sendEnvelope(envelopeId, ctx = {}) {
  const env = loadEnvelope(envelopeId);
  if (!env) throw new WorkflowError('Envelope not found', 404);
  validateForSend(env);

  const ts = nowIso();
  db.exec('BEGIN');
  try {
    for (const s of env.signers) {
      db.prepare('UPDATE signers SET token = ?, status = ? WHERE id = ?').run(newToken(), 'pending', s.id);
    }
    db.prepare('UPDATE envelopes SET status = ?, sent_at = ? WHERE id = ?').run('sent', ts, envelopeId);
    logEvent(envelopeId, 'sent', `Sent to ${env.signers.length} signer(s), ${env.signing_order} order`, ctx);
    db.exec('COMMIT');
  } catch (e) {
    db.exec('ROLLBACK');
    throw e;
  }
  return notifyPendingSigners(envelopeId);
}

/** Email every signer whose turn it is and who has not been notified yet (or force with reminder=true). */
export async function notifyPendingSigners(envelopeId, { reminder = false } = {}) {
  const env = loadEnvelope(envelopeId);
  const results = [];
  for (const s of env.signers) {
    if (s.status === 'signed' || s.status === 'declined') continue;
    if (!isSignersTurn(env, env.signers, s)) continue;
    if (s.notified_at && !reminder) continue;
    const link = signingLink(s);
    const r = await sendMail({ to: s.email, ...signingRequestEmail({ envelope: env, signer: s, link, reminder }) });
    db.prepare('UPDATE signers SET notified_at = ? WHERE id = ?').run(nowIso(), s.id);
    logEvent(envelopeId, reminder ? 'reminder_sent' : 'invitation_sent', `${s.email} (${r.sent ? 'delivered to SMTP' : 'not sent - see outbox'})`, { signerId: s.id });
    results.push({ signer: s.email, ...r });
  }
  return results;
}

/** Called after a signer completes their fields. Advances to the next signer or finalizes. */
export async function afterSignerSigned(envelopeId) {
  const env = loadEnvelope(envelopeId);
  if (env.signers.every((s) => s.status === 'signed')) return finalizeEnvelope(envelopeId);
  return notifyPendingSigners(envelopeId);
}

export async function finalizeEnvelope(envelopeId) {
  const env = loadEnvelope(envelopeId);
  const completedAt = nowIso();
  env.completed_at = completedAt;
  const originalBytes = fs.readFileSync(originalPath(envelopeId));
  const bytes = await buildCompletedPdf({ envelope: env, signers: env.signers, fields: env.fields, events: env.events, originalBytes });
  const hash = sha256(bytes);
  fs.writeFileSync(completedPath(envelopeId), bytes);
  db.prepare('UPDATE envelopes SET status = ?, completed_at = ?, completed_sha256 = ? WHERE id = ?').run('completed', completedAt, hash, envelopeId);
  logEvent(envelopeId, 'completed', `Completed PDF generated, SHA-256 ${hash}`);

  const done = loadEnvelope(envelopeId);
  const recipients = new Set(done.signers.map((s) => s.email));
  for (const s of done.signers) {
    await sendMail({ to: s.email, ...completedEmail({ envelope: done, link: `${signingLink(s)}` }) });
  }
  if (config.notifyEmail && !recipients.has(config.notifyEmail)) {
    await sendMail({ to: config.notifyEmail, ...completedEmail({ envelope: done, link: dashboardLink(envelopeId) }) });
  }
  return { completed: true, sha256: hash };
}

export async function declineEnvelope(envelopeId, signer, reason, ctx = {}) {
  const ts = nowIso();
  db.prepare('UPDATE signers SET status = ?, decline_reason = ?, ip = ?, user_agent = ? WHERE id = ?')
    .run('declined', reason || '', ctx.ip || null, ctx.userAgent || null, signer.id);
  db.prepare('UPDATE envelopes SET status = ? WHERE id = ?').run('declined', envelopeId);
  logEvent(envelopeId, 'declined', reason ? `Reason: ${reason}` : '', { signerId: signer.id, ...ctx });
  const env = q.envelope.get(envelopeId);
  if (config.notifyEmail) {
    await sendMail({ to: config.notifyEmail, ...declinedEmail({ envelope: env, signer, reason, link: dashboardLink(envelopeId) }) });
  }
}

export function voidEnvelope(envelopeId, reason = '') {
  const env = q.envelope.get(envelopeId);
  if (!env) throw new WorkflowError('Envelope not found', 404);
  if (env.status === 'completed') throw new WorkflowError('Completed envelopes cannot be voided');
  db.prepare('UPDATE envelopes SET status = ? WHERE id = ?').run('voided', envelopeId);
  logEvent(envelopeId, 'voided', reason);
}

export function deleteEnvelope(envelopeId) {
  db.prepare('DELETE FROM envelopes WHERE id = ?').run(envelopeId);
  for (const p of [originalPath(envelopeId), completedPath(envelopeId)]) {
    try { fs.unlinkSync(p); } catch { /* already gone */ }
  }
}
