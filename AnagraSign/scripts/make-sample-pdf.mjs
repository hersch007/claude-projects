// Generates docs/sample-agreement.pdf, a two-page contract you can use to test the app.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const out = path.join(root, 'docs', 'sample-agreement.pdf');

const doc = await PDFDocument.create();
const font = await doc.embedFont(StandardFonts.Helvetica);
const bold = await doc.embedFont(StandardFonts.HelveticaBold);

const para = (page, text, y, size = 11) => {
  const words = text.split(' ');
  let line = '';
  for (const w of words) {
    const t = line ? `${line} ${w}` : w;
    if (font.widthOfTextAtSize(t, size) > 500) { page.drawText(line, { x: 56, y, size, font }); y -= size + 4; line = w; }
    else line = t;
  }
  page.drawText(line, { x: 56, y, size, font });
  return y - size - 12;
};

const p1 = doc.addPage([612, 792]);
p1.drawText('SERVICE AGREEMENT', { x: 56, y: 730, size: 20, font: bold, color: rgb(0.06, 0.16, 0.35) });
p1.drawText('Sample document for testing AnagraSign', { x: 56, y: 710, size: 10, font, color: rgb(0.4, 0.4, 0.4) });
let y = 670;
y = para(p1, 'This Service Agreement (the "Agreement") is entered into between Start Performance ("Provider") and the client identified below ("Client"). By signing, both parties agree to the terms set out in this document.', y);
y = para(p1, '1. Services. Provider will deliver the marketing, web and performance services described in the attached statement of work.', y);
y = para(p1, '2. Term. This Agreement begins on the date of the last signature below and continues month to month until cancelled by either party with 30 days written notice.', y);
y = para(p1, '3. Fees. Client agrees to pay the fees listed in the statement of work. Invoices are due within 15 days of receipt.', y);
y = para(p1, '4. Confidentiality. Each party will keep the other party\'s non-public information confidential and use it only to perform this Agreement.', y);
y = para(p1, '5. Limitation of Liability. Neither party is liable for indirect or consequential damages. Provider\'s total liability is limited to fees paid in the prior three months.', y);
p1.drawText('Client initials: ________', { x: 56, y: 90, size: 11, font });
p1.drawText('Page 1 of 2', { x: 520, y: 40, size: 9, font, color: rgb(0.5, 0.5, 0.5) });

const p2 = doc.addPage([612, 792]);
p2.drawText('SIGNATURES', { x: 56, y: 730, size: 16, font: bold, color: rgb(0.06, 0.16, 0.35) });
y = 700;
y = para(p2, 'IN WITNESS WHEREOF, the parties have executed this Agreement by their duly authorized representatives.', y);
const block = (label, top) => {
  p2.drawText(label, { x: 56, y: top, size: 12, font: bold });
  p2.drawText('Signature:', { x: 56, y: top - 40, size: 10, font });
  p2.drawLine({ start: { x: 130, y: top - 42 }, end: { x: 360, y: top - 42 }, thickness: 0.7 });
  p2.drawText('Name:', { x: 56, y: top - 70, size: 10, font });
  p2.drawLine({ start: { x: 130, y: top - 72 }, end: { x: 360, y: top - 72 }, thickness: 0.7 });
  p2.drawText('Date:', { x: 390, y: top - 40, size: 10, font });
  p2.drawLine({ start: { x: 425, y: top - 42 }, end: { x: 556, y: top - 42 }, thickness: 0.7 });
};
block('CLIENT', 620);
block('PROVIDER', 470);
p2.drawText('[ ]  I have read and agree to the confidentiality terms in Section 4.', { x: 56, y: 330, size: 10, font });
p2.drawText('Page 2 of 2', { x: 520, y: 40, size: 9, font, color: rgb(0.5, 0.5, 0.5) });

fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, await doc.save());
console.log(`Wrote ${path.relative(root, out)}`);
