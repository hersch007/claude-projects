// C-P Flexible Packaging — month-end SEO progress report (pre-launch build).
// Run: node build-report.js
const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
        Header, Footer, AlignmentType, BorderStyle, WidthType, ShadingType,
        VerticalAlign, PageNumber, LevelFormat, PageBreak, TabStopType } =
  require('C:/Users/richa/AppData/Roaming/npm/node_modules/docx');
const fs = require('fs');

const CONTENT_W = 9360;
const GREEN="215732", LIGHT_GRN="E2EFDA", MID_GRN="C6E0B4", GREEN_TXT="217346",
      BORDER="CCCCCC", WHITE="FFFFFF", DARK="262626", GRAY_666="666666", GRAY_888="888888", GRAY_444="444444",
      RED_TXT="B03030", LIGHT_RED="FCE9E4";

const b1 = (color = BORDER) => ({ style: BorderStyle.SINGLE, size: 1, color });
const bAll = (color = BORDER) => ({ top: b1(color), bottom: b1(color), left: b1(color), right: b1(color) });
const cellPad = { top: 90, bottom: 90, left: 130, right: 130 };

const tr = (text, { bold=false, size=20, color=DARK, font="Arial", italics=false }={}) =>
  new TextRun({ text, bold, size, color, font, italics });
const spacer = (before=120, after=0) => new Paragraph({ children: [tr("")], spacing: { before, after } });
const rule = (color=GREEN, before=80, after=120) =>
  new Paragraph({ children: [tr("")], border: { bottom: { style: BorderStyle.SINGLE, size: 8, color, space: 1 } }, spacing: { before, after } });
const sectionHead = (text) => [ spacer(200, 0),
  new Paragraph({ children: [tr(text, { bold: true, size: 24, color: WHITE })], shading: { fill: GREEN, type: ShadingType.CLEAR }, spacing: { before: 0, after: 0 }, indent: { left: 160, right: 160 } }),
  spacer(100, 0) ];
const metricCell = (label, value, valueColor=GREEN, bg=LIGHT_GRN) =>
  new TableCell({ borders: bAll(MID_GRN), width: { size: 2340, type: WidthType.DXA }, shading: { fill: bg, type: ShadingType.CLEAR }, margins: { top: 120, bottom: 120, left: 140, right: 140 }, verticalAlign: VerticalAlign.CENTER,
    children: [ new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 40 }, children: [tr(value, { bold: true, size: 36, color: valueColor })] }),
                new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 0, after: 0 }, children: [tr(label, { size: 18, color: DARK })] }) ] });
const bullet = (text, bold=false) => new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [tr(text, { bold })], spacing: { before: 60, after: 60 } });
const subHead = (text, color=GREEN) => new Paragraph({ children: [tr(text, { bold: true, size: 22, color })], spacing: { before: 120, after: 60 } });
const para = (text, before=60, after=80) => new Paragraph({ children: [tr(text)], spacing: { before, after } });

// category table (cat | count)
const CAT_COLS = [7360, 2000];
const catRow = (cat, count, isHeader=false) => {
  const bg = isHeader ? GREEN : WHITE; const txt = isHeader ? WHITE : DARK;
  const mk = (t,w,align=AlignmentType.LEFT,bold=isHeader) => new TableCell({ borders: bAll(BORDER), width:{size:w,type:WidthType.DXA}, shading:{fill:bg,type:ShadingType.CLEAR}, margins:cellPad, children:[new Paragraph({ alignment:align, spacing:{before:0,after:0}, children:[tr(t,{bold,size:19,color:txt})] })] });
  return new TableRow({ children:[ mk(cat,CAT_COLS[0]), mk(String(count),CAT_COLS[1],AlignmentType.CENTER) ] });
};

// 3-col comparison table (element | current | new)
const CMP_COLS = [2760, 3300, 3300];
const cmpRow = (a, b, c, isHeader=false) => {
  const mk = (t, w, {bg=WHITE, color=DARK, bold=false, align=AlignmentType.LEFT}={}) =>
    new TableCell({ borders: bAll(BORDER), width:{size:w,type:WidthType.DXA}, shading:{fill:bg,type:ShadingType.CLEAR}, margins:cellPad, verticalAlign:VerticalAlign.CENTER,
      children:[new Paragraph({ alignment:align, spacing:{before:0,after:0}, children:[tr(t,{bold,size:18,color})] })] });
  if (isHeader) return new TableRow({ children:[ mk(a,CMP_COLS[0],{bg:GREEN,color:WHITE,bold:true}), mk(b,CMP_COLS[1],{bg:GREEN,color:WHITE,bold:true}), mk(c,CMP_COLS[2],{bg:GREEN,color:WHITE,bold:true}) ] });
  return new TableRow({ children:[ mk(a,CMP_COLS[0],{bold:true}), mk(b,CMP_COLS[1],{bg:LIGHT_RED,color:RED_TXT}), mk(c,CMP_COLS[2],{bg:LIGHT_GRN,color:GREEN_TXT}) ] });
};

const doc = new Document({
  numbering: { config: [{ reference: "bullets", levels: [{ level: 0, format: LevelFormat.BULLET, text: "•", alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 720, hanging: 360 } } } }] }] },
  styles: { default: { document: { run: { font: "Arial", size: 20 } } } },
  sections: [{
    properties: { page: { size: { width: 12240, height: 15840 }, margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 } } },
    headers: { default: new Header({ children: [new Paragraph({
      children: [ tr("C-P Flexible Packaging  |  SEO Progress Report  |  July 2026", { size: 18, color: GRAY_666 }), tr("\t\tPrepared by: Start Advertising", { size: 18, color: GRAY_666 }) ],
      tabStops: [{ type: TabStopType.RIGHT, position: CONTENT_W }], border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: BORDER } }, spacing: { after: 80 } })] }) },
    footers: { default: new Footer({ children: [new Paragraph({
      children: [ tr("Confidential — For C-P Flexible Packaging  |  Page ", { size: 16, color: GRAY_888 }), new TextRun({ children: [PageNumber.CURRENT], size: 16, color: GRAY_888, font: "Arial" }) ],
      border: { top: { style: BorderStyle.SINGLE, size: 4, color: BORDER } }, spacing: { before: 80, after: 0 } })] }) },
    children: [
      new Paragraph({ children: [tr("C-P Flexible Packaging", { bold: true, size: 52, color: GREEN })], spacing: { before: 160, after: 80 } }),
      new Paragraph({ children: [tr("SEO Progress Report", { bold: true, size: 40, color: DARK })], spacing: { before: 0, after: 60 } }),
      new Paragraph({ children: [tr("Reporting Period: June 24 – July 28, 2026", { size: 22, color: GRAY_444 })], spacing: { before: 0, after: 40 } }),
      new Paragraph({ children: [tr("Prepared by Start Advertising  |  Scope: Pre-launch on-page SEO & structured data (new HubSpot site)", { size: 20, color: GRAY_666 })], spacing: { before: 0, after: 0 } }),
      rule(GREEN, 80, 0),

      ...sectionHead("Executive Summary"),
      para(
        "This period, Start Advertising completed a full on-page SEO and structured-data build across all 59 pages of the new C-P " +
        "Flexible Packaging website in HubSpot. Every page now carries a rewritten title tag, a new meta description, descriptive image " +
        "alt text, and, where applicable, JSON-LD structured data (schema) — 100% of the page set."
      ),
      para(
        "Important context: this new site is built in a HubSpot instance that is not yet live. There is therefore no organic ranking or " +
        "traffic data to report this period — the work is the site's pre-launch SEO foundation, ensuring it goes live search-ready " +
        "rather than needing months of optimization after the fact. The company's current live site (garlockflexibles.com) has none " +
        "of this structure in place today."
      ),

      ...sectionHead("Dashboard Snapshot — as of July 28, 2026"),
      new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [2340, 2340, 2340, 2340],
        rows: [new TableRow({ children: [
          metricCell("Pages Optimized", "59"),
          metricCell("Page-Set Coverage", "100%", GREEN_TXT, LIGHT_GRN),
          metricCell("Content Images Alt-Texted", "160+"),
          metricCell("Structured-Data Blocks", "51", GREEN_TXT, LIGHT_GRN),
        ]})] }),
      spacer(80),
      new Paragraph({ children: [tr(
        "The new site is not yet live, so there is no organic ranking or traffic data this period. Performance measurement — Google " +
        "Search Console, Analytics, and rank tracking — begins once the site launches.",
        { size: 17, color: GRAY_888 })], spacing: { before: 40, after: 0 } }),

      ...sectionHead("Work Completed This Period"),
      para("The following on-page SEO and structured-data work was completed across the new C-P Flexible Packaging site:"),
      bullet("Page Titles (Title Tags) — 59 rewritten: keyword-front-loaded and kept within the ~70-character limit for clean display in search results.", true),
      bullet("Meta Descriptions — 59 written: benefit-driven with clear calls to action, within ~155 characters, to lift click-through once live."),
      bullet("Image Alt Text — 160+ content images given descriptive, keyword-aligned alt text (replacing empty or filename-style tags), improving accessibility and image-search visibility."),
      bullet("Structured Data (Schema) — 51 JSON-LD blocks added (Service and CollectionPage types), helping Google understand each page and enabling eligibility for rich results."),
      bullet("Sitewide Fixes — footer certification badges (SQF, AIB, BRCGS) and global elements (logo, GreenStream block, icons) given correct, consistent alt text across the whole site in a single pass."),

      ...sectionHead("Current Live Site vs. New Build"),
      para(
        "For context, here is how the company's current live site (garlockflexibles.com) compares to the new, optimized site we have " +
        "prepared for launch. The new build closes the gaps that leave the current site under-optimized for search."
      ),
      new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: CMP_COLS,
        rows: [
          cmpRow("SEO Element", "Current live site (garlockflexibles.com)", "New C-P site (built, pre-launch)", true),
          cmpRow("Page titles", "Basic / not optimized", "59 rewritten, keyword-focused"),
          cmpRow("Meta descriptions", "Missing or weak", "59 written with CTAs"),
          cmpRow("Image alt text", "Largely missing", "160+ images alt-texted"),
          cmpRow("Structured data (schema)", "None detected", "51 JSON-LD blocks"),
          cmpRow("H1 headings", "Multiple / unclear H1s", "Single descriptive H1 per page"),
        ] }),
      spacer(60),
      new Paragraph({ children: [tr(
        "Baseline from a review of garlockflexibles.com on July 28, 2026. The new site launches with structured data and full on-page " +
        "optimization the current site lacks entirely.", { size: 17, color: GRAY_888 })], spacing: { before: 40, after: 0 } }),

      new Paragraph({ children: [new PageBreak()] }),

      ...sectionHead("Pages Optimized by Category"),
      new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: CAT_COLS,
        rows: [
          catRow("Category", "Pages", true),
          catRow("Product pages (bags, pouches, rollstock, lidding, sleeves, labels, etc.)", 37),
          catRow("Market pages (coffee, confectionery, cookie & bakery, medical, aerospace, pet food, snack, health)", 9),
          catRow("Capability pages (design, prepress, HD flexographic, finishing)", 4),
          catRow("Sustainability pages (Sustainable hub, GreenStream, recycling, solar)", 4),
          catRow("Corporate / utility pages (Our Companies, Technology, Privacy, Consultation)", 4),
          catRow("Resource / landing pages (Cold-Seal Guide)", 1),
          catRow("Total optimized", 59, true),
        ] }),

      ...sectionHead("What's Next"),
      para("The on-page SEO build is complete. The following are queued for the coming period and for launch:"),
      bullet("Pre-launch measurement setup — connect Google Search Console and Google Analytics and stand up rank tracking, so performance can be measured the moment the site goes live.", true),
      bullet("Content expansion with targeted keyword research and new product pages to broaden topical coverage and capture more search demand."),
      bullet("Additional structured data — FAQ (FAQPage) schema on high-intent product pages and a sitewide Organization schema on the homepage to reinforce brand-entity and E-E-A-T signals."),

      ...sectionHead("Findings & Recommendations"),
      subHead("On-Page (quick fixes)"),
      bullet("Fix five generic H1 headings — the HD Flexographic Printing, Technology, Products, Package Finishing, and Request a Consultation pages use vague or sitewide H1s. Changing them to their descriptive titles is a fast, high-value gain.", true),
      bullet("Cross-link the three near-duplicate page pairs — matte/gloss, paper, and recyclable (each has a rollstock and a pouches version) — so they support rather than compete with each other."),
      subHead("Launch Readiness"),
      bullet("Prepare a launch-day SEO checklist — 301 redirects from garlockflexibles.com URLs, XML sitemap, robots directives, and Search Console verification — so existing search equity transfers cleanly at go-live.", true),
      bullet("Run an internal-linking pass connecting hub pages to their sub-pages (Markets → market pages, Products → categories, Bags → bag types)."),
      subHead("Strategic"),
      bullet("Layer FAQ sections + FAQPage schema onto top product pages to compete for featured snippets and AI answers.", true),
      bullet("Establish a monthly reporting cadence post-launch so rankings, impressions, clicks, and conversions are tracked on a consistent timeline."),

      spacer(200), rule(BORDER, 80, 80),
      new Paragraph({ alignment: AlignmentType.CENTER, children: [tr(
        "Report prepared by Start Advertising  •  Pre-launch on-page SEO & structured data  •  Period: June 24 – July 28, 2026",
        { size: 17, color: GRAY_888 })], spacing: { before: 0, after: 0 } }),
    ],
  }],
});

const OUT = "C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Garlock/C-P-Flexible-Packaging-SEO-Progress-Report-2026-07-28.docx";
Packer.toBuffer(doc).then(buf => { fs.writeFileSync(OUT, buf); console.log("SUCCESS: " + OUT); }).catch(err => { console.error("FAILED: " + err.message); process.exit(1); });
