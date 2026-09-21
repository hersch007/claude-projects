// Generates the "Customer Audit Report" — a Word document meant to be
// shown to a prospect to make the case that they need help, not a teaser
// that withholds detail. It shows the score, how far that score is from
// "Good," and the full findings number-grid — the same stat tiles as the
// on-screen quick report (see audit-engine.js's buildReport(), the
// `.snap-card`/`.snap-num`/`.snap-label` markup) — so the sheer number of
// red/amber tiles makes the case on its own.
//
// Deliberately built from only what a stored audit_runs row actually has
// (seo_health_score, pages_crawled, html_report — see webapp/server/
// index.js's GET .../audit-run/:id/sales-report route), so it can be
// regenerated for any past run, not just a crawl that just completed in
// memory (see build-docx-report.js's own doc comment for why the Master
// report can't do this). The stat grid itself isn't stored as structured
// data anywhere — it's parsed back out of the stored html_report, the one
// place every one of those counts already lives for a saved run.
const {
  Document, Packer, Paragraph, TextRun, AlignmentType, Footer,
  Table, TableRow, TableCell, WidthType, BorderStyle,
} = require('docx');
const cheerio = require('cheerio');
const {
  scoreTierColor, scoreBarTable, coverLogoImageRun, BODY_FONT, HEADING_FONT,
} = require('./build-docx-report');

const GOOD_THRESHOLD = 85; // matches audit-engine.js's own scoreLabel tiering

const STATUS_COLOR = { good: '16A34A', warn: 'D97706', bad: 'DC2626' };

// Pulls the exact same stat tiles a client already sees on the on-screen
// quick report back out of that report's stored HTML — `.snap-card` wraps
// a `.snap-num` (the count, classed good/warn/bad/neutral) and a
// `.snap-label` (its name). See audit-engine.js's buildReport() for the
// markup this depends on; if that ever changes, this needs to move with
// it (there's no other source for these per-category counts on a past
// run — see this file's top doc comment).
function parseStatGrid(html) {
  const $ = cheerio.load(html || '');
  const grid = [];
  $('.snap-card').each((i, el) => {
    const numEl = $(el).find('.snap-num').first();
    const value = numEl.text().trim();
    const label = $(el).find('.snap-label').first().text().trim();
    if (!label) return;
    const classes = (numEl.attr('class') || '').split(/\s+/);
    const status = classes.find(c => c !== 'snap-num') || 'neutral';
    grid.push({ label, value, status });
  });
  return grid;
}

const GRID_COLUMNS = 3;
function statGridTable(grid, brandHex) {
  const cell = (item) => new TableCell({
    width: { size: 100 / GRID_COLUMNS, type: WidthType.PERCENTAGE },
    borders: { top: { style: BorderStyle.SINGLE, size: 2, color: 'E2E8F0' }, bottom: { style: BorderStyle.SINGLE, size: 2, color: 'E2E8F0' }, left: { style: BorderStyle.SINGLE, size: 2, color: 'E2E8F0' }, right: { style: BorderStyle.SINGLE, size: 2, color: 'E2E8F0' } },
    margins: { top: 100, bottom: 100, left: 80, right: 80 },
    children: item ? [
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 30 },
        children: [new TextRun({ text: item.value, bold: true, size: 26, color: STATUS_COLOR[item.status] || brandHex, font: HEADING_FONT })],
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: item.label, size: 15, color: '64748B' })],
      }),
    ] : [new Paragraph({ children: [] })],
  });

  const rows = [];
  for (let i = 0; i < grid.length; i += GRID_COLUMNS) {
    const rowItems = grid.slice(i, i + GRID_COLUMNS);
    while (rowItems.length < GRID_COLUMNS) rowItems.push(null);
    rows.push(new TableRow({ children: rowItems.map(cell) }));
  }
  return new Table({ width: { size: 100, type: WidthType.PERCENTAGE }, rows });
}

function buildSalesReport({ client, provider, score, pagesCrawled, htmlReport, date }) {
  const brandHex = (provider.brand || '#003366').replace('#', '');
  const dateLabel = new Date(date + 'T12:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const scoreLabel = score >= 85 ? 'Good' : score >= 70 ? 'Needs Improvement' : 'Needs Attention';
  const scoreColor = scoreTierColor(score);
  const logoImageRun = coverLogoImageRun(provider);
  const grid = parseStatGrid(htmlReport);
  const problemCount = grid.filter(g => g.status === 'bad' || g.status === 'warn').length;

  const children = [
    new Paragraph({ spacing: { before: logoImageRun ? 400 : 600 }, children: [] }),
    ...(logoImageRun
      ? [new Paragraph({ alignment: AlignmentType.CENTER, children: [logoImageRun], spacing: { after: 120 } })]
      : []),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: client.name, bold: true, size: 40, color: brandHex, font: HEADING_FONT })],
      spacing: { after: 60 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'Customer Audit Report', size: 24, color: '595959', font: HEADING_FONT })],
      spacing: { after: 40 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `${client.url}  |  Scanned ${pagesCrawled} page${pagesCrawled === 1 ? '' : 's'} on ${dateLabel}`, size: 18, color: '94A3B8' })],
      spacing: { after: 260 },
    }),

    // ── Your Score ──
    new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: 'YOUR SCORE', size: 15, color: '94A3B8', characterSpacing: 30 })], spacing: { after: 50 } }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [
        new TextRun({ text: String(score), bold: true, size: 60, color: scoreColor, font: HEADING_FONT }),
        new TextRun({ text: ' / 100', size: 24, color: '94A3B8' }),
      ],
      spacing: { after: 30 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: scoreLabel.toUpperCase(), bold: true, size: 18, color: scoreColor, characterSpacing: 20 })],
      spacing: { after: 200 },
    }),
    scoreBarTable(score, 45),
    new Paragraph({ spacing: { after: 320 }, children: [] }),
  ];

  // ── Where You Should Be ──
  children.push(
    new Paragraph({ children: [new TextRun({ text: 'Where You Should Be', bold: true, size: 26, color: brandHex, font: HEADING_FONT })], spacing: { after: 140 }, border: { bottom: { color: brandHex, space: 4, style: BorderStyle.SINGLE, size: 6 } } }),
  );
  if (score < GOOD_THRESHOLD) {
    const gap = GOOD_THRESHOLD - score;
    children.push(new Paragraph({
      spacing: { after: 260 },
      children: [
        new TextRun({ text: `Sites that rank well typically score `, size: 21, color: '333333' }),
        new TextRun({ text: `${GOOD_THRESHOLD}+ (Good)`, bold: true, size: 21, color: '16A34A' }),
        new TextRun({ text: `. Right now ${client.name} is `, size: 21, color: '333333' }),
        new TextRun({ text: `${gap} point${gap === 1 ? '' : 's'} away`, bold: true, size: 21, color: 'DC2626' }),
        new TextRun({ text: ` from that threshold — every point below it is a real reason a competitor outranks you in search.`, size: 21, color: '333333' }),
      ],
    }));
  } else {
    const gap = 100 - score;
    children.push(new Paragraph({
      spacing: { after: 260 },
      children: gap > 0 ? [
        new TextRun({ text: `${client.name} is already in the `, size: 21, color: '333333' }),
        new TextRun({ text: 'Good', bold: true, size: 21, color: '16A34A' }),
        new TextRun({ text: ` range — but there's still `, size: 21, color: '333333' }),
        new TextRun({ text: `${gap} point${gap === 1 ? '' : 's'}`, bold: true, size: 21, color: 'DC2626' }),
        new TextRun({ text: ` between here and a perfect, fully-optimized site. Here's exactly what's left:`, size: 21, color: '333333' }),
      ] : [
        new TextRun({ text: `A perfect technical score — genuinely rare. The findings below are the remaining, lower-priority polish items.`, size: 21, color: '333333' }),
      ],
    }));
  }

  // ── The Problems We Found ── the full stat grid, unfiltered — the same
  // tiles the client already sees on the on-screen report, not a curated
  // subset, so nothing here can be second-guessed as cherry-picked.
  children.push(
    new Paragraph({ children: [new TextRun({ text: 'What We Found', bold: true, size: 26, color: brandHex, font: HEADING_FONT })], spacing: { before: 100, after: 100 }, border: { bottom: { color: brandHex, space: 4, style: BorderStyle.SINGLE, size: 6 } } }),
  );
  if (grid.length) {
    children.push(new Paragraph({
      spacing: { after: 200 },
      children: [new TextRun({
        text: problemCount
          ? `${problemCount} of the ${grid.length} categories we check came back flagged — the same full breakdown our own report uses internally, not a summary.`
          : `Every category we check came back clean — a rare, strong result.`,
        size: 20, color: '333333',
      })],
    }));
    children.push(statGridTable(grid, brandHex));
  } else {
    // No stored html_report to parse (an old run from before this was
    // saved) — the score/target sections above still stand on their own.
    children.push(new Paragraph({
      spacing: { after: 200 },
      children: [new TextRun({ text: 'A detailed category breakdown isn\'t available for this specific run, but the score above reflects a real, full scan of the site.', size: 20, color: '595959', italics: true })],
    }));
  }

  children.push(
    new Paragraph({ spacing: { before: 320 }, children: [], border: { top: { color: 'E2E8F0', space: 8, style: BorderStyle.SINGLE, size: 4 } } }),
    new Paragraph({ spacing: { before: 260, after: 100 }, children: [new TextRun({ text: 'Ready to fix this?', bold: true, size: 24, color: brandHex })] }),
    new Paragraph({
      spacing: { after: 100 },
      children: [new TextRun({
        text: `${provider.name} turns this list into a prioritized, done-for-you fix plan — most of what's above is fixable in weeks, not months.`,
        size: 21, color: '333333',
      })],
    }),
    new Paragraph({
      children: [new TextRun({ text: provider.email || '', bold: true, size: 21, color: brandHex })],
    }),
  );

  const doc = new Document({
    styles: {
      default: {
        document: { run: { font: BODY_FONT, size: 20 } },
      },
    },
    sections: [{
      properties: { titlePage: true },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: `${provider.name} — Customer Audit Report — ${dateLabel}`, size: 15, color: '94A3B8' })],
          })],
        }),
      },
      children,
    }],
  });

  return Packer.toBuffer(doc);
}

module.exports = { buildSalesReport, parseStatGrid };
