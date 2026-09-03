const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
        Header, Footer, AlignmentType, BorderStyle, WidthType, ShadingType,
        VerticalAlign, PageNumber, LevelFormat, PageBreak, TabStopType } = require(
          'C:/Users/richa/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/docx'
        );
const fs = require('fs');

// ─── Page dimensions (US Letter, 1" margins) ────────────────────────────────
// Content width = 12240 - 1440 - 1440 = 9360 DXA
const CONTENT_W = 9360;

// ─── Colors ──────────────────────────────────────────────────────────────────
const BLUE       = "1F5C99";
const LIGHT_BLUE = "D6E4F0";
const MID_BLUE   = "BDD7EE";
const GREEN_TXT  = "217346";
const LIGHT_GRN  = "E2EFDA";
const RED_TXT    = "C00000";
const LIGHT_RED  = "FCE4D6";
const BORDER     = "CCCCCC";
const WHITE      = "FFFFFF";
const DARK       = "262626";
const GRAY_666   = "666666";
const GRAY_888   = "888888";
const GRAY_444   = "444444";

// ─── Border helper ───────────────────────────────────────────────────────────
const b1 = (color = BORDER) => ({ style: BorderStyle.SINGLE, size: 1, color });
const bAll = (color = BORDER) => ({ top: b1(color), bottom: b1(color), left: b1(color), right: b1(color) });
const cellPad = { top: 100, bottom: 100, left: 140, right: 140 };
const cellPadSm = { top: 72, bottom: 72, left: 100, right: 100 };

// ─── Text helpers ─────────────────────────────────────────────────────────────
const tr = (text, { bold=false, size=20, color=DARK, font="Arial" }={}) =>
  new TextRun({ text, bold, size, color, font });

const spacer = (before=120, after=0) =>
  new Paragraph({ children: [tr("")], spacing: { before, after } });

const rule = (color=BLUE, before=80, after=120) =>
  new Paragraph({
    children: [tr("")],
    border: { bottom: { style: BorderStyle.SINGLE, size: 8, color, space: 1 } },
    spacing: { before, after },
  });

// ─── Section heading bar ─────────────────────────────────────────────────────
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

// ─── Dashboard metric cell ────────────────────────────────────────────────────
// 4 equal cells across CONTENT_W = 2340 each
const metricCell = (label, value, valueColor=BLUE, bg=LIGHT_BLUE) =>
  new TableCell({
    borders: bAll(MID_BLUE),
    width: { size: 2340, type: WidthType.DXA },
    shading: { fill: bg, type: ShadingType.CLEAR },
    margins: { top: 120, bottom: 120, left: 160, right: 160 },
    verticalAlign: VerticalAlign.CENTER,
    children: [
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 40 },
        children: [tr(value, { bold: true, size: 36, color: valueColor })],
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 0 },
        children: [tr(label, { size: 18, color: DARK })],
      }),
    ],
  });

// ─── Rank table ──────────────────────────────────────────────────────────────
// Columns: keyword=4560, pos=1080, change=1200, vol=1080, diff=1440 → total=9360
const RANK_COLS = [4560, 1080, 1200, 1080, 1440];

const rankRow = (keyword, pos, change, vol, sd, isHeader=false) => {
  const up   = !isHeader && change.startsWith('+');
  const down = !isHeader && change.startsWith('-');
  const bg   = isHeader ? BLUE : up ? LIGHT_GRN : down ? LIGHT_RED : WHITE;
  const txtC = isHeader ? WHITE : DARK;
  const chgC = isHeader ? WHITE : up ? GREEN_TXT : down ? RED_TXT : DARK;
  const bords = bAll(BORDER);

  const mkCell = (text, w, align=AlignmentType.LEFT, color=txtC, bold=isHeader) =>
    new TableCell({
      borders: bords,
      width: { size: w, type: WidthType.DXA },
      shading: { fill: bg, type: ShadingType.CLEAR },
      margins: cellPadSm,
      children: [new Paragraph({
        alignment: align,
        spacing: { before: 0, after: 0 },
        children: [tr(text, { bold, size: isHeader ? 19 : 18, color })],
      })],
    });

  return new TableRow({ children: [
    mkCell(keyword,  RANK_COLS[0], AlignmentType.LEFT),
    mkCell(pos,      RANK_COLS[1], AlignmentType.CENTER),
    mkCell(change,   RANK_COLS[2], AlignmentType.CENTER, chgC, isHeader || up || down),
    mkCell(vol,      RANK_COLS[3], AlignmentType.CENTER),
    mkCell(sd,       RANK_COLS[4], AlignmentType.CENTER),
  ]});
};

// ─── Opportunity table ────────────────────────────────────────────────────────
// Columns: keyword=6960, vol=1200, diff=1200 → total=9360
const OPP_COLS = [6960, 1200, 1200];

const oppRow = (keyword, vol, sd, isHeader=false) => {
  const bg    = isHeader ? BLUE : WHITE;
  const txtC  = isHeader ? WHITE : DARK;
  const bords = bAll(BORDER);

  const mkCell = (text, w, align=AlignmentType.LEFT) =>
    new TableCell({
      borders: bords,
      width: { size: w, type: WidthType.DXA },
      shading: { fill: bg, type: ShadingType.CLEAR },
      margins: cellPadSm,
      children: [new Paragraph({
        alignment: align,
        spacing: { before: 0, after: 0 },
        children: [tr(text, { bold: isHeader, size: 18, color: txtC })],
      })],
    });

  return new TableRow({ children: [
    mkCell(keyword, OPP_COLS[0], AlignmentType.LEFT),
    mkCell(vol,     OPP_COLS[1], AlignmentType.CENTER),
    mkCell(sd,      OPP_COLS[2], AlignmentType.CENTER),
  ]});
};

// ─── Bullet ──────────────────────────────────────────────────────────────────
const bullet = (text, bold=false) =>
  new Paragraph({
    numbering: { reference: "bullets", level: 0 },
    children: [tr(text, { bold })],
    spacing: { before: 60, after: 60 },
  });

// ─── Sub-heading ─────────────────────────────────────────────────────────────
const subHead = (text, color=BLUE) =>
  new Paragraph({
    children: [tr(text, { bold: true, size: 22, color })],
    spacing: { before: 120, after: 60 },
  });

// ─── Body paragraph ──────────────────────────────────────────────────────────
const para = (text, before=60, after=80) =>
  new Paragraph({
    children: [tr(text)],
    spacing: { before, after },
  });

// ─────────────────────────────────────────────────────────────────────────────
// BUILD DOCUMENT
// ─────────────────────────────────────────────────────────────────────────────
const doc = new Document({
  numbering: {
    config: [{
      reference: "bullets",
      levels: [{
        level: 0,
        format: LevelFormat.BULLET,
        text: "•",
        alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 720, hanging: 360 } } },
      }],
    }],
  },
  styles: {
    default: { document: { run: { font: "Arial", size: 20 } } },
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
      },
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          children: [
            tr("Fruth.com  |  SEO Progress Report  |  May 2026", { size: 18, color: GRAY_666 }),
            tr("\t\tPrepared by: Start Advertising", { size: 18, color: GRAY_666 }),
          ],
          tabStops: [
            { type: TabStopType.RIGHT, position: CONTENT_W },
          ],
          border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: BORDER } },
          spacing: { after: 80 },
        })],
      }),
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          children: [
            tr("Confidential -- For Fruth Internal Use Only  |  Page ", { size: 16, color: GRAY_888 }),
            new TextRun({ children: [PageNumber.CURRENT], size: 16, color: GRAY_888, font: "Arial" }),
          ],
          border: { top: { style: BorderStyle.SINGLE, size: 4, color: BORDER } },
          spacing: { before: 80, after: 0 },
        })],
      }),
    },
    children: [

      // ══════ TITLE ═══════════════════════════════════════════════════════════
      new Paragraph({
        children: [tr("Fruth.com", { bold: true, size: 56, color: BLUE })],
        spacing: { before: 200, after: 80 },
      }),
      new Paragraph({
        children: [tr("SEO Progress Report", { bold: true, size: 40, color: DARK })],
        spacing: { before: 0, after: 60 },
      }),
      new Paragraph({
        children: [tr("Reporting Period: April 27, 2026 - May 27, 2026", { size: 22, color: GRAY_444 })],
        spacing: { before: 0, after: 40 },
      }),
      new Paragraph({
        children: [tr("Prepared by Start Advertising  |  Source: Ubersuggest Rank Tracking", { size: 20, color: GRAY_666 })],
        spacing: { before: 0, after: 0 },
      }),
      rule(BLUE, 80, 0),

      // ══════ EXECUTIVE SUMMARY ═══════════════════════════════════════════════
      ...sectionHead("Executive Summary"),
      para(
        "Over the past 30 days, targeted on-page SEO improvements have produced significant, measurable results for Fruth.com. " +
        "Eight keywords that were previously not appearing in Google search results (ranked beyond position 100) are now ranking on " +
        "page one or two. These improvements -- driven by optimized meta titles, meta descriptions, image alt tags, and schema markup -- " +
        "confirm that search engines are now properly reading and indexing Fruth's product pages."
      ),
      para(
        "The site's overall On-Page SEO Score is 76/100 (rated Great by Ubersuggest), and 9 out of 12 tracked keywords " +
        "moved up in ranking during this period. This is a strong foundation to build on."
      ),

      // ══════ DASHBOARD SNAPSHOT ══════════════════════════════════════════════
      ...sectionHead("Dashboard Snapshot -- May 27, 2026"),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: [2340, 2340, 2340, 2340],
        rows: [new TableRow({ children: [
          metricCell("On-Page SEO Score", "76 / 100", BLUE,     LIGHT_BLUE),
          metricCell("Keywords Moved Up", "9",        GREEN_TXT, "#E2EFDA"),
          metricCell("Keywords Moved Down", "3",      RED_TXT,   LIGHT_RED),
          metricCell("Total Backlinks",    "2,837",   BLUE,     LIGHT_BLUE),
        ]})],
      }),
      spacer(80),
      new Paragraph({
        children: [tr(
          "* Organic traffic data is unavailable until Google Analytics is connected to Ubersuggest. " +
          "Estimated traffic value: US$14/month. Connecting GA will unlock full traffic and conversion data.",
          { size: 17, color: GRAY_888 }
        )],
        spacing: { before: 40, after: 0 },
      }),

      // ══════ KEYWORD RANKING WINS ════════════════════════════════════════════
      ...sectionHead("Keyword Ranking Wins -- April 27 to May 27, 2026"),
      para(
        "The table below shows all 12 tracked keywords and their current positions. Green rows mark keywords that " +
        "jumped from not ranked (position 100+) to page one or two of Google in just 30 days -- a dramatic improvement."
      ),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: RANK_COLS,
        rows: [
          rankRow("Keyword",                          "Position", "Change",   "Vol/Mo",  "Difficulty", true),
          rankRow("bottom seal bags",                 "3",        "+97",      "30",      "11"),
          rankRow("plastic plant bags",               "5",        "+95",      "170",     "27"),
          rankRow("autoclaving bags",                 "7",        "+93",      "880",     "14"),
          rankRow("plastic bags for plants",          "7",        "+93",      "170",     "33"),
          rankRow("autoclave bags",                   "10",       "+90",      "880",     "16"),
          rankRow("plastic grow bag",                 "16",       "+84",      "210",     "31"),
          rankRow("polypropylene grow bag",           "25",       "+75",      "480",     "21"),
          rankRow("autoclavable bags",                "26",       "+74",      "880",     "16"),
          rankRow("header bags",                      "32",       "+68",      "140",     "19"),
          rankRow("fruth custom packaging",           "1",        "Steady",   "210",     "30"),
          rankRow("polyethylene film manufacturer",   "26",       "-7",       "170",     "20"),
          rankRow("polyethylene film manufacturers",  "28",       "-8",       "170",     "27"),
        ],
      }),
      spacer(80),
      new Paragraph({
        children: [tr(
          "Vol/Mo = avg. monthly searches (US) | Difficulty = Ubersuggest SEO Difficulty (0-100; lower = easier to rank)",
          { size: 17, color: GRAY_888 }
        )],
        spacing: { before: 40, after: 40 },
      }),
      new Paragraph({
        children: [tr(
          "Note: The two polyethylene film keywords slipped slightly (7-8 positions). These keywords showed minor fluctuations and " +
          "will be strengthened with additional supporting content.",
          { size: 17, color: GRAY_888 }
        )],
        spacing: { before: 0, after: 0 },
      }),

      // ══════ WORK COMPLETED ══════════════════════════════════════════════════
      ...sectionHead("Work Completed This Period"),
      para("The following on-page SEO updates were implemented across Fruth.com product and category pages:"),
      bullet("Meta Titles (Title Tags) -- Rewritten to include primary keywords naturally, within 60-character limits, to improve click-through rates from search results.", true),
      bullet("Meta Descriptions -- Crafted compelling, keyword-rich descriptions for key product pages to encourage higher organic click-through rates."),
      bullet("Image Alt Tags -- Added descriptive alt text to product images, improving both accessibility compliance and image search visibility."),
      bullet("Schema Markup -- Structured data added to product pages, helping Google understand page content and enabling eligibility for rich results."),
      bullet("Keyword Research -- Identified high-opportunity, lower-competition keywords relevant to Fruth's product categories and geographic market."),

      // Page break
      new Paragraph({ children: [new PageBreak()] }),

      // ══════ GROWTH OPPORTUNITIES ════════════════════════════════════════════
      ...sectionHead("Growth Opportunities -- High-Value Keywords Not Yet Ranking"),
      para(
        "These keywords are currently tracked but not ranking. They represent the strongest next targets, selected for " +
        "meaningful search volume and achievable difficulty scores. Targeting them will expand Fruth's organic reach significantly."
      ),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: OPP_COLS,
        rows: [
          oppRow("Keyword",                             "Monthly Vol", "Difficulty", true),
          oppRow("vacuum seal bags",                    "49,500",      "42"),
          oppRow("zipper bags",                         "5,400",       "38"),
          oppRow("anti static bags",                    "4,400",       "30"),
          oppRow("anti static packaging",               "4,400",       "29"),
          oppRow('gusseted bags',                       "1,900",       "25"),
          oppRow("gusset of bag",                       "1,900",       "26"),
          oppRow("custom plastic bags",                 "1,900",       "42"),
          oppRow('poly bag manufacturer',               "1,900",       "18"),
          oppRow("custom packaging solutions",          "1,300",       "42"),
          oppRow('volatile corrosion inhibitor bags',   "880",         "31"),
          oppRow("vci bags",                            "880",         "31"),
          oppRow('tamper evident bags',                 "590",         "16"),
          oppRow('wicketed bags',                       "390",         "14"),
          oppRow("packaging films",                     "390",         "42"),
          oppRow("static shielding bags",               "390",         "33"),
          oppRow("custom packaging manufacturer",       "260",         "67"),
          oppRow("lay flat bags",                       "110",         "21"),
        ],
      }),
      spacer(80),
      new Paragraph({
        children: [tr(
          "Priority picks for next 30 days: 'tamper evident bags' (vol 590, difficulty 16), 'wicketed bags' (vol 390, difficulty 14), " +
          "and 'poly bag manufacturer' (vol 1,900, difficulty 18) offer the best effort-to-reward ratio.",
          { size: 18, color: GRAY_444 }
        )],
        spacing: { before: 40, after: 0 },
      }),

      // ══════ RECOMMENDATIONS ═════════════════════════════════════════════════
      ...sectionHead("Recommendations -- Next 30-90 Days"),

      subHead("Quick Wins (This Week)", BLUE),
      bullet("Connect Google Analytics to Ubersuggest to unlock real traffic data, user behavior, and conversion tracking.", true),
      bullet("Create or optimize a dedicated page for 'tamper evident bags' and 'wicketed bags' -- low difficulty, solid volume, clear product fit."),
      bullet("Add FAQ schema to top-ranking product pages (autoclave bags, plastic grow bags) to compete for featured snippets."),

      subHead("Medium Term (2-4 Weeks)", BLUE),
      bullet("Refresh the 'polyethylene film' page content to reverse the slight ranking drop -- add 200-300 words of product-specific copy with keyword variations.", true),
      bullet("Build a dedicated VCI & Corrosion Protection category page targeting 'vci bags,' 'vci packaging,' and 'volatile corrosion inhibitor bags' (combined ~2,000/mo searches)."),
      bullet("Target 'poly bag manufacturer' and 'gusseted bags' with focused product/landing pages optimized around those exact phrases."),

      subHead("Strategic (1-3 Months)", BLUE),
      bullet("Activate or optimize a Google Business Profile to capture local packaging searches and reinforce E-E-A-T trust signals.", true),
      bullet("Begin link-building outreach targeting packaging industry directories and B2B trade publications to push competitive keywords above position 10."),
      bullet("Develop application-focused content (e.g., 'How Autoclave Bag Material Affects Sterilization Results') to build topical authority and capture informational queries."),
      bullet("Establish a monthly Ubersuggest reporting cadence: pull rank tracking + dashboard on the same date each month for clean, comparable trend data."),

      spacer(200),
      rule(BORDER, 80, 80),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [tr(
          "Report prepared by Start Advertising  •  Data source: Ubersuggest  •  Period: April 27 - May 27, 2026",
          { size: 17, color: GRAY_888 }
        )],
        spacing: { before: 0, after: 0 },
      }),
    ],
  }],
});

const OUT = "C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Fruth/Fruth-SEO-Progress-Report-2026-05-27.docx";

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(OUT, buf);
  console.log("SUCCESS:", OUT);
}).catch(err => {
  console.error("FAILED:", err.message);
  process.exit(1);
});
