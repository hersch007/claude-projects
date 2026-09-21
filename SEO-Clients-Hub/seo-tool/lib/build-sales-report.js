// Generates the "Customer Audit Report" — a Word document meant to be
// shown to a prospect to make the case that they need help, not a teaser
// that withholds detail. It shows the score, how far that score is from
// "Good," and the full findings number-grid — the same stat tiles as the
// on-screen quick report (see audit-engine.js's buildReport(), the
// `.snap-card`/`.snap-num`/`.snap-label` markup) — so the sheer number of
// red/amber tiles makes the case on its own. Styled with real color
// blocking (banner-style section headers, tinted grid cells, a solid CTA
// box) rather than thin text-and-a-border — a plain white page with only
// colored numbers read as "unfinished" next to a competitor's report.
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
  Table, TableRow, TableCell, WidthType, BorderStyle, ShadingType,
} = require('docx');
const cheerio = require('cheerio');
const {
  scoreTierColor, scoreBarTable, coverLogoImageRun, BODY_FONT, HEADING_FONT,
} = require('./build-docx-report');

const GOOD_THRESHOLD = 85; // matches audit-engine.js's own scoreLabel tiering

// The number of distinct check *types* calcScore() can flag — one count
// per `deduct()` call site in lib/audit-engine.js (missing title, duplicate
// H1, broken links, Open Graph, sitemap, etc.), not the same thing as
// TOTAL_FINDING_CATEGORIES below (that's the on-screen stat grid's tile
// count, which includes a few purely informational tiles this doesn't).
// No single source of truth exports this today, so it's a plain constant —
// re-count `grep -c "if (.*deduct(" lib/audit-engine.js` and update this
// if calcScore() gains or loses a check.
const TOTAL_CHECK_TYPES = 33;

const STATUS_COLOR = { good: '16A34A', warn: 'D97706', bad: 'DC2626' };
const STATUS_TINT = { good: 'F0FDF4', warn: 'FFFBEB', bad: 'FEF2F2' };
const NO_BORDER = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const NO_BORDERS = { top: NO_BORDER, bottom: NO_BORDER, left: NO_BORDER, right: NO_BORDER };

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

// A full-width solid-color banner for a section title — stands in for a
// real "card header," and reads as designed in a way a thin bottom-border
// on plain text never does. White text throughout since every brand color
// this is called with is dark enough for contrast (same assumption
// build-docx-report.js's own cover already makes about provider.brand).
function sectionBand(text, bgHex) {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({
      children: [new TableCell({
        shading: { type: ShadingType.CLEAR, fill: bgHex },
        borders: NO_BORDERS,
        margins: { top: 140, bottom: 140, left: 200, right: 200 },
        children: [new Paragraph({ children: [new TextRun({ text, bold: true, size: 24, color: 'FFFFFF', font: HEADING_FONT, characterSpacing: 4 })] })],
      })],
    })],
  });
}

// A light-tint "pill" badge row (failed / warnings / passed counts) right
// under the score — free to compute (it's just a tally of the same grid
// statuses the tiles below already carry) and gives an at-a-glance read
// before anyone scrolls to the grid itself. Word has no border-radius, so
// the "pill" is approximated the same way scoreBarTable/sectionBand fake
// shapes elsewhere in this file: a borderless, shaded table cell.
const PILL_STYLE = {
  bad: { bg: 'FEE2E2', text: 'DC2626', word: 'failed' },
  warn: { bg: 'FEF3C7', text: 'D97706', word: 'warnings' },
  good: { bg: 'DCFCE7', text: '16A34A', word: 'passed' },
};
function statusPillsTable(counts) {
  const cell = (status) => {
    const style = PILL_STYLE[status];
    return new TableCell({
      width: { size: 100 / 3, type: WidthType.PERCENTAGE },
      shading: { type: ShadingType.CLEAR, fill: style.bg },
      margins: { top: 90, bottom: 90, left: 60, right: 60 },
      borders: NO_BORDERS,
      children: [new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [
          new TextRun({ text: String(counts[status]), bold: true, size: 22, color: style.text }),
          new TextRun({ text: ` ${style.word}`, bold: true, size: 18, color: style.text }),
        ],
      })],
    });
  };
  return new Table({
    width: { size: 78, type: WidthType.PERCENTAGE },
    alignment: AlignmentType.CENTER,
    rows: [new TableRow({ children: ['bad', 'warn', 'good'].map(cell) })],
  });
}

// Each tile now carries a light background tint matching its verdict (the
// same red/amber/green family the pills above use), not just colored text
// on a plain white cell — turns the grid into something that reads as a
// mosaic of results at a glance, the same way the on-screen report's own
// `.snap-card` color-coding does, instead of a data table.
const GRID_COLUMNS = 3;
function statGridTable(grid, brandHex) {
  const cell = (item) => new TableCell({
    width: { size: 100 / GRID_COLUMNS, type: WidthType.PERCENTAGE },
    shading: item ? { type: ShadingType.CLEAR, fill: STATUS_TINT[item.status] || 'FFFFFF' } : undefined,
    borders: { top: { style: BorderStyle.SINGLE, size: 4, color: 'FFFFFF' }, bottom: { style: BorderStyle.SINGLE, size: 4, color: 'FFFFFF' }, left: { style: BorderStyle.SINGLE, size: 4, color: 'FFFFFF' }, right: { style: BorderStyle.SINGLE, size: 4, color: 'FFFFFF' } },
    margins: { top: 140, bottom: 140, left: 80, right: 80 },
    children: item ? [
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 30 },
        children: [new TextRun({ text: item.value, bold: true, size: 28, color: STATUS_COLOR[item.status] || brandHex, font: HEADING_FONT })],
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: item.label, size: 15, color: '475569' })],
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

// The closing call-to-action as a solid color block, not a plain
// paragraph — the one place on the page this is deliberately as bold as
// the score itself, since it's the entire point of the document.
function ctaBox(provider, accentHex) {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({
      children: [new TableCell({
        shading: { type: ShadingType.CLEAR, fill: accentHex },
        borders: NO_BORDERS,
        margins: { top: 280, bottom: 280, left: 320, right: 320 },
        children: [
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 100 },
            children: [new TextRun({ text: 'Ready to fix this?', bold: true, size: 28, color: 'FFFFFF', font: HEADING_FONT })],
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 140 },
            children: [new TextRun({
              text: `${provider.name} turns this list into a prioritized, done-for-you fix plan — most of what's above is fixable in weeks, not months.`,
              size: 21, color: 'FFFFFF',
            })],
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: provider.email || '', bold: true, size: 22, color: 'FFFFFF' })],
          }),
        ],
      })],
    })],
  });
}

function buildSalesReport({ client, provider, score, pagesCrawled, htmlReport, date }) {
  const brandHex = (provider.brand || '#003366').replace('#', '');
  const accentHex = (provider.accent || provider.brand2 || provider.brand || '#003366').replace('#', '');
  const dateLabel = new Date(date + 'T12:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const scoreLabel = score >= 85 ? 'Good' : score >= 70 ? 'Needs Improvement' : 'Needs Attention';
  const scoreColor = scoreTierColor(score);
  const logoImageRun = coverLogoImageRun(provider);
  const grid = parseStatGrid(htmlReport);
  const problemCount = grid.filter(g => g.status === 'bad' || g.status === 'warn').length;
  // "neutral" tiles (Missing Canonical Tag's raw count, Sitemap.xml Found,
  // Total Pages Audited) are informational counts, not a pass/fail
  // judgment the on-screen report itself made — left out of the pills so
  // this always sums to a subset of grid.length, never double-counts, and
  // never claims a verdict the grid's own coloring didn't already make.
  const pillCounts = { bad: 0, warn: 0, good: 0 };
  for (const g of grid) if (pillCounts[g.status] !== undefined) pillCounts[g.status]++;
  const totalChecksRun = TOTAL_CHECK_TYPES * Math.max(1, pagesCrawled || 1);

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
      children: [new TextRun({ text: 'CUSTOMER AUDIT REPORT', bold: true, size: 22, color: '1E293B', font: HEADING_FONT, characterSpacing: 20 })],
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
    new Paragraph({ spacing: { after: 240 }, children: [] }),
    ...(grid.length ? [statusPillsTable(pillCounts), new Paragraph({ spacing: { after: 160 }, children: [] })] : [new Paragraph({ spacing: { after: 160 }, children: [] })]),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `${totalChecksRun.toLocaleString()} checks performed  •  ${pagesCrawled} page${pagesCrawled === 1 ? '' : 's'} scanned`, size: 17, bold: true, color: '64748B' })],
      spacing: { after: 320 },
    }),
  ];

  // ── Where You Should Be ──
  children.push(
    sectionBand('WHERE YOU SHOULD BE', brandHex),
    new Paragraph({ spacing: { before: 200 }, children: [] }),
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
    sectionBand('WHAT WE FOUND', brandHex),
    new Paragraph({ spacing: { before: 200 }, children: [] }),
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
    children.push(new Paragraph({ spacing: { after: 320 }, children: [] }));
  } else {
    // No stored html_report to parse (an old run from before this was
    // saved) — the score/target sections above still stand on their own.
    children.push(new Paragraph({
      spacing: { after: 320 },
      children: [new TextRun({ text: 'A detailed category breakdown isn\'t available for this specific run, but the score above reflects a real, full scan of the site.', size: 20, color: '595959', italics: true })],
    }));
  }

  children.push(ctaBox(provider, accentHex));

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
