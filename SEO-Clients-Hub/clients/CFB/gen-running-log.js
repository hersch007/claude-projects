// CFB On-Page SEO Audit Running Log generator
// Data lives in pages-data.js. Run:
//   NODE_PATH="C:/Users/richa/AppData/Roaming/npm/node_modules" node gen-running-log.js
const docx = require('docx');
const fs = require('fs');
const data = require('./pages-data.js');
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, BorderStyle, ShadingType, LevelFormat,
  Header, Footer, PageNumber, TabStopType, TabStopPosition, convertInchesToTwip
} = docx;

const PRIMARY = '003366';
const ACCENT = 'C9A84C';
const HEADER_TINT = 'D9E0E8';
const GRAY = '595959';
const LIGHT = 'F2F2F2';
const GREEN = '2E7D32';
const FONT = 'Arial';
const BORDER = { style: BorderStyle.SINGLE, size: 4, color: 'CCCCCC' };
const CELL_BORDERS = { top: BORDER, bottom: BORDER, left: BORDER, right: BORDER };
const CELL_MARGIN = { top: 80, bottom: 80, left: 120, right: 120 };

function run(text, opts = {}) { return new TextRun({ text, font: FONT, size: 20, ...opts }); }
function para(children, opts = {}) {
  return new Paragraph({ children: Array.isArray(children) ? children : [run(children)], spacing: { after: 120 }, ...opts });
}
function h1(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 400, after: 160 },
    children: [new TextRun({ text, font: FONT, size: 30, bold: true, color: PRIMARY })]
  });
}
function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 240, after: 120 },
    children: [new TextRun({ text, font: FONT, size: 24, bold: true, color: ACCENT })]
  });
}
function bullet(text) {
  return new Paragraph({
    numbering: { reference: 'bullets', level: 0 },
    spacing: { after: 80 },
    children: [run(text)]
  });
}
function altTable(rows, firstColHeader) {
  const headers = [firstColHeader, 'Current Alt', 'Suggested Alt Text', 'Chars', 'Rationale & SEO Benefit'];
  const colW = [2300, 1700, 2600, 600, 2600];
  const headerRow = new TableRow({
    tableHeader: true,
    children: headers.map((t, i) => new TableCell({
      children: [new Paragraph({ children: [run(t, { bold: true, color: PRIMARY })] })],
      borders: CELL_BORDERS, margins: CELL_MARGIN,
      shading: { type: ShadingType.CLEAR, fill: HEADER_TINT },
      width: { size: colW[i], type: WidthType.DXA }
    }))
  });
  const bodyRows = rows.map((r, ri) => new TableRow({
    children: r.map((c, ci) => new TableCell({
      children: [new Paragraph({ children: [run(c)] })],
      borders: CELL_BORDERS, margins: CELL_MARGIN,
      shading: ri % 2 === 1 ? { type: ShadingType.CLEAR, fill: LIGHT } : undefined,
      width: { size: colW[ci], type: WidthType.DXA }
    }))
  }));
  return new Table({ rows: [headerRow, ...bodyRows], columnWidths: colW, width: { size: 9800, type: WidthType.DXA } });
}
function codeBlock(text) {
  return text.split('\n').map(line => new Paragraph({
    shading: { type: ShadingType.CLEAR, fill: 'F5F5F5' },
    indent: { left: 360 },
    spacing: { after: 0 },
    children: [new TextRun({ text: line || ' ', font: 'Courier New', size: 16 })]
  }));
}
function labelLine(label, value, valueOpts = {}) {
  return para([run(label, { bold: true }), run(value, valueOpts)]);
}

const body = [];

// ---------- TITLE BLOCK ----------
body.push(new Paragraph({ children: [new TextRun({ text: data.client, font: FONT, size: 52, bold: true, color: PRIMARY })], spacing: { before: 160, after: 60 } }));
body.push(new Paragraph({ children: [new TextRun({ text: data.docTitle, font: FONT, size: 36, bold: true, color: '333333' })], spacing: { after: 60 } }));
body.push(new Paragraph({ children: [new TextRun({ text: data.docSubtitle, font: FONT, size: 24, italics: true, color: GRAY })], spacing: { after: 80 } }));
body.push(new Paragraph({
  children: [new TextRun({ text: `Last updated: ${data.lastUpdated}   •   Pages audited: ${data.pages.length} of ${data.totalPages}`, font: FONT, size: 22, color: ACCENT, bold: true })],
  spacing: { after: 160 },
  border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: PRIMARY } }
}));

// ---------- GUIDELINES ----------
body.push(h2('Standard HubSpot Image SEO Guidelines'));
data.guidelines.forEach(g => body.push(bullet(g)));

// ---------- GLOBAL ELEMENTS ----------
body.push(h2('Global / Sitewide Elements (apply once)'));
body.push(para('These elements repeat on every page (logos, vendor seal). Set their alt text once and apply sitewide — they are not repeated in the per-page tables below.'));
body.push(altTable(data.globalElements, 'Sitewide Element'));
body.push(new Paragraph({ children: [], spacing: { after: 160 } }));

// ---------- PER-PAGE ENTRIES ----------
for (const p of data.pages) {
  body.push(h1(p.name));
  const done = /^IMPLEMENTED/.test(p.status);
  body.push(para([run(p.status, { bold: true, color: done ? GREEN : 'B26B00' })]));
  body.push(labelLine('Live URL: ', p.liveUrl, { color: '0563C1' }));
  body.push(labelLine('HubSpot editor: ', p.editorUrl, { color: '0563C1' }));
  body.push(para([run('Audited: ', { bold: true }), run(p.audited + '      '), run('Primary keyword: ', { bold: true }), run(p.primaryKw)]));
  body.push(labelLine('Secondary keywords: ', p.secondaryKw));
  body.push(labelLine(`Recommended Page Title (${p.title.chars} chars)`, ''));
  body.push(para([run(p.title.text, { size: 22, color: PRIMARY, bold: true })]));
  body.push(labelLine(`Recommended Meta Description (${p.meta.chars} chars)`, ''));
  body.push(para([run(p.meta.text, { size: 22, color: '333333' })]));
  if (p.author) {
    body.push(para([run('Byline: ', { bold: true }), run(`${p.author}${p.datePublished ? '  •  Published: ' + p.datePublished : ''}`)]));
  }
  if (p.notes) {
    body.push(para([run('Notes: ', { bold: true }), run(p.notes)]));
  }
  if (p.images && p.images.length) {
    body.push(para([run('Image + Alt Text Audit (page-specific content images)', { bold: true })]));
    body.push(altTable(p.images, 'Image & Context'));
    body.push(new Paragraph({ children: [], spacing: { after: 120 } }));
  } else if (p.featuredImageNote) {
    body.push(para([run('Featured image alt: ', { bold: true }), run(p.featuredImageNote)]));
  }
  body.push(para([run(p.schemaLabel, { bold: true })]));
  codeBlock(p.schema).forEach(c => body.push(c));
  body.push(new Paragraph({ children: [], spacing: { after: 160 } }));
}

// ---------- DOC ----------
const doc = new Document({
  numbering: {
    config: [{
      reference: 'bullets',
      levels: [{ level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 620, hanging: 320 } } } }]
    }]
  },
  styles: { default: { document: { run: { font: FONT, size: 20 } } } },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: convertInchesToTwip(1), bottom: convertInchesToTwip(1), left: convertInchesToTwip(1), right: convertInchesToTwip(1) }
      }
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          alignment: AlignmentType.RIGHT,
          children: [new TextRun({ text: `${data.client} — On-Page SEO Audit Running Log | Start Advertising`, font: FONT, size: 16, color: GRAY })]
        })]
      })
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          tabStops: [{ type: TabStopType.RIGHT, position: TabStopPosition.MAX }],
          children: [
            new TextRun({ text: `Confidential — Prepared Exclusively for ${data.client}`, font: FONT, size: 16, color: GRAY }),
            new TextRun({ text: '\t', font: FONT, size: 16 }),
            new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 16, color: GRAY })
          ]
        })]
      })
    },
    children: body
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync('CFB-On-Page-SEO-Audit-Running-Log.docx', buf);
  console.log(`Running log written: ${buf.length} bytes — ${data.pages.length} of ${data.totalPages} pages audited`);
});
