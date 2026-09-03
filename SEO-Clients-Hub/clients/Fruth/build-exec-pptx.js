const pptxgen = require('pptxgenjs');

const pres = new pptxgen();
pres.layout = 'LAYOUT_WIDE'; // 13.33" x 7.5"

// ── Colors (Fruth palette) ───────────────────────────────────────────────────
const RED     = 'C41230'; // Fruth red  — replaces CP green 215732
const NAVY    = '0C2340'; // Fruth navy — headlines
const DARK    = '262626'; // body text
const GRAY    = '6B7280'; // subtext
const TILE_BG = 'EBF1F9'; // light blue tile bg  (CP used EAF3E6)
const TILE_BD = 'BDD7EE'; // tile border          (CP used C6E0B4)
const AMBER   = 'FFF3D6'; // in-progress bg
const AMBER_B = 'F0D890'; // in-progress border
const AMBER_T = '8A6D00'; // in-progress label

const slide = pres.addSlide();
slide.background = { color: 'FFFFFF' };

// ── Eyebrow ──────────────────────────────────────────────────────────────────
slide.addText('FRUTH CUSTOM PACKAGING', {
  x: 0.5, y: 0.38, w: 9.0, h: 0.3,
  fontSize: 12, bold: true, color: RED,
  charSpacing: 2, fontFace: 'Arial',
  margin: 0, valign: 'middle',
});

// ── Headline ─────────────────────────────────────────────────────────────────
slide.addText('SEO Progress — 47-Day Snapshot', {
  x: 0.5, y: 0.66, w: 12.3, h: 0.65,
  fontSize: 34, bold: true, color: NAVY,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Subline ──────────────────────────────────────────────────────────────────
slide.addText('Reporting period: May 27 – July 14, 2026   |   Prepared by Start Advertising', {
  x: 0.5, y: 1.32, w: 12.3, h: 0.35,
  fontSize: 13, color: GRAY, fontFace: 'Arial',
  margin: 0, valign: 'middle',
});

// ── KPI tiles ────────────────────────────────────────────────────────────────
const kpis = [
  { value: '26',      label: 'Product Pages\nExpanded & Optimized' },
  { value: '62',      label: 'Organic Keywords\nIndexed by Google', delta: '+87.9% vs. May 27' },
  { value: '154',     label: 'Organic Traffic\nSessions',           delta: '+30.5% vs. May 27' },
  { value: '78/100',  label: 'On-Page SEO\nHealth Score',           delta: '+2.6 pts vs. May 27' },
];

const TILE_X = [0.5, 3.59, 6.68, 9.77];
const TILE_Y = 1.9;
const TILE_W = 2.87;
const TILE_H = 1.5;

kpis.forEach((k, i) => {
  const x = TILE_X[i];

  // Rounded rect tile
  slide.addShape(pres.ShapeType.roundRect, {
    x, y: TILE_Y, w: TILE_W, h: TILE_H,
    fill: { color: TILE_BG },
    line: { color: TILE_BD, width: 1 },
    rectRadius: 0.12,
  });

  // Big value
  slide.addText(k.value, {
    x, y: TILE_Y + 0.18, w: TILE_W, h: 0.7,
    fontSize: 40, bold: true, color: RED,
    fontFace: 'Arial', align: 'center', margin: 0, valign: 'middle',
  });

  // Label
  slide.addText(k.label, {
    x: x + 0.1, y: TILE_Y + 0.88, w: TILE_W - 0.2, h: 0.38,
    fontSize: 12, color: DARK, fontFace: 'Arial',
    align: 'center', margin: 0, valign: 'top',
  });

  // Delta badge (if present)
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
  fontSize: 16, bold: true, color: RED,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Checklist ────────────────────────────────────────────────────────────────
const checks = [
  '26 product pages rewritten with B2B-focused, keyword-rich copy',
  'FAQ sections added to all 26 product pages',
  'FAQ schema (JSON-LD structured data) implemented on all 26 pages',
  'Organization schema added to the homepage',
  'SEO issues reduced by 14.5% — from 76 open to 65',
  '14 pages fully live in HubSpot — content and schema active',
];

const checkItems = checks.map((c, i) => ([
  { text: '✓  ', options: { bold: true, color: RED, fontSize: 14 } },
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
  fontSize: 16, bold: true, color: RED,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Bar chart ─────────────────────────────────────────────────────────────────
slide.addChart(pres.ChartType.bar, [
  {
    name: 'Positions Gained',
    labels: [
      'plastic grow bag',
      'autoclaving bags',
      'plastic bags for plants',
      'polypropylene grow bag',
      'autoclavable bags',
      'cleanroom packaging',
    ],
    values: [1, 1, 4, 6, 11, 80],
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
  { text: 'IN PROGRESS   ', options: { bold: true, color: AMBER_T, fontSize: 12 } },
  { text: 'H1 headings needed on 3 pages (/capabilities, /fruth-360, /our-story)  ·  “cleanroom packaging” at #20 and climbing — an internal link will accelerate it to page one', options: { color: DARK, fontSize: 12 } },
], {
  x: 0.75, y: 6.35, w: 11.83, h: 0.6,
  fontFace: 'Arial', margin: 0, valign: 'middle',
});

// ── Footer ────────────────────────────────────────────────────────────────────
slide.addText('Prepared by Start Advertising   ·   fruth.com   ·   Confidential — For Fruth Custom Packaging', {
  x: 0.5, y: 7.08, w: 12.3, h: 0.3,
  fontSize: 10, color: GRAY, fontFace: 'Arial',
  margin: 0, valign: 'middle',
});

// ── Save ──────────────────────────────────────────────────────────────────────
const OUT = 'C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Fruth/Fruth-SEO-Executive-Summary-July2026.pptx';

pres.writeFile({ fileName: OUT })
  .then(() => console.log('SUCCESS:', OUT))
  .catch(err => { console.error('FAILED:', err.message); process.exit(1); });
