import crypto from 'node:crypto';
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib';

export const sha256 = (bytes) => crypto.createHash('sha256').update(bytes).digest('hex');

const INK = rgb(0.05, 0.1, 0.35);
const GREY = rgb(0.35, 0.35, 0.35);
const BLACK = rgb(0, 0, 0);

/** Replace characters that Helvetica (WinAnsi) cannot encode. */
export function safeText(s) {
  return String(s ?? '').replace(/[\r\n\t]+/g, ' ').replace(/[^\x20-\x7E\xA0-\xFF]/g, '?');
}

export async function inspectPdf(bytes) {
  let doc;
  try {
    doc = await PDFDocument.load(bytes, { ignoreEncryption: false, updateMetadata: false });
  } catch (err) {
    if (/encrypt/i.test(String(err?.message))) {
      throw new Error('This PDF is password-protected. Remove the protection and upload it again.');
    }
    throw new Error('That file could not be read as a PDF.');
  }
  return { pageCount: doc.getPageCount() };
}

async function embedDataUrl(doc, dataUrl, cache) {
  if (cache.has(dataUrl)) return cache.get(dataUrl);
  const m = /^data:image\/(png|jpe?g);base64,(.+)$/i.exec(dataUrl || '');
  if (!m) throw new Error('Unsupported signature image format');
  const bytes = Buffer.from(m[2], 'base64');
  const img = m[1].toLowerCase() === 'png' ? await doc.embedPng(bytes) : await doc.embedJpg(bytes);
  cache.set(dataUrl, img);
  return img;
}

function drawImageContained(page, img, box, pad = 1) {
  const scale = Math.min((box.w - pad * 2) / img.width, (box.h - pad * 2) / img.height);
  const w = img.width * scale;
  const h = img.height * scale;
  page.drawImage(img, { x: box.x + (box.w - w) / 2, y: box.y + (box.h - h) / 2, width: w, height: h });
}

function drawFittedText(page, font, text, box, { color = BLACK, maxSize = 11 } = {}) {
  text = safeText(text);
  if (!text) return;
  let size = Math.min(maxSize, box.h * 0.62);
  while (size > 5 && font.widthOfTextAtSize(text, size) > box.w - 4) size -= 0.5;
  // truncate if it still does not fit
  while (text.length > 1 && font.widthOfTextAtSize(text, size) > box.w - 4) text = text.slice(0, -1);
  const y = box.y + (box.h - size * 0.72) / 2;
  page.drawText(text, { x: box.x + 2, y, size, font, color });
}

function wrapText(text, font, size, maxWidth) {
  const lines = [];
  for (const para of String(text).split('\n')) {
    let line = '';
    for (const word of para.split(/\s+/)) {
      const candidate = line ? `${line} ${word}` : word;
      if (font.widthOfTextAtSize(candidate, size) <= maxWidth) {
        line = candidate;
      } else {
        if (line) lines.push(line);
        // hard-break very long tokens
        let chunk = word;
        while (font.widthOfTextAtSize(chunk, size) > maxWidth && chunk.length > 1) {
          let cut = chunk.length;
          while (cut > 1 && font.widthOfTextAtSize(chunk.slice(0, cut), size) > maxWidth) cut--;
          lines.push(chunk.slice(0, cut));
          chunk = chunk.slice(cut);
        }
        line = chunk;
      }
    }
    lines.push(line);
  }
  return lines;
}

function makeWriter(doc, fonts) {
  const W = 612, H = 792, margin = 54;
  let page, y;
  const newPage = () => {
    page = doc.addPage([W, H]);
    y = H - margin;
  };
  newPage();
  const ensure = (need) => { if (y - need < margin) newPage(); };
  const line = (text, { size = 9.5, font = fonts.regular, color = BLACK, indent = 0 } = {}) => {
    for (const l of wrapText(safeText(text), font, size, W - margin * 2 - indent)) {
      ensure(size + 5);
      page.drawText(l, { x: margin + indent, y: y - size, size, font, color });
      y -= size + 4;
    }
  };
  const heading = (text) => { gap(6); line(text, { size: 12, font: fonts.bold, color: INK }); rule(); };
  const gap = (n = 8) => { y -= n; };
  const rule = () => { ensure(6); page.drawLine({ start: { x: margin, y: y - 2 }, end: { x: W - margin, y: y - 2 }, thickness: 0.5, color: GREY }); y -= 8; };
  const image = (img, maxW, maxH) => {
    const scale = Math.min(maxW / img.width, maxH / img.height, 1);
    const w = img.width * scale, h = img.height * scale;
    ensure(h + 6);
    page.drawImage(img, { x: margin + 12, y: y - h, width: w, height: h });
    page.drawRectangle({ x: margin + 10, y: y - h - 2, width: w + 4, height: h + 4, borderColor: GREY, borderWidth: 0.5 });
    y -= h + 10;
  };
  return { line, heading, gap, rule, image };
}

const fmt = (iso) => (iso ? new Date(iso).toUTCString().replace('GMT', 'UTC') : '—');

/**
 * Produce the final PDF: original pages with every field stamped, followed by a signature certificate.
 * @returns {Promise<Uint8Array>}
 */
export async function buildCompletedPdf({ envelope, signers, fields, events, originalBytes, appName = 'AnagraSign' }) {
  const doc = await PDFDocument.load(originalBytes, { ignoreEncryption: true });
  const fonts = {
    regular: await doc.embedFont(StandardFonts.Helvetica),
    bold: await doc.embedFont(StandardFonts.HelveticaBold),
  };
  const pages = doc.getPages();
  const signerById = new Map(signers.map((s) => [s.id, s]));
  const imgCache = new Map();

  for (const f of fields) {
    const page = pages[f.page - 1];
    if (!page) continue;
    const { width, height } = page.getSize();
    const box = { x: f.x * width, y: height - (f.y + f.h) * height, w: f.w * width, h: f.h * height };

    if (f.type === 'signature' || f.type === 'initials') {
      if (!f.value) continue;
      const img = await embedDataUrl(doc, f.value, imgCache);
      drawImageContained(page, img, box);
    } else if (f.type === 'checkbox') {
      if (f.value === '1') {
        const size = Math.min(box.w, box.h) * 0.9;
        const tw = fonts.bold.widthOfTextAtSize('X', size);
        page.drawText('X', { x: box.x + (box.w - tw) / 2, y: box.y + (box.h - size * 0.72) / 2, size, font: fonts.bold, color: BLACK });
      }
    } else {
      drawFittedText(page, fonts.regular, f.value, box);
    }
  }

  // ---- Signature certificate ----
  const w = makeWriter(doc, fonts);
  w.line('Signature Certificate', { size: 18, font: fonts.bold, color: INK });
  w.line(`Generated by ${appName}`, { size: 9, color: GREY });
  w.gap(4);
  w.line(`Document: ${envelope.title}`, { font: fonts.bold });
  w.line(`Envelope ID: ${envelope.id}`);
  w.line(`Original file: ${envelope.original_name}  (${envelope.page_count} page${envelope.page_count === 1 ? '' : 's'})`);
  w.line(`Original document SHA-256: ${envelope.original_sha256}`);
  w.line(`Signing order: ${envelope.signing_order}`);
  w.line(`Signer verification: ${{ email_code: 'one-time code emailed to each signer', access_code: 'access code provided by sender' }[envelope.auth_method] || 'none (unique email link)'}`);
  w.line(`Sent: ${fmt(envelope.sent_at)}`);
  w.line(`Completed: ${fmt(envelope.completed_at || new Date().toISOString())}`);

  w.heading('Signers');
  for (const s of signers) {
    w.line(`${s.name}  <${s.email}>`, { font: fonts.bold });
    w.line(`Status: ${s.status}`, { indent: 12 });
    w.line(`Identity verification: ${s.verified_method === 'email_code' ? `one-time code sent to ${s.email}, verified ${fmt(s.verified_at)}` : s.verified_method === 'access_code' ? `sender-provided access code, verified ${fmt(s.verified_at)}` : 'email link only (no additional verification)'}`, { indent: 12 });
    w.line(`Consented to electronic signature: ${fmt(s.consent_at)}`, { indent: 12 });
    w.line(`First viewed: ${fmt(s.viewed_at)}`, { indent: 12 });
    w.line(`Signed: ${fmt(s.signed_at)}`, { indent: 12 });
    w.line(`IP address: ${s.ip || '—'}`, { indent: 12 });
    w.line(`Browser: ${s.user_agent || '—'}`, { indent: 12, size: 8, color: GREY });
    const sig = fields.find((f) => f.signer_id === s.id && f.type === 'signature' && f.value);
    if (sig) {
      w.line(`Signature ID: ${sig.id}`, { indent: 12, size: 8, color: GREY });
      try { w.image(await embedDataUrl(doc, sig.value, imgCache), 160, 50); } catch { /* ignore bad image */ }
    }
    w.gap(4);
  }

  w.heading('Fields');
  for (const f of fields) {
    const s = signerById.get(f.signer_id);
    const val = f.type === 'signature' || f.type === 'initials' ? (f.value ? '[image]' : '[empty]') : f.type === 'checkbox' ? (f.value === '1' ? 'checked' : 'unchecked') : (f.value || '[empty]');
    w.line(`p.${f.page}  ${f.type.padEnd(9)}  ${s ? s.name : '?'}  ->  ${val}`, { size: 8.5 });
  }

  w.heading('Audit trail (all times UTC)');
  for (const e of events) {
    const who = e.signer_id ? (signerById.get(e.signer_id)?.name || 'signer') : 'system';
    w.line(`${fmt(e.created_at)}  |  ${e.type}  |  ${who}${e.ip ? `  |  ${e.ip}` : ''}`, { size: 8.5 });
    if (e.detail) w.line(e.detail, { size: 8, color: GREY, indent: 14 });
  }
  w.gap(10);
  w.rule();
  w.line('This certificate documents the electronic signature process for the attached document. The SHA-256 fingerprint of the completed file is recorded by the sending system; any change to the file will change its fingerprint.', { size: 8, color: GREY });

  return doc.save({ useObjectStreams: false });
}

/**
 * Append an "Import Record" page to an already-signed PDF that was uploaded for record-keeping
 * only. Unlike buildCompletedPdf, this does NOT stamp anything onto the original pages and does
 * NOT claim to certify a signing process AnagraSign never witnessed — it plainly documents what
 * the importer told us (source, claimed signer(s)/date, notes) plus the fingerprint of the file
 * exactly as it was received, with an explicit disclaimer.
 * @returns {Promise<Uint8Array>}
 */
export async function buildImportRecordPdf({ envelope, originalBytes, originalSha256, appName = 'AnagraSign' }) {
  const doc = await PDFDocument.load(originalBytes, { ignoreEncryption: true });
  const fonts = {
    regular: await doc.embedFont(StandardFonts.Helvetica),
    bold: await doc.embedFont(StandardFonts.HelveticaBold),
  };
  const w = makeWriter(doc, fonts);

  w.line('Import Record', { size: 18, font: fonts.bold, color: INK });
  w.line(`Added by ${appName} for record-keeping — not a signature certificate`, { size: 9, color: GREY });
  w.gap(4);
  w.line(`Document: ${envelope.title}`, { font: fonts.bold });
  w.line(`Original file name: ${envelope.original_name}`);
  w.line(`Imported: ${fmt(envelope.created_at)}`);
  w.line(`SHA-256 of the file as uploaded (before this page was added): ${originalSha256}`);

  w.heading('What the importer told us');
  w.line(`Originally signed via: ${envelope.imported_source || '(not specified)'}`);
  w.line(`Signed on (as reported): ${envelope.imported_signed_date || '(not specified)'}`);
  if (envelope.imported_note) { w.line('Notes:'); w.line(envelope.imported_note, { indent: 12 }); }

  w.gap(10);
  w.rule();
  w.line(
    'This page was added when the attached document was imported into AnagraSign purely for storage and record-keeping. '
    + 'AnagraSign did not witness, verify, or take part in the original signing process, and makes no representation as to the '
    + 'authenticity of the attached pages or the accuracy of the information above — it reflects only what the person importing '
    + 'this file entered at the time. The SHA-256 fingerprint above lets you confirm the imported file has not been altered since '
    + 'upload; it does not verify anything about how it was signed originally.',
    { size: 8, color: GREY },
  );

  return doc.save({ useObjectStreams: false });
}
