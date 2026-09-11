import fs from 'node:fs';
import express from 'express';
import multer from 'multer';
import { config } from '../config.js';
import { db, q, newId, nowIso, logEvent, loadEnvelope } from '../db.js';
import { requireAuth, requireAdmin } from '../auth.js';
import { inspectPdf, sha256, buildImportRecordPdf } from '../services/pdf.js';
import {
  WorkflowError, sendEnvelope, notifyPendingSigners, voidEnvelope, deleteEnvelope,
  originalPath, completedPath, signingLink, isSignersTurn,
} from '../services/workflow.js';

export const envelopesRouter = express.Router();
// Everyday document work (upload, prepare, send, remind) only needs to be signed in as SOME
// role. Voiding and deleting are destructive enough to reserve for admin — see the individual
// routes below.
envelopesRouter.use(requireAuth);

const FIELD_TYPES = new Set(['signature', 'initials', 'date', 'name', 'text', 'checkbox']);
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: config.maxUploadBytes, files: 1 },
  fileFilter: (req, file, cb) => {
    const ok = file.mimetype === 'application/pdf' || /\.pdf$/i.test(file.originalname);
    cb(ok ? null : new WorkflowError('Only PDF files can be uploaded'), ok);
  },
});

function ctx(req) {
  return { ip: req.ip, userAgent: req.headers['user-agent'] };
}

function present(env) {
  return {
    ...env,
    signers: env.signers.map((s) => ({
      ...s,
      link: s.token ? signingLink(s) : null,
      is_turn: s.token ? isSignersTurn(env, env.signers, s) : false,
    })),
  };
}

// List
envelopesRouter.get('/', (req, res) => {
  const rows = q.envelopes.all().map((e) => {
    const signers = q.signers.all(e.id);
    return {
      ...e,
      signer_count: signers.length,
      signed_count: signers.filter((s) => s.status === 'signed').length,
      signers: signers.map((s) => ({ name: s.name, email: s.email, status: s.status })),
    };
  });
  res.json({ envelopes: rows });
});

// Create (upload PDF)
envelopesRouter.post('/', upload.single('pdf'), async (req, res, next) => {
  try {
    if (!req.file) throw new WorkflowError('Attach a PDF file');
    const { pageCount } = await inspectPdf(req.file.buffer);
    const id = newId();
    const title = String(req.body?.title || '').trim() || req.file.originalname.replace(/\.pdf$/i, '');
    fs.writeFileSync(originalPath(id), req.file.buffer);
    db.prepare(`INSERT INTO envelopes (id, title, message, status, signing_order, original_name, page_count, original_sha256, created_at)
                VALUES (?, ?, '', 'draft', 'sequential', ?, ?, ?, ?)`)
      .run(id, title, req.file.originalname, pageCount, sha256(req.file.buffer), nowIso());
    logEvent(id, 'created', `Uploaded ${req.file.originalname} (${pageCount} pages, ${req.file.size} bytes)`, ctx(req));
    res.status(201).json({ envelope: present(loadEnvelope(id)) });
  } catch (err) { next(err); }
});

// Import an already-signed PDF from elsewhere, for record-keeping only. No signers, no fields,
// no sending — the original is stored untouched and a clearly-labeled Import Record page (NOT a
// signature certificate) documents what the importer told us. See buildImportRecordPdf.
envelopesRouter.post('/import', upload.single('pdf'), async (req, res, next) => {
  try {
    if (!req.file) throw new WorkflowError('Attach the already-signed PDF');
    const title = String(req.body?.title || '').trim().slice(0, 200) || req.file.originalname.replace(/\.pdf$/i, '');
    const importedSource = String(req.body?.imported_source || '').trim().slice(0, 200);
    const importedSignedDate = String(req.body?.imported_signed_date || '').trim().slice(0, 100);
    const importedNote = String(req.body?.imported_note || '').trim().slice(0, 2000);

    const { pageCount } = await inspectPdf(req.file.buffer);
    const id = newId();
    const ts = nowIso();
    const originalHash = sha256(req.file.buffer);
    fs.writeFileSync(originalPath(id), req.file.buffer);

    const envelopeForPdf = {
      id, title, original_name: req.file.originalname, created_at: ts,
      imported_source: importedSource || null, imported_signed_date: importedSignedDate || null, imported_note: importedNote || null,
    };
    const recordBytes = await buildImportRecordPdf({ envelope: envelopeForPdf, originalBytes: req.file.buffer, originalSha256: originalHash });
    const completedHash = sha256(recordBytes);
    fs.writeFileSync(completedPath(id), recordBytes);

    db.prepare(`INSERT INTO envelopes
        (id, title, message, status, signing_order, original_name, page_count, original_sha256, completed_sha256, created_at, completed_at, imported_source, imported_signed_date, imported_note)
        VALUES (?, ?, '', 'imported', 'sequential', ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
      .run(id, title, req.file.originalname, pageCount, originalHash, completedHash, ts, ts, importedSource || null, importedSignedDate || null, importedNote || null);
    logEvent(id, 'imported', `Imported ${req.file.originalname} for record-keeping${importedSource ? ` (via ${importedSource})` : ''}`, ctx(req));

    res.status(201).json({ envelope: present(loadEnvelope(id)) });
  } catch (err) { next(err); }
});

// Detail
envelopesRouter.get('/:id', (req, res) => {
  const env = loadEnvelope(req.params.id);
  if (!env) return res.status(404).json({ error: 'Envelope not found' });
  res.json({ envelope: present(env) });
});

// Update draft: title, message, signing order, signers, fields (full replacement of signers/fields)
envelopesRouter.put('/:id', (req, res, next) => {
  try {
    const env = loadEnvelope(req.params.id);
    if (!env) throw new WorkflowError('Envelope not found', 404);
    if (env.status !== 'draft') throw new WorkflowError('Only drafts can be edited');

    const body = req.body || {};
    const title = String(body.title ?? env.title).trim().slice(0, 200) || env.title;
    const message = String(body.message ?? env.message).slice(0, 2000);
    const signingOrder = body.signing_order === 'parallel' ? 'parallel' : 'sequential';
    const authMethod = ['none', 'email_code', 'access_code'].includes(body.auth_method) ? body.auth_method : env.auth_method;

    const signersIn = Array.isArray(body.signers) ? body.signers : null;
    const fieldsIn = Array.isArray(body.fields) ? body.fields : null;

    db.exec('BEGIN');
    try {
      db.prepare('UPDATE envelopes SET title = ?, message = ?, signing_order = ?, auth_method = ? WHERE id = ?').run(title, message, signingOrder, authMethod, env.id);

      if (signersIn) {
        if (signersIn.length > 20) throw new WorkflowError('Maximum 20 signers');
        db.prepare('DELETE FROM signers WHERE envelope_id = ?').run(env.id); // cascades to fields
        const ins = db.prepare('INSERT INTO signers (id, envelope_id, name, email, order_index, status, access_code) VALUES (?, ?, ?, ?, ?, ?, ?)');
        const ids = signersIn.map((s, i) => {
          const id = newId();
          const name = String(s.name || '').trim().slice(0, 120);
          const email = String(s.email || '').trim().toLowerCase().slice(0, 200);
          if (email && !EMAIL_RE.test(email)) throw new WorkflowError(`Invalid email: ${email}`);
          const code = authMethod === 'access_code' ? String(s.access_code || '').trim().slice(0, 40) : null;
          ins.run(id, env.id, name, email, i, 'pending', code || null);
          return id;
        });

        if (fieldsIn) {
          if (fieldsIn.length > 500) throw new WorkflowError('Too many fields');
          const insF = db.prepare('INSERT INTO fields (id, envelope_id, signer_id, type, page, x, y, w, h, required, label) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
          for (const f of fieldsIn) {
            if (!FIELD_TYPES.has(f.type)) throw new WorkflowError(`Unknown field type: ${f.type}`);
            const si = Number(f.signer_index);
            if (!Number.isInteger(si) || si < 0 || si >= ids.length) throw new WorkflowError('Field assigned to a missing signer');
            const page = Number(f.page);
            if (!Number.isInteger(page) || page < 1 || page > env.page_count) throw new WorkflowError(`Field on invalid page ${f.page}`);
            const nums = ['x', 'y', 'w', 'h'].map((k) => Number(f[k]));
            if (nums.some((n) => !Number.isFinite(n) || n < 0 || n > 1) || nums[2] <= 0 || nums[3] <= 0) throw new WorkflowError('Field geometry out of range');
            insF.run(newId(), env.id, ids[si], f.type, page, nums[0], nums[1], Math.min(nums[2], 1 - nums[0]), Math.min(nums[3], 1 - nums[1]), f.required === false ? 0 : 1, String(f.label || '').slice(0, 60));
          }
        }
      }
      db.exec('COMMIT');
    } catch (e) { db.exec('ROLLBACK'); throw e; }

    res.json({ envelope: present(loadEnvelope(env.id)) });
  } catch (err) { next(err); }
});

envelopesRouter.post('/:id/send', async (req, res, next) => {
  try {
    const mail = await sendEnvelope(req.params.id, ctx(req));
    res.json({ envelope: present(loadEnvelope(req.params.id)), mail });
  } catch (err) { next(err); }
});

envelopesRouter.post('/:id/remind', async (req, res, next) => {
  try {
    const env = q.envelope.get(req.params.id);
    if (!env) throw new WorkflowError('Envelope not found', 404);
    if (env.status !== 'sent') throw new WorkflowError('Reminders can only be sent for envelopes that are out for signature');
    const mail = await notifyPendingSigners(env.id, { reminder: true });
    res.json({ envelope: present(loadEnvelope(env.id)), mail });
  } catch (err) { next(err); }
});

envelopesRouter.post('/:id/void', requireAdmin, (req, res, next) => {
  try {
    voidEnvelope(req.params.id, String(req.body?.reason || '').slice(0, 500));
    res.json({ envelope: present(loadEnvelope(req.params.id)) });
  } catch (err) { next(err); }
});

envelopesRouter.delete('/:id', requireAdmin, (req, res) => {
  if (!q.envelope.get(req.params.id)) return res.status(404).json({ error: 'Envelope not found' });
  deleteEnvelope(req.params.id);
  res.json({ ok: true });
});

envelopesRouter.get('/:id/pdf', (req, res) => {
  const env = q.envelope.get(req.params.id);
  if (!env) return res.status(404).json({ error: 'Envelope not found' });
  res.type('application/pdf').sendFile(originalPath(env.id));
});

envelopesRouter.get('/:id/completed', (req, res) => {
  const env = q.envelope.get(req.params.id);
  if (!env || !['completed', 'imported'].includes(env.status)) return res.status(404).json({ error: 'No completed document yet' });
  const suffix = env.status === 'imported' ? 'imported-record' : 'signed';
  const name = `${env.title.replace(/[^\w.-]+/g, '_')}-${suffix}.pdf`;
  res.type('application/pdf');
  if (req.query.download) res.attachment(name);
  res.sendFile(completedPath(env.id));
});
