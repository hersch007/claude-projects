// CFB Executive Summary slide — mirrors Fruth's build-exec-pptx.js structure/style.
// Run: NODE_PATH="C:/Users/richa/AppData/Roaming/npm/node_modules" node gen-exec-slide.js
const pptxgen = require('pptxgenjs');

const pres = new pptxgen();
pres.layout = 'LAYOUT_WIDE'; // 13.33" x 7.5"

// ── Colors (CFB palette — navy/gold, vs Fruth's red/navy) ───────────────────
const NAVY    = '003366'; // CFB navy — headlines, replaces Fruth red accent
const GOLD    = 'C9A84C'; // CFB gold accent
const DARK    = '262626';
const GRAY    = '6B7280';
const TILE_BG = 'EBF1F9';
const TILE_BD = 'B7C6D9';
const AMBER   = 'FFF3D6';
const AMBER_B = 'F0D890';
const AMBER_T = '8A6D00';

const slide = pres.addSlide();
slide.background = { color: 'FFFFFF' };

// ── Eyebrow ──────────────────────────────────────────────────────────────────
slide.addText('CLEANROOM FILM & BAGS', {
  x: 0.5, y: 0.38, w: 9.0, h: 0.3,
  fontSize: 12, bold: true, color: NAVY,
  charSpacing: 2, fontFace: 'Arial',
  margin: 0, valign: 'middle',
});

// ── Headline ─────────────────────────────────────────────────────────────────
slide.addText('SEO Progress — First Tracked Period', {
  x: 0.5, y: 0.66, w: 12.3, h: 0.65,
  fontSize: 34, bold: true, color: NAVY,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Subline ──────────────────────────────────────────────────────────────────
slide.addText('Reporting period: July 14 – July 28, 2026   |   Prepared by Start Advertising', {
  x: 0.5, y: 1.32, w: 12.3, h: 0.35,
  fontSize: 13, color: GRAY, fontFace: 'Arial',
  margin: 0, valign: 'middle',
});

// ── KPI tiles ────────────────────────────────────────────────────────────────
const kpis = [
  { value: '19',      label: 'Site Pages\nFully Optimized' },
  { value: '785',     label: 'Organic Clicks\n(Search Console)', delta: '+8% this period' },
  { value: '17',      label: 'Keywords Ranking\nin Top 10',       delta: 'up from 12' },
  { value: '80/100',  label: 'On-Page SEO\nScore (Ubersuggest)',  delta: '"High" rating' },
];

const TILE_X = [0.5, 3.59, 6.68, 9.77];
const TILE_Y = 1.9;
const TILE_W = 2.87;
const TILE_H = 1.5;

kpis.forEach((k, i) => {
  const x = TILE_X[i];

  slide.addShape(pres.ShapeType.roundRect, {
    x, y: TILE_Y, w: TILE_W, h: TILE_H,
    fill: { color: TILE_BG },
    line: { color: TILE_BD, width: 1 },
    rectRadius: 0.12,
  });

  slide.addText(k.value, {
    x, y: TILE_Y + 0.18, w: TILE_W, h: 0.7,
    fontSize: 40, bold: true, color: NAVY,
    fontFace: 'Arial', align: 'center', margin: 0, valign: 'middle',
  });

  slide.addText(k.label, {
    x: x + 0.1, y: TILE_Y + 0.88, w: TILE_W - 0.2, h: 0.38,
    fontSize: 12, color: DARK, fontFace: 'Arial',
    align: 'center', margin: 0, valign: 'top',
  });

  if (k.delta) {
    slide.addText(k.delta, {
      x: x + 0.1, y: TILE_Y + 1.28, w: TILE_W - 0.2, h: 0.16,
      fontSize: 9, bold: true, color: '1A7D43', fontFace: 'Arial',
      align: 'center', margin: 0,
    });
  }
});

// ── Section head: Work Delivered ─────────────────────────────────────────────
slide.addText('Work Delivered This Period', {
  x: 0.5, y: 3.62, w: 7.0, h: 0.35,
  fontSize: 16, bold: true, color: NAVY,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Checklist ────────────────────────────────────────────────────────────────
const checks = [
  'Homepage: title, meta description, and Organization schema deployed sitewide',
  'All 7 market pages optimized — titles, metas, alt text, and Service/CollectionPage schema',
  'All 10 Learning Center posts optimized with Article/NewsArticle schema and author byline',
  'Our Story page corrected — replaced a live title/meta mismatch, added AboutPage schema',
  '50-keyword tracking list built in Ubersuggest, including a dedicated VCI cluster',
  '19 of 52 total site pages now fully optimized (37% complete)',
];

const checkItems = checks.map((c, i) => ([
  { text: '✓  ', options: { bold: true, color: NAVY, fontSize: 14 } },
  { text: c,         options: { color: DARK, fontSize: 14 } },
  ...(i < checks.length - 1 ? [{ text: '\n', options: {} }] : []),
])).flat();

slide.addText(checkItems, {
  x: 0.5, y: 4.05, w: 6.8, h: 2.15,
  fontFace: 'Arial', margin: 0, valign: 'top',
  paraSpaceAfter: 9,
});

// ── Section head: Keyword Gains ───────────────────────────────────────────────
slide.addText('Keyword Position Gains', {
  x: 7.75, y: 3.62, w: 5.08, h: 0.35,
  fontSize: 16, bold: true, color: NAVY,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Bar chart ─────────────────────────────────────────────────────────────────
slide.addChart(pres.ChartType.bar, [
  {
    name: 'Positions Gained',
    labels: [
      'nylon bag for packaging',
      'cleanroom ziplock bags',
      'nylon oven bag',
      'semiconductor cleanroom pkg',
      'aluminium foil bag',
    ],
    values: [4, 6, 6, 6, 15],
  },
], {
  x: 7.62, y: 3.98, w: 5.2, h: 2.4,
  barDir: 'bar',
  chartColors: [NAVY],
  showTitle: false,
  showLegend: false,
  showValue: true,
  dataLabelPosition: 'outEnd',
  dataLabelFontSize: 11,
  dataLabelColor: DARK,
  catAxisLabelFontSize: 11,
  catAxisLabelColor: GRAY,
  valAxisHidden: true,
  valGridLine: { style: 'none' },
  catGridLine: { style: 'none' },
  plotAreaBorderColor: 'FFFFFF',
  chartAreaBorderColor: 'FFFFFF',
});

// ── IN PROGRESS banner ────────────────────────────────────────────────────────
slide.addShape(pres.ShapeType.roundRect, {
  x: 0.5, y: 6.35, w: 12.33, h: 0.6,
  fill: { color: AMBER },
  line: { color: AMBER_B, width: 1 },
  rectRadius: 0.1,
});

slide.addText([
  { text: 'OPPORTUNITY   ', options: { bold: true, color: AMBER_T, fontSize: 12 } },
  { text: '"anti-static bags" — 4,400 searches/month, not yet ranking — the largest untapped keyword found this period  ·  VCI cluster (1,190+ combined monthly searches) awaiting its landing page', options: { color: DARK, fontSize: 12 } },
], {
  x: 0.75, y: 6.35, w: 11.83, h: 0.6,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Footer ────────────────────────────────────────────────────────────────────
slide.addText('Prepared by Start Advertising   ·   cleanroomfilm.com   ·   Confidential — For Cleanroom Film & Bags', {
  x: 0.5, y: 7.08, w: 12.3, h: 0.3,
  fontSize: 10, color: GRAY, fontFace: 'Arial',
  margin: 0, valign: 'middle',
});

// ── Save ──────────────────────────────────────────────────────────────────────
const OUT = 'CFB-SEO-Executive-Summary-2026-07-28.pptx';

pres.writeFile({ fileName: OUT })
  .then(() => console.log('SUCCESS:', OUT))
  .catch(err => { console.error('FAILED:', err.message); process.exit(1); });
