// Generates the "Customer Audit Report" — a Word document meant to be
// shown to a prospect to make the case that they need help, not a teaser
// that withholds detail. It shows the score, how far that score is from
// "Good," and the full findings number-grid — the same stat tiles as the
// on-screen quick report (see audit-engine.js's buildReport(), the
// `.snap-card`/`.snap-num`/`.snap-label` markup) — so the sheer number of
// red/amber tiles makes the case on its own.
//
// The cover is a full-bleed brand-color page (its own docx `section` with
// page margins zeroed out and a single edge-to-edge shaded table filling
// it), not a banner on a white background — a plain white page with only
// colored numbers read as "unfinished" next to a competitor's report, and
// full-bleed color is what actually reads as "designed" rather than
// "a Word document." Section 2 (normal margins, white background) carries
// the findings grid and close, styled with the same color-blocking
// (banner-style section headers, tinted grid cells, a solid CTA box).
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
  HeightRule, VerticalAlign,
} = require('docx');
const cheerio = require('cheerio');
const {
  scoreTierColor, coverLogoImageRun, BODY_FONT, HEADING_FONT,
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

// US Letter, in twips (1/20 pt) — docx's own default page size, spelled out
// explicitly here since the cover section below overrides margins to 0 and
// needs to know the exact page box its full-bleed table has to fill.
const PAGE_WIDTH = 12240;
const PAGE_HEIGHT = 15840;

// Blends a hex color toward white — used to derive the cover's score-badge
// fill/border from the brand color itself (a fixed lighter navy wouldn't
// track every partner's actual brand hue), since Word shading only takes
// solid colors, no alpha/opacity.
function blendWithWhite(hex, amt) {
  const n = parseInt(hex, 16);
  const r = (n >> 16) & 0xff, g = (n >> 8) & 0xff, b = n & 0xff;
  const mix = (c) => Math.round(c + (255 - c) * amt);
  return [mix(r), mix(g), mix(b)].map((v) => v.toString(16).padStart(2, '0')).join('').toUpperCase();
}

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

// The score badge that floats top-right on the cover — a bordered card
// (Word has no shape/rect primitive, so this is the same borderless/bordered
// shaded-table trick used throughout this file) sized to a fraction of its
// parent cell's width and right-aligned, rather than the score being just
// another centered line of text in the page flow.
function scoreBadgeTable(score, scoreColor, scoreLabel, brandHex) {
  const fill = blendWithWhite(brandHex, 0.12);
  const border = blendWithWhite(brandHex, 0.35);
  return new Table({
    width: { size: 62, type: WidthType.PERCENTAGE },
    alignment: AlignmentType.RIGHT,
    rows: [new TableRow({
      children: [new TableCell({
        shading: { type: ShadingType.CLEAR, fill },
        borders: {
          top: { style: BorderStyle.SINGLE, size: 6, color: border },
          bottom: { style: BorderStyle.SINGLE, size: 6, color: border },
          left: { style: BorderStyle.SINGLE, size: 6, color: border },
          right: { style: BorderStyle.SINGLE, size: 6, color: border },
        },
        margins: { top: 160, bottom: 160, left: 120, right: 120 },
        children: [
          new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: String(score), bold: true, size: 44, color: 'FFFFFF', font: HEADING_FONT })],
            spacing: { after: 20 },
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: 'SEO HEALTH SCORE', size: 12, color: 'DCE4F2', characterSpacing: 8 })],
            spacing: { after: 30 },
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: scoreLabel, bold: true, size: 15, color: scoreColor })],
          }),
        ],
      })],
    })],
  });
}

// Eyebrow (left) + score badge (right) sharing one borderless row — mirrors
// a competitor report's cover layout, where the brand line and the score
// sit on the same header row instead of stacking.
function coverHeaderRow(providerName, badge) {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({
      children: [
        new TableCell({
          width: { size: 38, type: WidthType.PERCENTAGE },
          borders: NO_BORDERS,
          verticalAlign: VerticalAlign.CENTER,
          children: [new Paragraph({
            children: [new TextRun({ text: `${providerName.toUpperCase()}  •  SEO AUDIT`, size: 14, color: 'B9C6DC', characterSpacing: 10 })],
          })],
        }),
        new TableCell({
          width: { size: 62, type: WidthType.PERCENTAGE },
          borders: NO_BORDERS,
          children: [badge],
        }),
      ],
    })],
  });
}

// The horizontal "quick facts" strip near the bottom of the cover — label
// small-caps above, bold value below, spread across equal columns. Only
// uses numbers this route actually has (see this file's top doc comment);
// unlike a competitor's per-page breakdowns ("16 of 19 pages"), this data
// only has category-level counts for a saved run, so the labels here are
// phrased to match what's really being counted.
function coverStatsStrip(stats) {
  const cell = (stat) => new TableCell({
    width: { size: 100 / stats.length, type: WidthType.PERCENTAGE },
    borders: NO_BORDERS,
    children: [
      new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text: stat.label.toUpperCase(), size: 13, color: '8CA0C4', characterSpacing: 8 })] }),
      new Paragraph({ children: [new TextRun({ text: stat.value, bold: true, size: 24, color: 'FFFFFF', font: HEADING_FONT })] }),
    ],
  });
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({ children: stats.map(cell) })],
  });
}

// A thin horizontal rule — same borderless-shaded-cell trick, one row tall.
function thinRule(color) {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({
      children: [new TableCell({
        shading: { type: ShadingType.CLEAR, fill: color },
        borders: NO_BORDERS,
        margins: { top: 6, bottom: 6, left: 0, right: 0 },
        children: [new Paragraph({ children: [] })],
      })],
    })],
  });
}

// The full-bleed cover itself: one table, one cell, sized to the exact page
// box (see PAGE_WIDTH/PAGE_HEIGHT above) so the brand color runs edge to
// edge — this is what a zero-margin docx `section` makes possible, and is
// the single biggest lever for "looks like a sales brochure" vs. "looks
// like a Word doc with a colored header."
function coverPage({ client, provider, brandHex, score, scoreColor, scoreLabel, logoImageRun, dateLabel, pagesCrawled, problemCount, gridLength, totalChecksRun }) {
  const badge = scoreBadgeTable(score, scoreColor, scoreLabel, brandHex);
  const stats = [
    { label: 'Report Date', value: dateLabel },
    { label: 'Pages Scanned', value: String(pagesCrawled) },
    { label: 'Categories Flagged', value: gridLength ? `${problemCount} of ${gridLength}` : '—' },
    { label: 'Checks Performed', value: totalChecksRun.toLocaleString() },
  ];

  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({
      height: { value: PAGE_HEIGHT - 40, rule: HeightRule.ATLEAST },
      children: [new TableCell({
        shading: { type: ShadingType.CLEAR, fill: brandHex },
        borders: NO_BORDERS,
        verticalAlign: VerticalAlign.TOP,
        margins: { top: 620, bottom: 620, left: 720, right: 720 },
        children: [
          coverHeaderRow(provider.name, badge),
          new Paragraph({ spacing: { before: logoImageRun ? 1400 : 2000 }, children: [] }),
          ...(logoImageRun
            ? [new Paragraph({ children: [logoImageRun], spacing: { after: 200 } })]
            : []),
          new Paragraph({
            children: [new TextRun({ text: client.name, bold: true, size: 56, color: 'FFFFFF', font: HEADING_FONT })],
            spacing: { after: 100 },
          }),
          new Paragraph({
            children: [new TextRun({ text: client.url, size: 20, color: 'B9C6DC' })],
            spacing: { after: 900 },
          }),
          thinRule(blendWithWhite(brandHex, 0.3)),
          new Paragraph({ spacing: { before: 320 }, children: [] }),
          coverStatsStrip(stats),
        ],
      })],
    })],
  });
}

// A light-tint "pill" badge row (failed / warnings / passed counts) — free
// to compute (it's just a tally of the same grid statuses the tiles below
// already carry) and gives an at-a-glance scoreboard right after the cover,
// before anyone scrolls to the full grid. Word has no border-radius, so the
// "pill" is approximated the same way everything else in this file fakes
// shapes: a borderless, shaded table cell.
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

// A full-width solid-color banner for a section title — stands in for a
// real "card header," and reads as designed in a way a thin bottom-border
// on plain text never does.
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

// Each tile carries a light background tint matching its verdict (the same
// red/amber/green family the pills above use), not just colored text on a
// plain white cell — turns the grid into something that reads as a mosaic
// of results at a glance, the same way the on-screen report's own
// `.snap-card` color-coding does, instead of a data table. Gaps between
// tiles are a very light gray (not pure white) so each card reads as a
// distinct tile with a little more breathing room, closer to a bordered
// "card" than a dense spreadsheet.
const GRID_COLUMNS = 3;
function statGridTable(grid, brandHex) {
  const gap = { style: BorderStyle.SINGLE, size: 8, color: 'F8FAFC' };
  const cell = (item) => new TableCell({
    width: { size: 100 / GRID_COLUMNS, type: WidthType.PERCENTAGE },
    shading: item ? { type: ShadingType.CLEAR, fill: STATUS_TINT[item.status] || 'FFFFFF' } : undefined,
    borders: { top: gap, bottom: gap, left: gap, right: gap },
    margins: { top: 170, bottom: 170, left: 80, right: 80 },
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
// paragraph — the one place on page 2 this is deliberately as bold as the
// cover itself, since it's the entire point of the document.
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

  const cover = coverPage({
    client, provider, brandHex, score, scoreColor, scoreLabel, logoImageRun, dateLabel,
    pagesCrawled, problemCount, gridLength: grid.length, totalChecksRun,
  });

  const children = [];

  // ── At A Glance ── the pill scoreboard right at the top of page 2, so
  // the reader who just saw the score badge on the cover gets the
  // pass/fail breakdown before the narrative or the full grid.
  if (grid.length) {
    children.push(
      new Paragraph({ spacing: { before: 0, after: 160 }, children: [] }),
      statusPillsTable(pillCounts),
      new Paragraph({ spacing: { after: 320 }, children: [] }),
    );
  }

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
    sections: [
      {
        // The cover: zero page margins so its full-bleed table (see
        // coverPage() above) reaches every edge, and no footer — a page
        // number/footer line on a brochure cover reads as "report,"
        // exactly what this is deliberately styled to not look like.
        properties: {
          page: {
            size: { width: PAGE_WIDTH, height: PAGE_HEIGHT },
            margin: { top: 0, bottom: 0, left: 0, right: 0 },
          },
        },
        children: [cover],
      },
      {
        // Back to normal margins for the findings/CTA pages.
        properties: {
          page: {
            size: { width: PAGE_WIDTH, height: PAGE_HEIGHT },
          },
        },
        footers: {
          default: new Footer({
            children: [new Paragraph({
              alignment: AlignmentType.CENTER,
              children: [new TextRun({ text: `${provider.name} — Customer Audit Report — ${dateLabel}`, size: 15, color: '94A3B8' })],
            })],
          }),
        },
        children,
      },
    ],
  });

  return Packer.toBuffer(doc);
}

module.exports = { buildSalesReport, parseStatGrid };
