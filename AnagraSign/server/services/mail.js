import fs from 'node:fs';
import path from 'node:path';
import nodemailer from 'nodemailer';
import { config } from '../config.js';

let transporter = null;
if (config.smtp.host) {
  transporter = nodemailer.createTransport({
    host: config.smtp.host,
    port: config.smtp.port,
    secure: config.smtp.secure,
    auth: config.smtp.user ? { user: config.smtp.user, pass: config.smtp.pass } : undefined,
  });
}

export const mailEnabled = Boolean(transporter);

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const slug = (s) => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 50);

/**
 * Send an email. When SMTP is not configured the message is written to storage/outbox
 * so you can still copy the signing link during development.
 */
export async function sendMail({ to, subject, text, html }) {
  if (!to) return { sent: false, reason: 'no recipient' };
  if (!transporter) {
    const file = path.join(config.outboxDir, `${Date.now()}-${Math.random().toString(36).slice(2, 6)}-${slug(subject)}.txt`);
    fs.writeFileSync(file, `To: ${to}\nFrom: ${config.mailFrom}\nSubject: ${subject}\n\n${text}\n`);
    console.log(`[mail] SMTP not configured - wrote to ${path.relative(config.storageDir, file)} (to: ${to})`);
    return { sent: false, outbox: file };
  }
  try {
    await transporter.sendMail({ from: config.mailFrom, to, subject, text, html });
    console.log(`[mail] sent "${subject}" to ${to}`);
    return { sent: true };
  } catch (err) {
    console.error(`[mail] FAILED "${subject}" to ${to}: ${err.message}`);
    return { sent: false, error: err.message };
  }
}

function layout(title, bodyHtml, cta) {
  return `<!doctype html><html><body style="margin:0;padding:24px;background:#f4f6f9;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#1f2937">
<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;padding:32px;border:1px solid #e5e7eb">
<h2 style="margin:0 0 16px;font-size:20px;color:#0f2a5a">${esc(title)}</h2>
${bodyHtml}
${cta ? `<p style="margin:28px 0"><a href="${esc(cta.href)}" style="background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:600;display:inline-block">${esc(cta.label)}</a></p>
<p style="font-size:12px;color:#6b7280">Or copy this link into your browser:<br>${esc(cta.href)}</p>` : ''}
</div></body></html>`;
}

export function signingRequestEmail({ envelope, signer, link, reminder = false }) {
  const subject = `${reminder ? 'Reminder: ' : ''}Please sign "${envelope.title}"`;
  const intro = envelope.message ? `${envelope.message}\n\n` : '';
  const text = `Hello ${signer.name},\n\n${intro}You have been asked to review and sign "${envelope.title}".\n\nReview and sign here:\n${link}\n\nThis link is unique to you. Please do not forward it.`;
  const html = layout(subject, `<p>Hello ${esc(signer.name)},</p>${envelope.message ? `<p style="white-space:pre-wrap">${esc(envelope.message)}</p>` : ''}<p>You have been asked to review and sign <strong>${esc(envelope.title)}</strong>.</p><p style="font-size:12px;color:#6b7280">This link is unique to you. Please do not forward it.</p>`, { href: link, label: 'Review & Sign' });
  return { subject, text, html };
}

export function verificationCodeEmail({ envelope, signer, code }) {
  const subject = `Your verification code for "${envelope.title}"`;
  const text = `Hello ${signer.name},\n\nYour verification code is: ${code}\n\nEnter it on the signing page to open "${envelope.title}". The code expires in 15 minutes.\n\nIf you did not request this, you can ignore this email.`;
  const html = layout(subject, `<p>Hello ${esc(signer.name)},</p><p>Your verification code is:</p><p style="font-size:30px;font-weight:700;letter-spacing:6px;color:#0f2a5a;margin:12px 0">${esc(code)}</p><p class="muted" style="font-size:13px;color:#6b7280">Enter it on the signing page to open <strong>${esc(envelope.title)}</strong>. The code expires in 15 minutes. If you did not request this, ignore this email.</p>`);
  return { subject, text, html };
}

export function completedEmail({ envelope, link }) {
  const subject = `Completed: "${envelope.title}"`;
  const text = `All parties have signed "${envelope.title}".\n\nDownload the completed document (with signature certificate):\n${link}\n\nCompleted document SHA-256: ${envelope.completed_sha256}`;
  const html = layout(subject, `<p>All parties have signed <strong>${esc(envelope.title)}</strong>.</p><p style="font-size:12px;color:#6b7280">Completed document SHA-256:<br><code>${esc(envelope.completed_sha256)}</code></p>`, { href: link, label: 'Download signed document' });
  return { subject, text, html };
}

export function declinedEmail({ envelope, signer, reason, link }) {
  const subject = `Declined: "${envelope.title}"`;
  const text = `${signer.name} <${signer.email}> declined to sign "${envelope.title}".\n\nReason: ${reason || '(none given)'}\n\nOpen the dashboard:\n${link}`;
  const html = layout(subject, `<p><strong>${esc(signer.name)}</strong> (${esc(signer.email)}) declined to sign <strong>${esc(envelope.title)}</strong>.</p><p>Reason: ${esc(reason || '(none given)')}</p>`, { href: link, label: 'Open dashboard' });
  return { subject, text, html };
}
