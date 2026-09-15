// Generates the Master report as a Word document — the mechanical/technical
// content the app can honestly produce from crawl data (score, findings,
// quick wins, page table), matching what the HTML report already shows.
// Deliberately NOT the fuller narrative report the `new-seo-client` skill
// writes (E-E-A-T analysis, local SEO, content strategy) — those sections
// are authored by Claude reasoning about the business, not derived
// mechanically from a crawl, so reproducing them here would need LLM
// integration that doesn't exist yet. See PHASE1-DESIGN.md §8.
//
// Generated fresh from the in-memory crawl `results` right after a run
// completes (same lifecycle as the HTML report in webapp/server's `runs`
// Map) rather than reconstructed later from the database — Phase 1
// deliberately doesn't persist page-level results long-term (see
// audit-engine.js's runAudit doc comment / db-storage.js), so a Word export
// of an arbitrary past run isn't available, only of a run just completed.
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle,
} = require('docx');
const { getQuickWins, friendlyIssue } = require('./audit-engine');

const CELL_MARGIN = { top: 80, bottom: 80, left: 120, right: 120 };
const THIN_BORDER = { style: BorderStyle.SINGLE, size: 2, color: 'CCCCCC' };
const CELL_BORDERS = { top: THIN_BORDER, bottom: THIN_BORDER, left: THIN_BORDER, right: THIN_BORDER };

function headerCell(text, brandHex) {
  return new TableCell({
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: 'FFFFFF', size: 18 })] })],
    shading: { type: ShadingType.CLEAR, fill: brandHex },
    margins: CELL_MARGIN,
    borders: CELL_BORDERS,
  });
}

function bodyCell(text, shaded) {
  return new TableCell({
    children: [new Paragraph({ children: [new TextRun({ text: String(text ?? ''), size: 18 })] })],
    shading: shaded ? { type: ShadingType.CLEAR, fill: 'F2F2F2' } : undefined,
    margins: CELL_MARGIN,
    borders: CELL_BORDERS,
  });
}

function buildDocxReport({ client, results, scoreData, provider, date }) {
  const pages = results.filter(r => !r.error);
  const brandHex = (provider.brand || '#003366').replace('#', '');
  const brand2Hex = (provider.brand2 || provider.brand || '#003366').replace('#', '');
  const dateLabel = new Date(date + 'T12:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const scoreLabel = scoreData.score >= 85 ? 'Good' : scoreData.score >= 70 ? 'Needs Improvement' : 'Needs Attention';

  const children = [];

  // ── Cover ──
  children.push(
    new Paragraph({ spacing: { before: 1600 }, children: [] }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: client.name, bold: true, size: 56, color: brandHex })],
      spacing: { after: 120 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'SEO Audit Report', size: 32, color: '595959' })],
      spacing: { after: 400 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `SEO Health Score: ${scoreData.score} / 100 — ${scoreLabel}`, bold: true, size: 28, color: brandHex })],
      spacing: { after: 600 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `${client.url}`, size: 22 })],
      spacing: { after: 100 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `Audit Date: ${dateLabel}  |  Prepared by: ${provider.name}`, size: 20, color: '595959' })],
      pageBreakAfter: true,
    }),
  );

  // ── Section: Score summary ──
  children.push(
    new Paragraph({ text: 'Overall SEO Health Score', heading: HeadingLevel.HEADING_1, spacing: { after: 160 } }),
    new Paragraph({ children: [new TextRun({ text: `${scoreData.score} / 100 — ${scoreLabel}`, bold: true, size: 24 })], spacing: { after: 200 } }),
  );
  if (scoreData.deductions.length) {
    children.push(new Paragraph({ children: [new TextRun({ text: 'Score deductions:', bold: true })], spacing: { after: 100 } }));
    for (const d of scoreData.deductions) {
      children.push(new Paragraph({ text: `-${d.pts}  ${d.label}`, bullet: { level: 0 } }));
    }
  }

  // ── Section: Quick Wins ──
  const wins = getQuickWins(pages);
  if (wins.length) {
    children.push(new Paragraph({ text: 'Quick Wins & Recommendations', heading: HeadingLevel.HEADING_1, spacing: { before: 400, after: 160 } }));
    for (const w of wins) {
      children.push(
        new Paragraph({ children: [new TextRun({ text: `${w.action} `, bold: true }), new TextRun({ text: `(${w.effort} effort, ${w.impact} impact)`, italics: true, color: '595959' })], spacing: { before: 100 } }),
        new Paragraph({ children: [new TextRun({ text: w.detail, size: 20, color: '444444' })], spacing: { after: 100 } }),
      );
    }
  }

  // ── Section: Findings by Page ──
  const issuePages = pages
    .filter(p => p.issues.length > 0 || p.warnings.length > 0)
    .sort((a, b) => (b.issues.length * 10 + b.warnings.length) - (a.issues.length * 10 + a.warnings.length));
  if (issuePages.length) {
    children.push(new Paragraph({ text: `Findings by Page (${issuePages.length} pages need attention)`, heading: HeadingLevel.HEADING_1, spacing: { before: 400, after: 160 } }));
    for (const p of issuePages) {
      const slug = p.url.replace(client.url.replace(/\/$/, ''), '') || '/';
      children.push(new Paragraph({ children: [new TextRun({ text: slug, bold: true, font: 'Courier New', size: 20 })], spacing: { before: 200, after: 60 } }));
      const items = [...p.issues.map(i => friendlyIssue(i)), ...p.warnings.map(w => friendlyIssue(w))];
      for (const item of items) {
        children.push(new Paragraph({ text: `[${item.priority.toUpperCase()}] ${item.label}`, bullet: { level: 0 } }));
      }
    }
  }

  // ── Section: All Pages table ──
  children.push(new Paragraph({ text: 'All Pages at a Glance', heading: HeadingLevel.HEADING_1, spacing: { before: 400, after: 160 } }));
  const tableRows = [
    new TableRow({
      children: [headerCell('Page URL', brandHex), headerCell('Title', brandHex), headerCell('H1', brandHex), headerCell('Meta Desc', brandHex), headerCell('Schema', brandHex), headerCell('Image Alts', brandHex)],
      tableHeader: true,
    }),
    ...pages.map((p, i) => new TableRow({
      children: [
        bodyCell(p.url.replace(client.url.replace(/\/$/, ''), '') || '/', i % 2 === 1),
        bodyCell(p.title || 'Missing', i % 2 === 1),
        bodyCell(p.h1Count === 0 ? 'None' : p.h1Count > 1 ? `${p.h1Count} (multiple)` : 'OK', i % 2 === 1),
        bodyCell(p.metaDesc ? 'OK' : 'Missing', i % 2 === 1),
        bodyCell(p.schemaTypes.length ? p.schemaTypes.join(', ') : 'None', i % 2 === 1),
        bodyCell(p.imagesNoAlt > 0 ? String(p.imagesNoAlt) : 'OK', i % 2 === 1),
      ],
    })),
  ];
  children.push(new Table({ rows: tableRows, width: { size: 100, type: WidthType.PERCENTAGE } }));

  // ── Footer note ──
  children.push(
    new Paragraph({
      spacing: { before: 600 },
      children: [new TextRun({ text: `Prepared by ${provider.name} — ${client.name} SEO Audit — ${dateLabel}. Questions? Contact ${provider.email}`, size: 18, color: '94A3B8', italics: true })],
    }),
  );

  const doc = new Document({
    sections: [{ properties: {}, children }],
  });

  return Packer.toBuffer(doc);
}

module.exports = { buildDocxReport };
