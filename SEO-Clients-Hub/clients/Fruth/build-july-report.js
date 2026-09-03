const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
        Header, Footer, AlignmentType, BorderStyle, WidthType, ShadingType,
        VerticalAlign, PageNumber, LevelFormat, PageBreak, TabStopType } = require(
          'C:/Users/richa/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/docx'
        );
const fs = require('fs');

// ─── Page dimensions (US Letter, 1" margins) ────────────────────────────────
const CONTENT_W = 9360;

// ─── Colors ──────────────────────────────────────────────────────────────────
const BLUE       = "1F5C99";
const LIGHT_BLUE = "D6E4F0";
const MID_BLUE   = "BDD7EE";
const GREEN_TXT  = "217346";
const LIGHT_GRN  = "E2EFDA";
const RED_TXT    = "C00000";
const LIGHT_RED  = "FCE4D6";
const ORANGE_TXT = "BF5700";
const LIGHT_ORG  = "FFF2CC";
const BORDER     = "CCCCCC";
const WHITE      = "FFFFFF";
const DARK       = "262626";
const GRAY_444   = "444444";
const GRAY_666   = "666666";
const GRAY_888   = "888888";

// ─── Border helper ───────────────────────────────────────────────────────────
const b1   = (color = BORDER) => ({ style: BorderStyle.SINGLE, size: 1, color });
const bAll = (color = BORDER) => ({ top: b1(color), bottom: b1(color), left: b1(color), right: b1(color) });
const noBorder = () => ({
  top: { style: BorderStyle.NONE, size: 0, color: WHITE },
  bottom: { style: BorderStyle.NONE, size: 0, color: WHITE },
  left: { style: BorderStyle.NONE, size: 0, color: WHITE },
  right: { style: BorderStyle.NONE, size: 0, color: WHITE },
});
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

// ─── Callout box (full-width colored banner) ─────────────────────────────────
const callout = (text, bg=LIGHT_GRN, textColor=GREEN_TXT) => [
  spacer(120, 0),
  new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: [CONTENT_W],
    rows: [new TableRow({ children: [
      new TableCell({
        borders: bAll(textColor),
        shading: { fill: bg, type: ShadingType.CLEAR },
        margins: { top: 120, bottom: 120, left: 200, right: 200 },
        children: [new Paragraph({
          children: [tr(text, { bold: true, size: 20, color: textColor })],
          spacing: { before: 0, after: 0 },
        })],
      }),
    ]})],
  }),
  spacer(60, 0),
];

// ─── Rank table ──────────────────────────────────────────────────────────────
const RANK_COLS = [4560, 1080, 1200, 1080, 1440];

const rankRow = (keyword, pos, change, vol, sd, isHeader=false) => {
  const up   = !isHeader && change.startsWith('+');
  const down = !isHeader && (change.startsWith('-') && change !== "-");
  const steady = !isHeader && (change === "Steady" || change === "—");
  const bg   = isHeader ? BLUE : up ? LIGHT_GRN : down ? LIGHT_RED : WHITE;
  const txtC = isHeader ? WHITE : DARK;
  const chgC = isHeader ? WHITE : up ? GREEN_TXT : down ? RED_TXT : GRAY_666;

  const mkCell = (text, w, align=AlignmentType.LEFT, color=txtC, bold=isHeader) =>
    new TableCell({
      borders: bAll(BORDER),
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

// ─── 2-column issue/fix table ─────────────────────────────────────────────────
const ISSUE_COLS = [5760, 3600];

const issueRow = (issue, action, isHeader=false) => {
  const bg   = isHeader ? BLUE : WHITE;
  const txtC = isHeader ? WHITE : DARK;
  const mkCell = (text, w) =>
    new TableCell({
      borders: bAll(BORDER),
      width: { size: w, type: WidthType.DXA },
      shading: { fill: bg, type: ShadingType.CLEAR },
      margins: cellPadSm,
      children: [new Paragraph({
        spacing: { before: 0, after: 0 },
        children: [tr(text, { bold: isHeader, size: 18, color: txtC })],
      })],
    });
  return new TableRow({ children: [mkCell(issue, ISSUE_COLS[0]), mkCell(action, ISSUE_COLS[1])] });
};

// ─── Opportunity table ────────────────────────────────────────────────────────
const OPP_COLS = [6960, 1200, 1200];

const oppRow = (keyword, vol, sd, isHeader=false) => {
  const bg   = isHeader ? BLUE : WHITE;
  const txtC = isHeader ? WHITE : DARK;
  const mkCell = (text, w, align=AlignmentType.LEFT) =>
    new TableCell({
      borders: bAll(BORDER),
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
    mkCell(keyword, OPP_COLS[0]),
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
    spacing: { before: 140, after: 60 },
  });

// ─── Body paragraph ──────────────────────────────────────────────────────────
const para = (text, before=60, after=80) =>
  new Paragraph({ children: [tr(text)], spacing: { before, after } });

const note = (text) =>
  new Paragraph({
    children: [tr(text, { size: 17, color: GRAY_888 })],
    spacing: { before: 40, after: 40 },
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
            tr("Fruth.com  |  SEO Progress Report  |  July 2026", { size: 18, color: GRAY_666 }),
            tr("\t\tPrepared by: Start Advertising", { size: 18, color: GRAY_666 }),
          ],
          tabStops: [{ type: TabStopType.RIGHT, position: CONTENT_W }],
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

      // ══════════════════════════════════════════════════════════════════════
      // PAGE 1 — EXECUTIVE SUMMARY
      // ══════════════════════════════════════════════════════════════════════

      new Paragraph({
        children: [tr("Fruth.com", { bold: true, size: 56, color: BLUE })],
        spacing: { before: 200, after: 80 },
      }),
      new Paragraph({
        children: [tr("SEO Progress Report", { bold: true, size: 40, color: DARK })],
        spacing: { before: 0, after: 60 },
      }),
      new Paragraph({
        children: [tr("Reporting Period: May 27, 2026 – July 14, 2026", { size: 22, color: GRAY_444 })],
        spacing: { before: 0, after: 40 },
      }),
      new Paragraph({
        children: [tr("Prepared by Start Advertising  |  Source: Ubersuggest Rank Tracking + Site Audit", { size: 20, color: GRAY_666 })],
        spacing: { before: 0, after: 0 },
      }),
      rule(BLUE, 80, 0),

      // Executive Summary
      ...sectionHead("Executive Summary"),
      para(
        "Over the past 47 days, a systematic content expansion program has produced measurable, compounding results for Fruth.com. " +
        "Twenty-six product pages were rewritten with B2B-focused copy, FAQ sections, and structured FAQ schema markup -- directly " +
        "increasing the volume of content Google can crawl and index. The results are clear: we have increased content, we are seeing " +
        "more organic traffic (+30.5%), we are seeing more keywords ranking in Google (+87.9%), and our SEO scores have increased by " +
        "2.6% (76 to 78 out of 100)."
      ),
      para(
        "The most striking signal is keyword velocity. Organic keywords tracked by Google have nearly doubled -- from approximately " +
        "33 to 62 -- in just 47 days. This reflects Google actively discovering and indexing Fruth product pages that previously had " +
        "too little content to rank. As implementation of the remaining pages continues, this upward trend is expected to accelerate."
      ),

      // Dashboard Snapshot
      ...sectionHead("Dashboard Snapshot -- July 14, 2026"),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: [2340, 2340, 2340, 2340],
        rows: [new TableRow({ children: [
          metricCell("On-Page SEO Score",    "78 / 100",  "▲ +2.6% vs. May 27",  BLUE,      LIGHT_BLUE),
          metricCell("Organic Traffic",      "154",       "▲ +30.5% vs. May 27", GREEN_TXT, LIGHT_GRN),
          metricCell("Organic Keywords",     "62",        "▲ +87.9% vs. May 27", GREEN_TXT, LIGHT_GRN),
          metricCell("SEO Issues Resolved",  "-14.5%",    "76 → 65 open issues",  BLUE,      LIGHT_BLUE),
        ]})],
      }),
      spacer(100, 0),

      // Standout win callout
      ...callout(
        "★  Standout Win: \"cleanroom packaging\" jumped +80 positions to rank #20 on Google -- " +
        "appearing on page two after previously being completely invisible to search engines. " +
        "\"autoclavable bags\" moved up +11 positions to #9, putting it on page one.",
        LIGHT_GRN,
        GREEN_TXT
      ),

      // Keyword ranking summary mini-table
      ...sectionHead("Keyword Ranking Summary -- May 27 to July 14, 2026"),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: RANK_COLS,
        rows: [
          rankRow("Keyword",                        "Position", "Change",  "Vol/Mo", "Difficulty", true),
          rankRow("fruth custom packaging",          "1",        "Steady",  "210",    "30"),
          rankRow("bottom seal bags",                "3",        "Steady",  "30",     "11"),
          rankRow("autoclaving bags",                "6",        "+1",      "880",    "14"),
          rankRow("autoclave bags",                  "8",        "Steady",  "880",    "16"),
          rankRow("autoclavable bags",               "9",        "+11",     "880",    "16"),
          rankRow("plastic bags for plants",         "11",       "+4",      "170",    "33"),
          rankRow("plastic plant bags",              "13",       "-1",      "170",    "27"),
          rankRow("plastic grow bag",                "17",       "+1",      "210",    "31"),
          rankRow("cleanroom packaging",             "20",       "+80",     "590",    "36"),
          rankRow("polypropylene grow bag",          "21",       "+6",      "480",    "21"),
          rankRow("polyethylene film manufacturer",  "28",       "Steady",  "170",    "20"),
          rankRow("polyethylene film manufacturers", "31",       "-4",      "170",    "27"),
        ],
      }),
      note("Vol/Mo = avg. monthly searches (US)  |  Difficulty = Ubersuggest SEO Difficulty (0-100; lower = easier to rank)"),
      note("Change shown vs. May 27, 2026 baseline."),

      // PAGE BREAK
      new Paragraph({ children: [new PageBreak()] }),

      // ══════════════════════════════════════════════════════════════════════
      // PAGE 2+ — DETAILED REPORT
      // ══════════════════════════════════════════════════════════════════════

      // New Keywords Now Appearing
      ...sectionHead("New Keywords Now Appearing in Google"),
      para(
        "In addition to the 12 tracked keywords above, the following keywords are now being detected in Ubersuggest " +
        "rank tracking -- meaning Google has begun indexing Fruth pages for these terms. Many are fluctuating as " +
        "Google evaluates the newly published content. These will stabilize and rise as pages gain age and authority."
      ),

      // Two-column layout for emerging keywords
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: [4680, 4680],
        rows: [
          new TableRow({ children: [
            new TableCell({
              borders: bAll(BORDER),
              shading: { fill: BLUE, type: ShadingType.CLEAR },
              margins: cellPadSm,
              children: [new Paragraph({ children: [tr("Keyword (Emerging)", { bold: true, size: 19, color: WHITE })], spacing: { before: 0, after: 0 } })],
            }),
            new TableCell({
              borders: bAll(BORDER),
              shading: { fill: BLUE, type: ShadingType.CLEAR },
              margins: cellPadSm,
              children: [new Paragraph({ children: [tr("Keyword (Emerging)", { bold: true, size: 19, color: WHITE })], spacing: { before: 0, after: 0 } })],
            }),
          ]}),
          ...[
            ["lay flat bags",                   "custom poly bags"],
            ["cleanroom poly bags",             "ESD packaging"],
            ["tamper evident bags",             "lip and tape bags"],
            ["heavy duty plastic bags",         "zipper bags"],
            ["medical packaging bags",          "cleanroom bags"],
            ["black conductive bags",           "moisture barrier film"],
            ["custom packaging supplier",       "anti static bags"],
            ["VCI plastic packaging",           "food safe plastic bags"],
            ["sterilization packaging bags",    "gusset of bag"],
            ["electronics packaging materials", "nylon packaging"],
          ].map(([left, right]) => new TableRow({ children: [
            new TableCell({
              borders: bAll(BORDER),
              shading: { fill: LIGHT_BLUE, type: ShadingType.CLEAR },
              margins: cellPadSm,
              children: [new Paragraph({ children: [tr(left, { size: 18, color: DARK })], spacing: { before: 0, after: 0 } })],
            }),
            new TableCell({
              borders: bAll(BORDER),
              shading: { fill: LIGHT_BLUE, type: ShadingType.CLEAR },
              margins: cellPadSm,
              children: [new Paragraph({ children: [tr(right, { size: 18, color: DARK })], spacing: { before: 0, after: 0 } })],
            }),
          ]})),
        ],
      }),
      spacer(60, 0),
      note("These keywords were not visible in May 27 tracking. Their appearance confirms Google is actively crawling and indexing the expanded product pages."),

      // Work Completed
      ...sectionHead("Work Completed This Period"),
      subHead("Content Expansion -- 26 Product Pages", BLUE),
      para(
        "Every key product and barrier film page on Fruth.com was rewritten with B2B-focused, keyword-rich copy. " +
        "Each page received a structured BEFORE / AFTER content upgrade -- replacing thin, generic descriptions with " +
        "detailed product specifications, applications, and industry-specific language that Google can rank."
      ),
      bullet("Bags Hub (category page) -- rewritten to anchor the entire bags product section", true),
      bullet("Individual bag pages (14): Autoclave, Bakery, Bottom Seal, Cleanroom, Foam, Fresh Produce, Grow, Gusset, Header, Lay Flat, Lip & Tape, Multi-Pocket, Side Seal, Square Bottom"),
      bullet("Additional bag pages (7): Tamper Evident, Vacuum Seal, VCI Bags, Wicketed, Zipper, Header, and specialty variants"),
      bullet("Barrier Films (5): Black Conductive Film, FFP Barrier Film, Kraft Foil Barrier Film, Scrim Foil Barrier Film, MIL-PRF-131K Barrier Film"),
      bullet("Films (3): Anti-Static Film, Flame Retardant PE Film, Nuclear Green PE Film, Nylon Film, Polypropylene Film"),

      subHead("FAQ Schema (JSON-LD) -- All 26 Pages", BLUE),
      para(
        "Structured FAQ schema was added to every product page, making Fruth eligible for Google FAQ rich results -- " +
        "expanded search listings that display questions and answers directly in Google's search results page, " +
        "increasing visibility and click-through rate without requiring a higher ranking position."
      ),
      bullet("Organization Schema also added to the homepage for brand entity recognition"),
      bullet("Schema follows Google's recommended JSON-LD format embedded via HubSpot custom HTML"),

      subHead("HubSpot Implementation -- 14 Pages Live", BLUE),
      para(
        "Content and schema have been implemented in HubSpot for 14 pages as of July 14, 2026. " +
        "Implementation is ongoing; remaining pages will be completed in the next 1-2 weeks."
      ),
      bullet("Implemented (14): Autoclave Bags (7/7), Bakery Bags (7/7), Bags Hub (7/7), Black Conductive Film (7/7), " +
             "Bottom Seal Bags (7/7), Cleanroom Bags (7/7), FFP Barrier Film (7/8), Foam Bags (7/8), " +
             "Fresh Produce Bags (7/8), Grow Bags (7/8), Gusset Bags (7/8), Header Bags (7/8), " +
             "Kraft Foil Barrier Film (7/13), Lay Flat Bags (7/13)", true),
      bullet("Pending (12): Lip & Tape Bags, Multi-Pocket Bags, Side Seal Bags, Square Bottom Bags, Tamper Evident Bags, " +
             "Vacuum Seal Bags, VCI Bags, Wicketed Bags, Zipper Bags, Scrim Foil Film, MIL-PRF-131K Film, Homepage"),

      // Technical Issues
      ...sectionHead("Open Technical Issues -- Site Audit"),
      para(
        "The July site audit identified the following issues to address. Low word count pages are actively being resolved " +
        "through the content expansion implementation. The items below require focused attention."
      ),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: ISSUE_COLS,
        rows: [
          issueRow("Issue", "Recommended Action", true),
          issueRow(
            "Duplicate title tag + meta description: lay-flat-bags and lip-and-tape-bags share identical title (\"Lip & Tape Bags | Resealable Protective Packaging Bags\") and identical meta description",
            "Priority fix: update Lay Flat Bags title and meta to be unique. Suggested title: \"Lay Flat Bags | Clear Polyethylene Tubing & Custom Sizes\""
          ),
          issueRow(
            "No H1 tag: 3 pages missing H1 heading -- /capabilities, /fruth-360, /our-story",
            "Add a single, keyword-relevant H1 to each page. Easy fix, medium SEO impact."
          ),
          issueRow(
            "Title tag too long: Learning Center article title exceeds 65 characters -- \"Fruth Custom Packaging Adds Capacity to Serve Increasing Demand for Biohazard Bags and Autoclave Bags\"",
            "Shorten to under 65 characters. Suggested: \"Fruth Adds Capacity for Biohazard & Autoclave Bag Demand\""
          ),
          issueRow(
            "Non-SEO-friendly URLs: /fruth-360, /products, /products/films flagged (keyword mismatch with page content)",
            "Low priority -- URL changes risk broken links. Consider in a future site restructure only."
          ),
          issueRow(
            "Temporary redirects (37 instances): all are ?hsLang=en parameter URLs (HubSpot language detection)",
            "Low priority -- these are HubSpot's language-detection redirects, not true redirect issues. No action needed."
          ),
          issueRow(
            "Low word count: 29 pages still below recommended threshold",
            "Actively being resolved through content expansion implementation. Expected to drop significantly as remaining 12 pages go live."
          ),
        ],
      }),

      // Growth Opportunities
      ...sectionHead("Growth Opportunities -- High-Value Keywords to Target Next"),
      para(
        "With the content expansion now live and Google beginning to index product pages, the following keywords " +
        "represent the strongest next-phase opportunities. Selected for meaningful search volume and achievable difficulty."
      ),
      new Table({
        width: { size: CONTENT_W, type: WidthType.DXA },
        columnWidths: OPP_COLS,
        rows: [
          oppRow("Keyword",                           "Monthly Vol", "Difficulty", true),
          oppRow("vacuum seal bags",                  "49,500",      "42"),
          oppRow("zipper bags",                       "5,400",       "38"),
          oppRow("anti static bags",                  "4,400",       "30"),
          oppRow("anti static packaging",             "4,400",       "29"),
          oppRow("poly bag manufacturer",             "1,900",       "18"),
          oppRow("gusseted bags",                     "1,900",       "25"),
          oppRow("tamper evident bags",               "590",         "16"),
          oppRow("cleanroom packaging bags",          "590",         "28"),
          oppRow("wicketed bags",                     "390",         "14"),
          oppRow("static shielding bags",             "390",         "33"),
          oppRow("moisture barrier bags",             "260",         "22"),
          oppRow("custom poly bags",                  "260",         "29"),
          oppRow("lay flat tubing",                   "210",         "20"),
          oppRow("VCI packaging",                     "880",         "31"),
        ],
      }),
      spacer(60, 0),
      note(
        "Priority picks: 'tamper evident bags' (590/mo, difficulty 16), 'wicketed bags' (390/mo, difficulty 14), " +
        "and 'poly bag manufacturer' (1,900/mo, difficulty 18) offer the best effort-to-reward ratio. " +
        "These pages are already written -- implementation in HubSpot will unlock their ranking potential."
      ),

      // Recommendations
      ...sectionHead("Recommendations -- Next 30-90 Days"),

      subHead("Quick Wins (This Week)", BLUE),
      bullet("Fix duplicate title + meta: Lay Flat Bags and Lip & Tape Bags must have unique titles and meta descriptions immediately -- this is a direct ranking conflict.", true),
      bullet("Complete HubSpot implementation for remaining 12 pages -- each page live = more keywords indexed, more traffic opportunity."),
      bullet("Add H1 tags to /capabilities, /fruth-360, and /our-story -- easy fix, removes a medium-impact audit flag."),
      bullet("Shorten the Learning Center article title to under 65 characters."),

      subHead("Medium Term (2-4 Weeks)", BLUE),
      bullet("Connect Google Analytics to Ubersuggest -- unlocks real session data, user behavior, landing page performance, and conversion tracking.", true),
      bullet("Build out the EMI Static Shielding Barrier Film page -- currently 149 words. Add content expansion + schema matching other barrier film pages."),
      bullet("Monitor 'cleanroom packaging' rank weekly -- it's at #20 and climbing. A targeted internal link from the Cleanroom Bags page will accelerate it toward page one."),
      bullet("Create a dedicated VCI / Corrosion Protection category page targeting 'VCI bags,' 'VCI packaging,' and 'volatile corrosion inhibitor bags' (combined ~2,000 searches/mo)."),

      subHead("Strategic (1-3 Months)", BLUE),
      bullet("Begin link-building outreach to packaging industry directories and B2B trade publications -- backlinks push competitive keywords above position 10.", true),
      bullet("Develop application-focused Learning Center articles (e.g., 'What Autoclave Bag Material Is Right for Your Sterilization Process?') to build topical authority and capture informational searches."),
      bullet("Activate or expand Google Business Profile to capture local packaging searches and strengthen E-E-A-T trust signals."),
      bullet("Establish a monthly Ubersuggest reporting cadence: pull rank tracking + site audit on the same date each month for clean, comparable trend data."),

      spacer(200),
      rule(BORDER, 80, 80),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [tr(
          "Report prepared by Start Advertising  •  Data source: Ubersuggest  •  Period: May 27 – July 14, 2026",
          { size: 17, color: GRAY_888 }
        )],
        spacing: { before: 0, after: 0 },
      }),
    ],
  }],
});

const OUT = "C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Fruth/Fruth-SEO-Progress-Report-2026-07-14.docx";

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(OUT, buf);
  console.log("SUCCESS:", OUT);
}).catch(err => {
  console.error("FAILED:", err.message);
  process.exit(1);
});
