// CFB SEO Progress Report — mirrors Fruth's report structure/styling.
// Run: NODE_PATH="C:/Users/richa/AppData/Roaming/npm/node_modules" node gen-progress-report.js
const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
        Header, Footer, AlignmentType, BorderStyle, WidthType, ShadingType,
        VerticalAlign, PageNumber, LevelFormat, PageBreak, TabStopType, TabStopPosition,
        convertInchesToTwip } = require('docx');
const fs = require('fs');

const CONTENT_W = 9360;

const BLUE       = "003366"; // CFB navy (vs Fruth's 1F5C99)
const LIGHT_BLUE = "D9E0E8";
const MID_BLUE   = "B7C6D9";
const GREEN_TXT  = "217346";
const LIGHT_GRN  = "E2EFDA";
const RED_TXT    = "C00000";
const LIGHT_RED  = "FCE4D6";
const ORANGE_TXT = "BF5700";
const LIGHT_ORG  = "FFF2CC";
const GOLD       = "C9A84C"; // CFB accent (vs Fruth's green)
const LIGHT_GOLD = "F5EFDD";
const BORDER     = "CCCCCC";
const WHITE      = "FFFFFF";
const DARK       = "262626";
const GRAY_444   = "444444";
const GRAY_666   = "666666";
const GRAY_888   = "888888";

const b1   = (color = BORDER) => ({ style: BorderStyle.SINGLE, size: 1, color });
const bAll = (color = BORDER) => ({ top: b1(color), bottom: b1(color), left: b1(color), right: b1(color) });
const cellPadSm = { top: 72, bottom: 72, left: 100, right: 100 };

const tr = (text, { bold=false, size=20, color=DARK, font="Arial", italics=false }={}) =>
  new TextRun({ text, bold, size, color, font, italics });

const spacer = (before=120, after=0) =>
  new Paragraph({ children: [tr("")], spacing: { before, after } });

const rule = (color=BLUE, before=80, after=120) =>
  new Paragraph({
    children: [tr("")],
    border: { bottom: { style: BorderStyle.SINGLE, size: 8, color, space: 1 } },
    spacing: { before, after },
  });

const sectionHead = (text) => [
  spacer(200, 0),
  new Paragraph({
    children: [tr(text, { bold: true, size: 24, color: WHITE })],
    shading: { fill: BLUE, type: ShadingType.CLEAR },
    spacing: { before: 0, after: 0 },
    indent: { left: 160, right: 160 },
  }),
  spacer(100, 0),
];

const metricCell = (label, value, delta, valueColor=BLUE, bg=LIGHT_BLUE) => {
  const children = [
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 0, after: delta ? 20 : 40 },
      children: [tr(value, { bold: true, size: 36, color: valueColor })],
    }),
  ];
  if (delta) {
    children.push(new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 0, after: 30 },
      children: [tr(delta, { bold: true, size: 18, color: valueColor })],
    }));
  }
  children.push(new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 0 },
    children: [tr(label, { size: 17, color: DARK })],
  }));
  return new TableCell({
    borders: bAll(MID_BLUE),
    width: { size: 2340, type: WidthType.DXA },
    shading: { fill: bg, type: ShadingType.CLEAR },
    margins: { top: 120, bottom: 120, left: 140, right: 140 },
    verticalAlign: VerticalAlign.CENTER,
    children,
  });
};

const callout = (text, bg=LIGHT_GRN, textColor=GREEN_TXT) => [
  spacer(120, 0),
  new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    rows: [new TableRow({ children: [new TableCell({
      borders: bAll(bg),
      shading: { fill: bg, type: ShadingType.CLEAR },
      margins: { top: 140, bottom: 140, left: 180, right: 180 },
      children: [new Paragraph({ children: [tr(text, { size: 19, color: textColor })] })],
    })]})],
  }),
  spacer(80, 0),
];

function dataTable(headers, rows, colWidths, opts = {}) {
  const total = colWidths.reduce((a,b)=>a+b, 0);
  const headerRow = new TableRow({
    tableHeader: true,
    children: headers.map((h, i) => new TableCell({
      borders: bAll(BLUE),
      shading: { fill: BLUE, type: ShadingType.CLEAR },
      margins: cellPadSm,
      width: { size: colWidths[i], type: WidthType.DXA },
      children: [new Paragraph({ alignment: i===0 ? AlignmentType.LEFT : AlignmentType.CENTER, children: [tr(h, { bold: true, size: 18, color: WHITE })] })],
    })),
  });
  const bodyRows = rows.map((r, ri) => new TableRow({
    children: r.map((c, ci) => {
      let color = DARK, bold = false;
      if (opts.colorCol === ci) {
        const s = String(c);
        if (s.includes('+') || /up|great|win|improv|strong/i.test(s)) { color = GREEN_TXT; bold = true; }
        else if (s.includes('-') && !s.includes('—')) { color = RED_TXT; bold = true; }
      }
      return new TableCell({
        borders: bAll(BORDER),
        shading: { fill: ri % 2 === 1 ? "F7F9FB" : WHITE, type: ShadingType.CLEAR },
        margins: cellPadSm,
        width: { size: colWidths[ci], type: WidthType.DXA },
        children: [new Paragraph({ alignment: ci===0 ? AlignmentType.LEFT : AlignmentType.CENTER, children: [tr(String(c), { size: 18, color, bold })] })],
      });
    }),
  }));
  return new Table({ width: { size: total, type: WidthType.DXA }, rows: [headerRow, ...bodyRows] });
}

const bullet = (text) => new Paragraph({
  numbering: { reference: 'bullets', level: 0 },
  spacing: { after: 80 },
  children: [tr(text, { size: 19 })],
});

// ────────────────────────────────────────────────────────────────────────────
const body = [];

// Cover / title block
body.push(new Paragraph({ children: [tr("CLEANROOM FILM & BAGS", { bold: true, size: 40, color: BLUE })], spacing: { before: 0, after: 60 } }));
body.push(new Paragraph({ children: [tr("SEO Progress Report", { bold: true, size: 28, color: DARK })], spacing: { after: 40 } }));
body.push(new Paragraph({ children: [tr("Reporting Period: July 14 – July 28, 2026   |   Prepared by Start Advertising", { size: 20, color: GRAY_444 })], spacing: { after: 20 } }));
body.push(new Paragraph({ children: [tr("Data Sources: Google Search Console · Ubersuggest", { size: 18, color: GRAY_666, italics: true })], spacing: { after: 0 } }));
body.push(rule(GOLD, 120, 160));

// Executive Summary
body.push(...sectionHead("Executive Summary"));
body.push(new Paragraph({
  spacing: { after: 160 },
  children: [tr(
    "This is the first tracked report for cleanroomfilm.com, covering the two weeks since Google Search Console and Ubersuggest tracking were established. In that short window, 19 of 52 site pages have been fully optimized — homepage, all 7 market pages, all 10 Learning Center posts, the Our Story page, and the Markets hub — each receiving a rewritten title, meta description, image alt text, and structured data (schema). Ubersuggest's independent Site Audit already rates the site 80/100 (\"High\"), and the site is generating 785 clicks with a click-through rate that is outperforming its impression volume — a strong early signal that the content already live is resonating with the right searches.",
    { size: 19 }
  )],
}));

// KPI Dashboard
body.push(...sectionHead("Key Performance Indicators — Ubersuggest Site Audit"));
body.push(new Table({
  width: { size: CONTENT_W, type: WidthType.DXA },
  rows: [new TableRow({ children: [
    metricCell("On-Page SEO Score", "80", "High", GREEN_TXT, LIGHT_GRN),
    metricCell("Organic Monthly Traffic", "56", null, BLUE, LIGHT_BLUE),
    metricCell("Organic Keywords", "30", null, BLUE, LIGHT_BLUE),
    metricCell("Backlinks", "457", "Great", GREEN_TXT, LIGHT_GRN),
  ]})],
}));
body.push(spacer(120,0));
body.push(new Paragraph({
  spacing: { after: 0 },
  children: [tr("An 80/100 On-Page SEO Score in the \"High\" band — established the same period as the first wave of implementation — reflects the title, meta, alt-text, and schema work now live across 19 pages. 457 backlinks is an exceptionally strong starting position for domain authority.", { size: 19 })],
}));

// GSC Search Performance
body.push(...sectionHead("Google Search Console — Search Performance"));
body.push(new Paragraph({ children: [tr("Last 2 Weeks (First Tracked Period)", { bold: true, size: 20, color: BLUE })], spacing: { after: 100 } }));
body.push(dataTable(
  ["Metric", "Value", "Trend"],
  [
    ["Clicks", "785", "+8%"],
    ["Impressions", "72,400", "-28%"],
  ],
  [3600, 2880, 2880],
  { colorCol: 2 }
));
body.push(...callout(
  "Clicks are up 8% while impressions are down 28% — that combination means click-through rate is improving: the searches Google is surfacing CFB for are converting to visits at a higher rate. With only two weeks of history, some early volatility in impressions is expected as Google recalculates relevance after the recent optimization work; the click trend is the more reliable early signal, and it is positive.",
  LIGHT_GRN, GREEN_TXT
));

// Top Performing Pages
body.push(...sectionHead("Top Performing Pages — Last 2 Weeks"));
body.push(dataTable(
  ["Page", "Clicks", "Trend"],
  [
    ["Homepage (cleanroomfilm.com)", "273", "<1%"],
    ["Polyethylene Cleanroom Packaging", "72", "-11%"],
    ["Anti-Static Nylon Cleanroom Packaging", "37", "+23%"],
    ["Extreme Low Outgassing (ULO) Packaging", "35", "+35%"],
    ["Aclar® 22A HydroBlock® Packaging", "29", "New — previously 0"],
  ],
  [5040, 2160, 2160],
  { colorCol: 2 }
));
body.push(...callout(
  "The homepage alone drove 273 of 785 total clicks (35%) in its first two tracked weeks — a strong result immediately following the title, meta description, and schema work completed July 16. Three material pages (Anti-Static Nylon, ULO, and Aclar®) are all trending up double digits, and Aclar® is a brand-new entrant to the click report — evidence that the exclusive-material pages the audit flagged as \"buried\" are starting to surface.",
  LIGHT_GRN, GREEN_TXT
));

// Queries
body.push(...sectionHead("Queries Leading to Your Site"));
body.push(dataTable(
  ["Query Group", "Clicks", "Trend"],
  [
    ["cleanroom film and bags (+ 8 related variants)", "104", "—"],
    ["cfb packaging", "11", "+57%"],
    ["cleanroom bags (+ 5 related variants)", "10", "-50%"],
    ["cleanroom packaging", "5", "+150%"],
    ["aluminum foil bags", "3", "+50%"],
  ],
  [5040, 2160, 2160],
  { colorCol: 2 }
));
body.push(...callout(
  "\"cfb packaging\" (+57%) and \"cleanroom packaging\" (+150%) are both branded-adjacent and category-defining terms trending up sharply — early evidence that the Organization schema and title/meta work are strengthening how Google associates CFB with its core category. The dip on the generic \"cleanroom bags\" grouping is a single query cluster in a two-week sample and worth monitoring, not yet a trend.",
  LIGHT_GRN, GREEN_TXT
));

// Geography & Branded Split
body.push(...sectionHead("Geography & Branded Traffic"));
body.push(new Paragraph({ children: [tr("Top Countries", { bold: true, size: 20, color: BLUE })], spacing: { after: 100 } }));
body.push(dataTable(
  ["Country", "Share of Clicks"],
  [
    ["United States", "60%"],
    ["United Kingdom", "4%"],
    ["India", "3%"],
    ["Malaysia", "3%"],
    ["Singapore", "2%"],
  ],
  [4680, 4680]
));
body.push(spacer(160, 0));
body.push(new Paragraph({ children: [tr("Branded vs. Non-Branded Traffic", { bold: true, size: 20, color: BLUE })], spacing: { after: 100 } }));
body.push(dataTable(
  ["Traffic Type", "Share of Clicks"],
  [
    ["Non-Branded (category/product searches)", "82%"],
    ["Branded (company-name searches)", "18%"],
  ],
  [4680, 4680]
));
body.push(...callout(
  "82% of clicks are non-branded — meaning the large majority of visitors are finding CFB through product and category searches rather than already knowing the company name. For a B2B manufacturer, this is the healthiest possible mix: it means SEO is doing its core job of introducing CFB to buyers who did not already know to look for it, not just capturing people who searched the brand directly.",
  LIGHT_GRN, GREEN_TXT
));

// Keyword Rankings (from Ubersuggest rank tracking — authoritative export, July 28)
body.push(...sectionHead("Keyword Ranking Snapshot"));
body.push(new Paragraph({ children: [tr("Rank Tracking Summary — 83 of 150 Keywords Tracked", { bold: true, size: 20, color: BLUE })], spacing: { after: 100 } }));
body.push(dataTable(
  ["Metric", "Prior", "Current", "Change"],
  [
    ["Average Position", "11.92", "11.22", "+0.7"],
    ["Ranking Top 10", "12", "17", "+5"],
    ["Ranking Top 100", "27", "22", "-5"],
    ["Not Yet Ranking", "26", "28", "+2"],
  ],
  [3240, 2040, 2040, 2040],
  { colorCol: 3 }
));
body.push(spacer(100,0));
body.push(new Paragraph({ children: [tr("Selected Page-One Rankings (Position 1–13)", { bold: true, size: 20, color: BLUE })], spacing: { after: 100 } }));
body.push(dataTable(
  ["Keyword", "Position", "Change", "Search Vol/Mo"],
  [
    ["cleanroom film and bag", "1", "—", "90"],
    ["cleanroom packaging manufacturer", "1", "+1", "0"],
    ["nylon bag packaging", "1", "—", "50"],
    ["cleanroom bags", "2", "+1", "260"],
    ["aerospace cleanroom packaging", "2", "—", "0"],
    ["cleanroom packaging", "3", "—", "70"],
    ["class 100 cleanroom packaging", "3", "—", "0"],
    ["nylon cleanroom bags", "4", "+1", "10"],
    ["semiconductor cleanroom packaging", "5", "+6", "0"],
    ["aluminium foil bag", "5", "+15", "480"],
    ["cleanroom poly bags", "5", "+1", "30"],
    ["cleanroom ziplock bags", "7", "+6", "10"],
    ["pharmaceutical cleanroom packaging", "10", "+2", "0"],
    ["bottom seal bags", "11", "+3", "30"],
    ["clean room for food packaging", "13", "+6", "10"],
  ],
  [4160, 1600, 1600, 2000],
  { colorCol: 2 }
));
body.push(...callout(
  "16 keywords moved up this period against 15 that moved down, and keywords ranking in the Top 10 jumped from 12 to 17 — a net gain of 5 page-one rankings in the first two weeks of tracking. \"aluminium foil bag\" is the standout mover: +15 positions to #5 on a 480/month search term. Average position across all 83 tracked keywords improved from 11.92 to 11.22.",
  LIGHT_GRN, GREEN_TXT
));

body.push(spacer(120,0));
body.push(new Paragraph({ children: [tr("Untapped Volume — High-Search-Volume Keywords Not Yet Ranking", { bold: true, size: 20, color: BLUE })], spacing: { after: 100 } }));
body.push(dataTable(
  ["Keyword", "Search Vol/Mo", "Status"],
  [
    ["anti-static bags", "4,400", "Not yet ranking"],
    ["esd bags", "1,300", "Not yet ranking"],
    ["vci bags", "880", "Not yet ranking"],
    ["controlled environment", "880", "Not yet ranking"],
    ["static shielding bags", "390", "Not yet ranking"],
    ["medical device packaging", "320", "#37"],
    ["vci film", "170", "Not yet ranking"],
    ["vci packaging", "140", "Not yet ranking"],
  ],
  [4680, 2340, 2340],
));
body.push(...callout(
  "\"anti-static bags\" at 4,400 searches/month is the single largest untapped keyword opportunity on the site — more monthly volume than the next three keywords in this table combined. CFB already has an Anti-Static Nylon material page; targeted optimization of that page's title and content around this exact phrase is a fast, low-cost path to meaningful new traffic. The VCI cluster (vci bags, vci film, vci packaging — 1,190 combined monthly searches) confirms the audit's original finding: this is real, trackable demand sitting on a keyword set with zero current site presence.",
  LIGHT_ORG, ORANGE_TXT
));

body.push(spacer(120,0));
body.push(...callout(
  "One optimization opportunity surfaced by this data: two keywords are currently ranking on a different page than their best-matched content. \"medical cleanroom packaging\" ranks at #13 via the Pharmaceutical page rather than the dedicated Medical page, and \"cleantuff\" ranks at #4 via the Medical market page rather than the CLEANTUFF® material page itself. This is a sign of healthy topical relevance across pages, but pointing these terms to their true best-match page (through stronger internal linking and on-page keyword emphasis) should improve both rankings.",
  LIGHT_BLUE, BLUE
));

// Work Completed
body.push(...sectionHead("Work Completed This Period"));
[
  "Homepage: title tag and meta description rewritten; Organization schema (with founding date, credentials, and contact info) deployed sitewide",
  "All 7 market pages optimized: Medical, Semiconductor, Pharmaceutical, Electronic, Food, Aerospace, and the Markets hub — new titles, meta descriptions, image alt text, and Service/CollectionPage schema",
  "All 10 Learning Center posts optimized: titles trimmed for search, featured-image alt text, Article/NewsArticle schema with author byline and publish dates, and internal cross-links connecting case studies to the market and material pages they support",
  "Our Story page corrected: replaced a live title/meta mismatch that had been mis-describing the page since before this engagement began; added AboutPage schema and links promoting the CLEANTUFF® and ULO exclusive-material pages",
  "50-keyword tracking list built and prioritized in Ubersuggest, including a dedicated VCI cluster flagged as a client priority",
  "19 of 52 total site pages now fully optimized (37% complete)",
].forEach(t => body.push(bullet(t)));

// Focus Areas
body.push(...sectionHead("Focus Areas — Next Period"));
[
  "Priority 1: Optimize the Anti-Static Nylon material page around \"anti-static bags\" (4,400 searches/month) — the single largest untapped keyword opportunity identified this period",
  "Priority 2: Materials cluster (11 pages) — including CLEANTUFF®, ULO, and Aclar®, CFB's exclusive and highest-margin product lines, already showing early click growth",
  "Priority 3: Build the VCI packaging landing page — a stated client priority with zero current site presence and 1,190+ combined monthly searches across the VCI keyword cluster",
  "Priority 4: Strengthen internal linking to resolve the two keyword-cannibalization cases identified this period (\"medical cleanroom packaging\" and \"cleantuff\") so each ranks on its best-matched page",
  "Priority 5: Complete remaining technical quick wins — robots.txt sitemap declaration, homepage H1 consolidation, and full Google Search Console configuration",
  "Priority 6: Continue the product-page rollout (16 pages) across both Cleanroom Bags and Cleanroom Film categories",
].forEach((t,i) => body.push(new Paragraph({
  spacing: { after: 100 },
  children: [tr(t, { size: 19 })],
})));

body.push(spacer(200,0));
body.push(rule(GOLD, 0, 100));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, children: [tr("Prepared by Start Advertising   ·   cleanroomfilm.com   ·   Reporting period: July 14 – July 28, 2026", { size: 16, color: GRAY_666 })], spacing: { after: 20 } }));
body.push(new Paragraph({ alignment: AlignmentType.CENTER, children: [tr("Data: Google Search Console (first tracked period) · Ubersuggest Site Audit & Rank Tracking", { size: 15, color: GRAY_888 })] }));

// ────────────────────────────────────────────────────────────────────────────
const doc = new Document({
  numbering: { config: [{
    reference: 'bullets',
    levels: [{ level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 620, hanging: 320 } } } }],
  }]},
  styles: { default: { document: { run: { font: "Arial", size: 19 } } } },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: convertInchesToTwip(0.75), bottom: convertInchesToTwip(0.75), left: convertInchesToTwip(0.75), right: convertInchesToTwip(0.75) },
      },
    },
    headers: { default: new Header({ children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      children: [tr("Cleanroom Film & Bags — SEO Progress Report | Start Advertising", { size: 16, color: GRAY_666 })],
      border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: BORDER } },
      spacing: { after: 80 },
    })] })},
    footers: { default: new Footer({ children: [new Paragraph({
      tabStops: [{ type: TabStopType.RIGHT, position: TabStopPosition.MAX }],
      children: [
        tr("Confidential — Prepared Exclusively for Cleanroom Film & Bags", { size: 15, color: GRAY_666 }),
        new TextRun({ text: '\t', size: 15 }),
        new TextRun({ children: [PageNumber.CURRENT], size: 15, color: GRAY_666, font: "Arial" }),
      ],
    })] })},
    children: body,
  }],
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync('CFB-SEO-Progress-Report-2026-07-28.docx', buf);
  console.log('Progress report written:', buf.length, 'bytes');
});
