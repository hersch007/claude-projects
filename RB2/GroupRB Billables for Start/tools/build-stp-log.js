#!/usr/bin/env node
/*
 * build-stp-log.js — Regenerate "Start Performance Time Log.xlsx" (IRS audit
 * trail) from the append-only ledger logs/stp-sessions.json.
 *
 * Ledger = array of merged task sessions:
 *   { date:"2026-07-04", start:"2:15p", end:"5:30p", hours:3.25,
 *     task:"SP Module Build out - Dashboard", job:"", billed:false }
 *
 * Output: a flat, timestamped, sortable log (Date | Start | End | Hours | Task |
 * Job # | Billed?) with billed / unbilled / grand-total summaries and per-month
 * subtotals. Zero-dependency (writes the .xlsx zip directly).
 *
 * Usage: node tools/build-stp-log.js
 */
'use strict';
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const ledgerPath = path.join(root, 'logs', 'stp-sessions.json');
const sessions = JSON.parse(fs.readFileSync(ledgerPath, 'utf8'));

const round2 = n => Math.round(n * 100) / 100;
const toMin = t => {
  const m = /^(\d{1,2}):(\d{2})\s*([ap])/i.exec(String(t).trim());
  if (!m) return 0;
  let h = +m[1] % 12; if (/p/i.test(m[3])) h += 12;
  return h * 60 + (+m[2]);
};
const monthKey = d => d.slice(0, 7);
const monthName = k => new Date(k + '-01T00:00:00').toLocaleString('en-US', { month: 'long', year: 'numeric' });

sessions.sort((a, b) => a.date.localeCompare(b.date) || toMin(a.start) - toMin(b.start));

// ----------------------------------------------------------------------------
// Worksheet rows
// ----------------------------------------------------------------------------
const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const cells = [];
let rIdx = 0;
const colLetter = i => String.fromCharCode(65 + i);
// styles: 0 default | 1 title | 2 sub | 3 header | 4 txt | 5 txtWrap | 6 num | 7 center | 8 monthLbl | 9 monthNum | 10 totLbl | 11 totNum
function row(defs) {
  rIdx++;
  const cs = defs.map((c, i) => {
    if (c == null) return '';
    const ref = colLetter(i) + rIdx;
    if (c.n !== undefined) return `<c r="${ref}" s="${c.s || 0}"><v>${c.n}</v></c>`;
    return `<c r="${ref}" s="${c.s || 0}" t="inlineStr"><is><t xml:space="preserve">${esc(c.t)}</t></is></c>`;
  }).join('');
  cells.push(`<row r="${rIdx}">${cs}</row>`);
}
const fmtDate = d => { const [y, m, dd] = d.split('-'); return `${m}/${dd}/${y}`; };

row([{ t: 'Start Performance — Time Log', s: 1 }]);
row([{ t: 'Richard Brashear · billed + unbilled · contemporaneous record for tax/audit', s: 2 }]);
row([]);
row([{ t: 'Date', s: 3 }, { t: 'Start', s: 3 }, { t: 'End', s: 3 }, { t: 'Hours', s: 3 }, { t: 'Task / Business Purpose', s: 3 }, { t: 'Job #', s: 3 }, { t: 'Billed?', s: 3 }]);

let grandBilled = 0, grandUnbilled = 0;
const months = [...new Set(sessions.map(s => monthKey(s.date)))].sort();
for (const mk of months) {
  const ms = sessions.filter(s => monthKey(s.date) === mk);
  let mBilled = 0, mUnbilled = 0;
  for (const s of ms) {
    row([
      { t: fmtDate(s.date), s: 4 },
      { t: s.start, s: 7 },
      { t: s.end, s: 7 },
      { n: round2(s.hours), s: 6 },
      { t: s.task, s: 5 },
      { t: s.job || '—', s: 7 },
      { t: s.billed ? 'Billed' : 'Unbilled', s: 7 },
    ]);
    if (s.billed) mBilled += s.hours; else mUnbilled += s.hours;
  }
  row([{ t: monthName(mk) + ' — subtotal', s: 8 }, null, null, { n: round2(mBilled + mUnbilled), s: 9 }, { t: `Billed ${round2(mBilled)} · Unbilled ${round2(mUnbilled)}`, s: 8 }, null, null]);
  grandBilled += mBilled; grandUnbilled += mUnbilled;
}
row([]);
row([{ t: 'TOTAL — Billed', s: 10 }, null, null, { n: round2(grandBilled), s: 11 }, null, null, null]);
row([{ t: 'TOTAL — Unbilled', s: 10 }, null, null, { n: round2(grandUnbilled), s: 11 }, null, null, null]);
row([{ t: 'TOTAL — All Start Performance', s: 10 }, null, null, { n: round2(grandBilled + grandUnbilled), s: 11 }, null, null, null]);

const sheetXml =
  `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
  `<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">` +
  `<cols>` +
  `<col min="1" max="1" width="12" customWidth="1"/><col min="2" max="3" width="8" customWidth="1"/>` +
  `<col min="4" max="4" width="9" customWidth="1"/><col min="5" max="5" width="46" customWidth="1"/>` +
  `<col min="6" max="6" width="12" customWidth="1"/><col min="7" max="7" width="11" customWidth="1"/>` +
  `</cols>` +
  `<sheetData>${cells.join('')}</sheetData></worksheet>`;

// ----------------------------------------------------------------------------
// Package parts (styles mirror make-daily)
// ----------------------------------------------------------------------------
const stylesXml =
  `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
  `<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">` +
  `<fonts count="4">` +
  `<font><sz val="11"/><name val="Calibri"/></font>` +
  `<font><b/><sz val="14"/><name val="Calibri"/></font>` +
  `<font><b/><sz val="11"/><name val="Calibri"/></font>` +
  `<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>` +
  `</fonts>` +
  `<fills count="4">` +
  `<fill><patternFill patternType="none"/></fill>` +
  `<fill><patternFill patternType="gray125"/></fill>` +
  `<fill><patternFill patternType="solid"><fgColor rgb="FF305496"/></patternFill></fill>` +
  `<fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/></patternFill></fill>` +
  `</fills>` +
  `<borders count="2">` +
  `<border><left/><right/><top/><bottom/><diagonal/></border>` +
  `<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>` +
  `</borders>` +
  `<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>` +
  `<cellXfs count="12">` +
  `<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>` +
  `<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>` +
  `<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>` +
  `<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>` +
  `<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>` +
  `<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment wrapText="1"/></xf>` +
  `<xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>` +
  `<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>` +
  `<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>` +
  `<xf numFmtId="2" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>` +
  `<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>` +
  `<xf numFmtId="2" fontId="3" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>` +
  `</cellXfs>` +
  `<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>` +
  `</styleSheet>`;

const contentTypes =
  `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
  `<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">` +
  `<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>` +
  `<Default Extension="xml" ContentType="application/xml"/>` +
  `<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>` +
  `<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>` +
  `<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>` +
  `</Types>`;
const rootRels =
  `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
  `<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">` +
  `<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>` +
  `</Relationships>`;
const workbookXml =
  `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
  `<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">` +
  `<sheets><sheet name="Time Log" sheetId="1" r:id="rId1"/></sheets></workbook>`;
const workbookRels =
  `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
  `<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">` +
  `<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>` +
  `<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>` +
  `</Relationships>`;

const CRC = (() => { const t = []; for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; } return t; })();
function crc32(buf) { let c = 0xFFFFFFFF; for (let i = 0; i < buf.length; i++) c = CRC[(c ^ buf[i]) & 0xFF] ^ (c >>> 8); return (c ^ 0xFFFFFFFF) >>> 0; }
function zip(files) {
  const locals = [], centrals = []; let offset = 0;
  for (const f of files) {
    const name = Buffer.from(f.name, 'utf8'); const body = Buffer.from(f.data, 'utf8'); const crc = crc32(body);
    const lh = Buffer.alloc(30);
    lh.writeUInt32LE(0x04034b50, 0); lh.writeUInt16LE(20, 4); lh.writeUInt32LE(crc, 14); lh.writeUInt32LE(body.length, 18); lh.writeUInt32LE(body.length, 22); lh.writeUInt16LE(name.length, 26);
    locals.push(lh, name, body);
    const ch = Buffer.alloc(46);
    ch.writeUInt32LE(0x02014b50, 0); ch.writeUInt16LE(20, 4); ch.writeUInt16LE(20, 6); ch.writeUInt32LE(crc, 16); ch.writeUInt32LE(body.length, 20); ch.writeUInt32LE(body.length, 24); ch.writeUInt16LE(name.length, 28); ch.writeUInt32LE(offset, 42);
    centrals.push(ch, name); offset += lh.length + name.length + body.length;
  }
  const cd = Buffer.concat(centrals); const cdOffset = offset;
  const end = Buffer.alloc(22);
  end.writeUInt32LE(0x06054b50, 0); end.writeUInt16LE(files.length, 8); end.writeUInt16LE(files.length, 10); end.writeUInt32LE(cd.length, 12); end.writeUInt32LE(cdOffset, 16);
  return Buffer.concat([...locals, cd, end]);
}
const buf = zip([
  { name: '[Content_Types].xml', data: contentTypes },
  { name: '_rels/.rels', data: rootRels },
  { name: 'xl/workbook.xml', data: workbookXml },
  { name: 'xl/_rels/workbook.xml.rels', data: workbookRels },
  { name: 'xl/styles.xml', data: stylesXml },
  { name: 'xl/worksheets/sheet1.xml', data: sheetXml },
]);
const outPath = path.join(root, 'Start Performance Time Log.xlsx');
fs.writeFileSync(outPath, buf);
console.log(`Wrote ${outPath}`);
console.log(`  Sessions: ${sessions.length} | Billed: ${round2(grandBilled)} hr | Unbilled: ${round2(grandUnbilled)} hr | Total: ${round2(grandBilled + grandUnbilled)} hr`);
