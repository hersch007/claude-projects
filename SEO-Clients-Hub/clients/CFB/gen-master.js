// CFB Master SEO Report generator — regenerates CFB-SEO-Report-2026-07-16-StartAdvertising-MASTER.docx
// Run: NODE_PATH="C:/Users/richa/AppData/Roaming/npm/node_modules" node gen-master.js
const docx = require('docx');
const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType, PageBreak,
  Table, TableRow, TableCell, WidthType, BorderStyle, ShadingType, LevelFormat,
  Header, Footer, PageNumber, TabStopType, TabStopPosition, convertInchesToTwip
} = docx;

const PRIMARY = '003366';
const ACCENT = 'C9A84C';
const HEADER_TINT = 'D9E0E8'; // ~15% navy on white
const GRAY = '595959';
const LIGHT = 'F2F2F2';
const GREEN = '2E7D32';
const BORDER = { style: BorderStyle.SINGLE, size: 4, color: 'CCCCCC' };
const CELL_BORDERS = { top: BORDER, bottom: BORDER, left: BORDER, right: BORDER };
const CELL_MARGIN = { top: 80, bottom: 80, left: 120, right: 120 };
const FONT = 'Arial';

function run(text, opts = {}) { return new TextRun({ text, font: FONT, size: 22, ...opts }); }
function para(text, opts = {}) {
  return new Paragraph({ children: [run(text, opts.run || {})], spacing: { after: 160 }, ...opts.para });
}
function h1(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 360, after: 200 },
    children: [new TextRun({ text, font: FONT, size: 32, bold: true, color: PRIMARY })]
  });
}
function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 240, after: 140 },
    children: [new TextRun({ text, font: FONT, size: 26, bold: true, color: ACCENT })]
  });
}
function bullet(text, bold = false) {
  return new Paragraph({
    numbering: { reference: 'bullets', level: 0 },
    spacing: { after: 100 },
    children: typeof text === 'string' ? [run(text, { bold })] : text
  });
}
// done=true renders a completed (☑) item
function check(text, done = false) {
  return new Paragraph({
    spacing: { after: 100 },
    indent: { left: 360 },
    children: [run(done ? '☑  ' : '☐  ', done ? { color: GREEN, bold: true } : {}), ...(typeof text === 'string' ? [run(text)] : text)]
  });
}
function numbered(text) {
  return new Paragraph({
    numbering: { reference: 'nums', level: 0 },
    spacing: { after: 100 },
    children: typeof text === 'string' ? [run(text)] : text
  });
}
function cell(content, opts = {}) {
  const children = (Array.isArray(content) ? content : [content]).map(t =>
    typeof t === 'string'
      ? new Paragraph({ children: [run(t, opts.run || {})] })
      : t
  );
  return new TableCell({ children, borders: CELL_BORDERS, margins: CELL_MARGIN, shading: opts.shading, width: opts.width });
}
function table(headers, rows, colWidths) {
  const total = colWidths.reduce((a, b) => a + b, 0);
  const headerRow = new TableRow({
    tableHeader: true,
    children: headers.map((htext, i) => cell(htext, {
      run: { bold: true, color: PRIMARY },
      shading: { type: ShadingType.CLEAR, fill: HEADER_TINT },
      width: { size: colWidths[i], type: WidthType.DXA }
    }))
  });
  const bodyRows = rows.map((r, ri) => new TableRow({
    children: r.map((c, ci) => cell(c, {
      shading: ri % 2 === 1 ? { type: ShadingType.CLEAR, fill: LIGHT } : undefined,
      width: { size: colWidths[ci], type: WidthType.DXA }
    }))
  }));
  return new Table({ rows: [headerRow, ...bodyRows], width: { size: total, type: WidthType.DXA } });
}
function codeBlock(text) {
  return text.split('\n').map(line => new Paragraph({
    shading: { type: ShadingType.CLEAR, fill: 'F5F5F5' },
    indent: { left: 360 },
    spacing: { after: 0 },
    children: [new TextRun({ text: line || ' ', font: 'Courier New', size: 18 })]
  }));
}
function spacer() { return new Paragraph({ children: [], spacing: { after: 120 } }); }

const orgSchema = `{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Cleanroom Film & Bags",
  "alternateName": "CFB",
  "url": "https://www.cleanroomfilm.com/",
  "logo": "https://www.cleanroomfilm.com/hubfs/CFB%20Logo.png",
  "description": "ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years.",
  "foundingDate": "1990",
  "slogan": "Sterile Packaging Solutions That Fuel Life",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "1700 Barcelona Circle",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-714-744-8361",
    "contactType": "sales",
    "email": "sales@cleanbags.com",
    "areaServed": "US"
  },
  "sameAs": [
    "https://www.linkedin.com/company/cleanbags"
  ],
  "hasCredential": [
    { "@type": "EducationalOccupationalCredential", "name": "ISO 9001:2015" },
    { "@type": "EducationalOccupationalCredential", "name": "ISO 14644-1 Class 5 Cleanroom" }
  ],
  "knowsAbout": [
    "cleanroom packaging",
    "cleanroom film",
    "cleanroom bags",
    "ESD packaging",
    "low outgassing films",
    "CLEANTUFF films",
    "Tyvek pouches",
    "sterile barrier packaging"
  ]
}`;

const localSchema = `{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": "https://www.cleanroomfilm.com/#business",
  "name": "Cleanroom Film & Bags",
  "image": "https://www.cleanroomfilm.com/hubfs/CFB%20Logo.png",
  "url": "https://www.cleanroomfilm.com/",
  "telephone": "+1-714-744-8361",
  "faxNumber": "+1-714-744-8360",
  "email": "sales@cleanbags.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "1700 Barcelona Circle",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "geo": { "@type": "GeoCoordinates", "latitude": 33.8925, "longitude": -117.8531 },
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "08:00",
    "closes": "17:00"
  }
}`;

const breadcrumbSchema = `{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.cleanroomfilm.com/" },
    { "@type": "ListItem", "position": 2, "name": "Products", "item": "https://www.cleanroomfilm.com/products" },
    { "@type": "ListItem", "position": 3, "name": "Cleanroom Bags", "item": "https://www.cleanroomfilm.com/products/cleanroom-bags" }
  ]
}`;

// ---------- COVER ----------
const cover = [
  new Paragraph({ spacing: { before: 2400 }, children: [] }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: 'Cleanroom Film & Bags', font: FONT, size: 72, bold: true, color: PRIMARY })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 240 }, children: [new TextRun({ text: 'SEO Audit Report', font: FONT, size: 48, color: GRAY })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 240 }, children: [new TextRun({ text: 'Sterile Packaging Solutions That Fuel Life', font: FONT, size: 28, italics: true, color: ACCENT })] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 160 }, children: [new TextRun({ text: 'https://www.cleanroomfilm.com/', font: FONT, size: 24, color: '0563C1', underline: {} })] }),
  new Paragraph({ spacing: { before: 1200 }, children: [] }),
  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: 'Audit Date: 2026-07-16   |   Platform: HubSpot CMS   |   Prepared by: Start Advertising', font: FONT, size: 22, color: GRAY })] }),
  new Paragraph({ children: [new PageBreak()] })
];

// ---------- SECTIONS ----------
const body = [];

// 1
body.push(h1('1. Overall SEO Health Score: 62 / 100'));
body.push(para('CFB has a healthy technical foundation and genuinely strong credibility assets (35+ years, ISO 9001:2015, ISO 14644 Class 5, exclusive CLEANTUFF® manufacturing), but is losing significant search visibility to a missing homepage meta description, mis-targeted title tags, near-total absence of structured data, and thin category-page content — all highly fixable within weeks.'));

// 2
body.push(h1('2. Top 5 Priorities'));
body.push(table(['#', 'Priority', 'Impact', 'Effort'], [
  ['1', 'Add homepage meta description + fix homepage title tag — ✔ COMPLETED 2026-07-16', 'High', 'Low'],
  ['2', 'Rewrite the mis-targeted /our-story title tag to match page content', 'High', 'Low'],
  ['3', 'Deploy Organization + Product + BreadcrumbList schema sitewide — Organization schema ✔ COMPLETED 2026-07-16', 'High', 'Medium'],
  ['4', 'Expand thin category pages (/products ~150 words, /standards ~280, /markets ~450) to 600–1,000 words of unique, spec-driven copy', 'High', 'Medium'],
  ['5', 'Surface E-E-A-T: team/engineering bios, customer logos/testimonials, and dated case studies in the Learning Center', 'Medium', 'Medium']
], [600, 6600, 1300, 1300]));
body.push(spacer());

// 3
body.push(h1('3. Quick Wins (Do in < 1 Week)'));
body.push(check([
  run('COMPLETED 2026-07-16 — Homepage meta description added. ', { bold: true, color: GREEN }),
  run('Copy: “ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years. Get a quote.” (150 chars)')
], true));
body.push(check([
  run('COMPLETED 2026-07-16 — Homepage title tag rewritten. ', { bold: true, color: GREEN }),
  run('New: “Cleanroom Packaging: Film & Bags Manufacturer | CFB” (52 chars); replaced the 74-char bag-only title.')
], true));
body.push(check([
  run('COMPLETED 2026-07-16 — Organization JSON-LD schema deployed to site header. ', { bold: true, color: GREEN }),
  run('Final client-verified code in Appendix (logo, LinkedIn, founding date 1990 all confirmed).')
], true));
[
  [run('Fix /our-story title tag. ', { bold: true }), run('Current title is a keyword-stuffed mismatch (“Sterile Packaging: Healthcare, Pharmaceutical, & Food Bags | CFB”). Suggested: “Our Story: 35+ Years of Cleanroom Packaging Excellence | CFB”')],
  [run('Add meta description to /markets', { bold: true }), run(' (currently missing). Suggested: “Cleanroom packaging for medical, pharmaceutical, semiconductor, aerospace, electronics & food industries. ISO-certified, USA-made film and bags.”')],
  [run('Add ', { bold: true }), run('“Sitemap: https://www.cleanroomfilm.com/sitemap.xml” to robots.txt (sitemap exists but is not declared).')],
  [run('Add publish dates to all Learning Center articles', { bold: true }), run(' — only one article (Mar 26, 2024) shows a date; undated content weakens freshness and trust signals.')],
  [run('Fix the homepage H1. ', { bold: true }), run('The hero splits the H1 across three phrases. Consolidate to one keyword-bearing H1, e.g. “Cleanroom Packaging Film & Bags — Sterile Delivery When It Matters Most”, and demote the narrative lines to styled paragraph text.')]
].forEach(c => body.push(check(c)));

// 4
body.push(h1('4. Medium-Term Recommendations (2–8 Weeks)'));
[
  [run('Expand /products to 600+ words: ', { bold: true }), run('add a film-vs-bags decision guide intro, typical applications by industry, cleanliness class options, and MOQ/customization notes. Currently ~150–200 words of thin hub content.')],
  [run('Expand /standards with per-standard explanations: ', { bold: true }), run('each ASTM/USP/MIL/NASA standard in the chart deserves 2–3 sentences on what it certifies and why a buyer should care. This page is a link-magnet opportunity for engineers.')],
  [run('Rework /materials title tag ', { bold: true }), run('— it currently targets only ESD while the page covers 11 materials. Retitle to “Cleanroom Packaging Materials: Nylon, Tyvek®, Aclar® & More | CFB”.')],
  [run('Add Product schema to every product page ', { bold: true }), run('(rollstock, tubing, sheeting, each bag type) with brand, material, and audience properties.')],
  [run('Add BreadcrumbList schema sitewide ', { bold: true }), run('— the deep Markets → Products → Materials hierarchy qualifies for breadcrumb rich results.')],
  [run('Create an About/Team layer on /our-story: ', { bold: true }), run('leadership and engineering bios with credentials (“120 years of combined blown film expertise” is claimed — name the people behind it).')],
  [run('Add customer proof: ', { bold: true }), run('client logos (a Boeing case study already exists — leverage it), testimonials, and industries-served counts above the fold on the homepage.')],
  [run('Internal linking pass: ', { bold: true }), run('link Learning Center case studies contextually from relevant market pages (e.g., Boeing/Aclar® case study → aerospace market and Aclar® material pages).')],
  [run('Descriptive image alt text ', { bold: true }), run('across product and market pages — most images carry generic or missing alt attributes.')],
  [run('Claim/optimize Google Business Profile ', { bold: true }), run('for the Placentia, CA facility (manufacturer category) — supports branded search and SoCal B2B map-pack queries.')],
  [run('Build a VCI packaging landing page (client priority, added 2026-07-16): ', { bold: true }), run('VCI (volatile corrosion inhibitor) bags/film currently have zero presence on the site — no page, no mention. Create /products/vci-bags targeting VCI bags, VCI film, VCI packaging, anti-corrosion packaging, and rust prevention packaging, with product specs, applications (metal parts in storage and transit), Product schema, and cross-links from aerospace and electronics market pages.')]
].forEach(c => body.push(check(c)));

// 5
body.push(h1('5. Long-Term / Strategic Recommendations'));
[
  [run('Build a technical resource hub ', { bold: true }), run('in the Learning Center: material selection guides, cleanliness class explainers (ISO 14644 Class 5 vs. Class 100), sterilization compatibility charts, outgassing spec sheets. The #1 defensible moat for a niche B2B manufacturer.')],
  [run('Promote the CLEANTUFF® and ULO branded-material category: ', { bold: true }), run('dedicated pages already exist and are strong (the CLEANTUFF® page runs ~1,200 words with full spec tables; the ULO page has a good title and meta) — but they are buried. Feature them on the homepage, cross-link from case studies and market pages, and add comparison content (“CLEANTUFF® vs. standard polyethylene”) since CFB is the exclusive manufacturer.')],
  [run('Systematic case-study program: ', { bold: true }), run('one dated, structured case study per quarter per key market (medical, semiconductor, aerospace), each with measurable outcomes and Article schema.')],
  [run('Digital PR / industry citations: ', { bold: true }), run('pursue Thomasnet, medical device and semiconductor supply-chain directories, and trade publications to build authority backlinks.')],
  [run('Target bottom-funnel comparison queries: ', { bold: true }), run('“cleanroom bags supplier,” “Class 100 packaging manufacturer,” “Tyvek pouch manufacturer USA” — build dedicated landing pages where volume justifies.')],
  [run('Video SEO expansion: ', { bold: true }), run('the homepage overview video already has VideoObject schema — build a library (facility tour, material tests) with transcripts to capture video results.')]
].forEach(c => body.push(check(c)));

// 6
body.push(h1('6. Technical SEO Analysis'));
body.push(table(['Element', 'Status', 'Notes'], [
  ['Title Tags', 'Improving', 'Homepage title fixed 2026-07-16; /our-story and /materials titles still mismatched to content'],
  ['Meta Descriptions', 'Improving', 'Homepage added 2026-07-16; still MISSING on /markets; present on /products, /materials, /standards, /contact-us, /our-story'],
  ['H1 Tags', 'Present', 'Homepage H1 split across 3 narrative phrases with no keyword; interior pages use short generic H1s'],
  ['Heading Hierarchy (H2–H4)', 'Issues', '/markets and /our-story have no H2s; shallow hierarchy across interior pages'],
  ['Schema / Structured Data', 'Improving', 'Organization schema deployed 2026-07-16 (joins existing VideoObject). Still to add: LocalBusiness, Product, BreadcrumbList, Article'],
  ['Canonical Tags', 'Present', 'Self-referencing canonical confirmed on homepage'],
  ['XML Sitemap', 'Present', 'Valid, 52 page URLs, images + video included, lastmod dates current (Apr–Jun 2026)'],
  ['Robots.txt', 'Present', 'Clean HubSpot rules; NO Sitemap declaration'],
  ['Page Speed', 'Moderate', 'HubSpot-hosted with hero video; run Core Web Vitals check (video poster/lazy-load)'],
  ['Mobile Friendliness', 'Good', 'Responsive HubSpot theme; mobile nav present'],
  ['HTTPS / SSL', 'Present', 'Sitewide'],
  ['Broken Links', 'None found', 'No 404s among primary pages; careers link exits to share.hsforms.com'],
  ['Image Alt Text', 'Partial', 'Category images use generic alts; most images lack descriptive alt text']
], [2400, 1800, 5600]));
body.push(spacer());
body.push(h2('Key Technical Issues'));
[
  'Homepage meta description was missing — FIXED 2026-07-16.',
  'Organization schema deployed 2026-07-16; continue rollout of LocalBusiness, Product, and BreadcrumbList markup for full rich-result coverage.',
  'Robots.txt lacks a sitemap declaration — one-line fix.',
  'Careers page links out to a raw HubSpot form URL (share.hsforms.com), leaking authority and looking untrustworthy; embed the form on an on-domain /careers page.',
  'Footer displays social icons for platforms the client is not active on (only LinkedIn is live) — remove dead icons.'
].forEach(t => body.push(bullet(t)));

// 7
body.push(h1('7. On-Page SEO & Content Analysis'));
body.push(h2('Homepage'));
[
  [run('Title: ', { bold: true }), run('UPDATED 2026-07-16 to “Cleanroom Packaging: Film & Bags Manufacturer | CFB” (was “Cleanroom Bags: Medical & Electronic Packaging | ESD Bags | CFB California”, 74 chars, truncating)')],
  [run('Meta Description: ', { bold: true }), run('ADDED 2026-07-16 (was missing)')],
  [run('H1: ', { bold: true }), run('Split narrative — “From the tiniest of micro-chips” / “To life saving surgical tools” / “Our cleanroom packaging ensures a safe and sterile delivery when it matters most.” Still to consolidate.')],
  [run('Notes: ', { bold: true }), run('~800–1,000 words; strong benefit-focused copy and clear CTAs (“Request a Quote Today”), good trust markers (ISO 9001:2015, USA Made, 100% No Regrind Guarantee).')]
].forEach(c => body.push(bullet(c)));
body.push(h2('/products'));
[
  [run('Title: ', { bold: true }), run('“Cleanroom Packaging Products | Cleanroom Film & Bags” — good. Meta description present. H1: “Products”.')],
  [run('Notes: ', { bold: true }), run('THIN (~150–200 words) — pure navigation hub. Biggest content-expansion opportunity on the site.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('/markets'));
[
  [run('Title: ', { bold: true }), run('“Markets | Poly Bag & Blown Film Manufacturing | Cleanroom Film & Bags”. Meta description MISSING. H1: “Markets & Capabilities”.')],
  [run('Notes: ', { bold: true }), run('~400–500 words, tile-driven, no H2s. Each of the 7 industry tiles should link to a fleshed-out market page.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('/standards'));
[
  [run('Title: ', { bold: true }), run('“Cleanroom Packaging Standards | Cleanroom Film & Bags” — good. Meta description present. H1: “Standards”.')],
  [run('Notes: ', { bold: true }), run('~280 words + standards chart (ASTM E595/F331/F2095/D1505/D6953/D257/F1140, USP 661/671, MIL-DTL-24466, NASA JPR 5322.1G, IEST-CC-1246, ANSI/ESD S-541). High-value chart, zero explanation — expand per-standard.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('/our-story'));
[
  [run('Title: ', { bold: true }), run('“Sterile Packaging: Healthcare, Pharmaceutical, & Food Bags | CFB” — MISMATCHED/keyword-stuffed; page is actually company history. Meta description equally mismatched.')],
  [run('Notes: ', { bold: true }), run('~400–450 words; good history (2003 CLEANFILM Inc. acquisition, ISO 14644 Class 5 equipment) but no people, no team photos, no timeline.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('/materials/cleantuff-cleanroom-packaging (exclusive material)'));
[
  [run('Title: ', { bold: true }), run('“CLEANTUFF® LDPE & HDPE Cleanroom Packaging | Cleanroom Film & Bags” — good. Meta description present and specific.')],
  [run('Notes: ', { bold: true }), run('STRONG page: ~1,200 words with CT100/CT200 spec tables (tensile, dart impact, tear resistance, gauges, cleanliness levels). The problem is discoverability — not featured on the homepage and weakly cross-linked. Promote, don’t rebuild.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('/contact-us and /learning-center'));
[
  [run('/contact-us: ', { bold: true }), run('Clean title, meta, and NAP block; consider adding a map embed and LocalBusiness schema.')],
  [run('/learning-center: ', { bold: true }), run('10 articles including two strong case studies (Boeing/Aclar®, FOUP semiconductor packaging). Only one article shows a publish date. No Article schema or category filtering.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('Content Quality Summary'));
[
  [run('Word count: ', { bold: true }), run('Homepage healthy (~900); category pages thin (150–500 words); deep material pages substantial (CLEANTUFF® ~1,200 words)')],
  [run('Keyword targeting: ', { bold: true }), run('Inconsistent — strong on the “cleanroom” family but title tags fight each other (/materials targets ESD only; /our-story targets sterile packaging)')],
  [run('Internal linking: ', { bold: true }), run('Navigation-strong, contextual-weak; case studies and exclusive-material pages not linked from market pages or homepage')],
  [run('CTAs: ', { bold: true }), run('Strong — “Request a Quote Today” prominent, phone/email in footer sitewide')]
].forEach(c => body.push(bullet(c)));

// 8
body.push(h1('8. Local & E-E-A-T Analysis'));
body.push(h2('E-E-A-T Signals Present'));
[
  '☐ Author/team bios — none',
  '☑ Credentials — ISO 9001:2015, ISO 14644 Class 5, Class 100 facility',
  '☑ Years in business — founded 1990 (confirmed); “120 years of combined blown film manufacturing expertise”',
  '☑ Professional standards — ASTM, USP, MIL, NASA, IEST, ANSI/ESD compliance chart',
  '☐ Media mentions — none visible',
  '☐ Client testimonials — none (case studies name Boeing but no quotes)',
  '☐ Awards — none visible (Verified Vendor Seal 2024 in footer only)',
  '☑ About page — /our-story exists but is thin'
].forEach(t => body.push(bullet(t)));
body.push(h2('E-E-A-T Gaps'));
[
  'No named humans anywhere on the site. Fix: leadership + engineering bios with credentials on /our-story.',
  '“120 years of combined expertise” is claimed but unsubstantiated. Fix: attribute it to named team members.',
  'Boeing and organ-transplant case studies exist but carry no dates, client quotes, or Article schema. Fix: restructure as dated, quoted, schema-marked case studies.',
  'Verified Vendor Seal (2024) is aging — renew/update or remove.'
].forEach(t => body.push(bullet(t)));
body.push(h2('Local SEO'));
[
  [run('Google Business Profile: ', { bold: true }), run('Unknown — verify claim status for 1700 Barcelona Circle, Placentia, CA (category: Manufacturer / Packaging Supply)')],
  [run('NAP Consistency: ', { bold: true }), run('Consistent sitewide; note email domain is cleanbags.com vs. site domain cleanroomfilm.com — keep citations identical everywhere')],
  [run('Geographic targeting: ', { bold: true }), run('Weak — “California” was only in the old homepage title tag; “USA Made” is the stronger national angle')],
  [run('Citations / directories: ', { bold: true }), run('Prioritize Thomasnet and industry-specific B2B directories over consumer citations — national B2B play with a local facility anchor')]
].forEach(c => body.push(bullet(c)));

// 9
body.push(h1('9. Conversion & User Experience Issues'));
[
  [run('CTAs: ', { bold: true }), run('Strong primary CTA (“Request a Quote Today”). Add quote CTAs to the bottom of every product/material page.')],
  [run('Contact friction: ', { bold: true }), run('Low — phone, email, and form all available. Form fields not excessive.')],
  [run('Mobile UX: ', { bold: true }), run('Responsive theme, functional mobile nav.')],
  [run('Page load perception: ', { bold: true }), run('Hero video on homepage — ensure poster image + lazy-load so LCP is not video-blocked.')],
  [run('Trust signals above the fold: ', { bold: true }), run('Partial — certifications live mid-page/footer; move ISO badges + “35+ Years / USA Made” strip up near the hero.')],
  [run('Navigation clarity: ', { bold: true }), run('Clear three-axis IA (Markets / Products / Materials) — genuinely good for a B2B catalog. Careers link exiting to share.hsforms.com is the one confusing element.')]
].forEach(c => body.push(bullet(c)));
body.push(h2('Key Fixes'));
[
  'Add ISO 9001:2015 + Class 100 badges and “35+ Years • USA Made” trust strip above the fold on homepage.',
  'Embed careers form on-domain.',
  'Add a map embed + directions on /contact-us.',
  'Add quote-request CTA blocks to the bottom of all product, material, and market pages.'
].forEach(t => body.push(bullet(t)));

// 10
body.push(h1('10. Content & Keyword Strategy Recommendations'));
body.push(h2('Target Keyword Opportunities'));
body.push(table(['Keyword', 'Intent', 'Difficulty', 'Priority Page'], [
  ['cleanroom packaging', 'Commercial', 'Medium', 'Homepage'],
  ['cleanroom bags', 'Commercial', 'Medium', '/products/cleanroom-bags'],
  ['cleanroom film', 'Commercial', 'Medium', '/products/cleanroom-film'],
  ['Class 100 cleanroom packaging', 'Commercial', 'Low', '/standards'],
  ['ESD bags manufacturer', 'Commercial', 'Medium', '/materials/esd-cleanroom-packaging'],
  ['Tyvek pouches medical packaging', 'Commercial', 'Low', '/materials/tyvek-cleanroom-packaging'],
  ['Aclar film packaging', 'Commercial', 'Low', '/materials/aclar-cleanroom-packaging'],
  ['medical device packaging manufacturer', 'Commercial', 'High', '/markets/medical-cleanroom-packaging'],
  ['semiconductor packaging bags', 'Commercial', 'Low', '/markets/semiconductor-cleanroom-packaging'],
  ['low outgassing packaging film', 'Commercial', 'Low', '/materials/extreme-low-outgassing-cleanroom-packaging (exists — promote)'],
  ['how to choose medical device packaging', 'Informational', 'Low', 'Learning Center (exists — optimize)'],
  ['ISO 14644 Class 5 packaging requirements', 'Informational', 'Low', 'New Learning Center guide'],
  ['sterile barrier packaging materials', 'Informational', 'Medium', 'New Learning Center guide'],
  ['anti-static vs static shielding bags', 'Informational', 'Low', 'New Learning Center guide'],
  ['VCI bags', 'Commercial', 'Low', 'New VCI page (client priority)'],
  ['VCI packaging', 'Commercial', 'Low', 'New VCI page (client priority)'],
  ['anti-corrosion packaging', 'Commercial', 'Low', 'New VCI page (client priority)']
], [3400, 1800, 1400, 3200]));
body.push(spacer());
body.push(h2('Content Gap Analysis'));
[
  'CLEANTUFF®/ULO pages exist and are substantial (spec tables, good metas) but are under-promoted: not featured on the homepage, no comparison content, and weak contextual internal links — the single most defensible keyword territory CFB owns, left unamplified.',
  'Standards page lists 15+ certifications with zero explanatory content — engineers search these standard numbers directly.',
  'No comparison/selection guides (film vs. bag, material vs. material, cleanliness class vs. class) — the highest-intent informational queries in this niche.',
  'Market pages are tiles, not pages — each of 7 industries deserves 800+ words of application-specific content.',
  'VCI packaging has zero site presence despite being a client priority — no page, no product mention, no sitemap entry. Every VCI keyword is an uncontested "Not ranked" until a page exists.'
].forEach(t => body.push(bullet(t)));
body.push(h2('Recommended Content Pieces'));
[
  '“ISO 14644 Cleanroom Classes Explained: What Class 5 / Class 100 Means for Your Packaging” — targets ISO 14644 Class 5 packaging, serves spec-driven engineers',
  '“Anti-Static vs. Static Shielding vs. ESD Bags: Which Does Your Product Need?” — high-converting comparison intent',
  '“CLEANTUFF® vs. Standard Polyethylene: Outgassing and Particulate Performance” — owns exclusive branded material territory',
  '“Sterilization Compatibility Guide: Which Packaging Materials Survive Gamma, EtO, and Autoclave” — targets medical device packaging engineers',
  '“Tyvek® vs. Medical-Grade Paper for Sterile Barrier Systems” — targets Tyvek pouches medical packaging',
  'Quarterly dated case studies per market (semiconductor FOUP and Boeing/Aclar® already exist — restructure with dates, quotes, outcomes, Article schema)',
  'VCI packaging landing page (client priority) — targets VCI bags / VCI packaging / anti-corrosion packaging; specs, applications, Product schema, cross-links from aerospace + electronics market pages'
].forEach(t => body.push(numbered(t)));

// 11
body.push(h1('11. Next Steps & Action Plan'));
[
  [run('Week 1 — CFB marketing/HubSpot admin: ', { bold: true }), run('Homepage title + meta description ✔ done 7/16; Organization schema ✔ done 7/16. Remaining: fix /our-story title/meta; add /markets meta description; add sitemap line to robots.txt.')],
  [run('Week 2 — Start Advertising: ', { bold: true }), run('Homepage H1 consolidation; trust-badge strip above fold; Learning Center publish dates; GBP claim/verification for Placentia facility.')],
  [run('Weeks 3–4 — Start Advertising + CFB product team: ', { bold: true }), run('Expand /products and /standards copy; /materials title fix; descriptive alt-text pass on top 20 images.')],
  [run('Weeks 5–8 — Start Advertising: ', { bold: true }), run('Product + BreadcrumbList schema rollout; team bios on /our-story; internal-linking pass; on-domain careers page.')],
  [run('Quarter 2 — joint: ', { bold: true }), run('CLEANTUFF®/ULO promotion (homepage feature + comparison guides); case-study program kickoff; Thomasnet + industry directory citations.')]
].forEach(c => body.push(numbered(c)));

// Appendix
body.push(h1('Appendix'));
body.push(h2('Suggested Title Tags'));
body.push(table(['Page', 'Recommended Title Tag'], [
  ['Homepage', 'Cleanroom Packaging: Film & Bags Manufacturer | CFB — ✔ LIVE as of 2026-07-16'],
  ['/products', 'Cleanroom Packaging Products: Film & Bags | CFB (current is fine; keep)'],
  ['/markets', 'Industries We Serve: Medical, Semiconductor, Aerospace | CFB'],
  ['/materials', 'Cleanroom Packaging Materials: Nylon, Tyvek®, Aclar® & More | CFB'],
  ['/standards', 'Cleanroom Packaging Standards & Certifications | CFB'],
  ['/our-story', 'Our Story: 35+ Years of Cleanroom Packaging Excellence | CFB'],
  ['/contact-us', 'Contact Us: Request a Cleanroom Packaging Quote | CFB']
], [2400, 7400]));
body.push(spacer());
body.push(h2('Suggested Meta Descriptions'));
body.push(table(['Page', 'Recommended Meta Description'], [
  ['Homepage', 'ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years. Get a quote. (150 chars) — ✔ ADDED 2026-07-16'],
  ['/markets', 'Cleanroom packaging for medical, pharmaceutical, semiconductor, aerospace, electronics & food industries. ISO-certified, USA-made film and bags.'],
  ['/our-story', '35+ years of cleanroom packaging excellence. Class 100 vertically integrated facility in Placentia, CA. Exclusive manufacturer of CLEANTUFF® and ULO films.'],
  ['/products', 'Cleanroom film (rollstock, tubing, sheeting) and cleanroom bags (zipper, header, heat-seal pouches) manufactured in ISO-certified Class 100 cleanrooms.']
], [2400, 7400]));
body.push(spacer());
body.push(h2('Full URL Inventory (from sitemap.xml — 52 page URLs)'));
[
  [run('Core (8): ', { bold: true }), run('/, /products, /markets, /materials, /standards, /our-story, /contact-us, /learning-center')],
  [run('Markets (7): ', { bold: true }), run('aerospace, electronic, food, healthcare, medical, pharmaceutical, semiconductor — all at /markets/[industry]-cleanroom-packaging')],
  [run('Materials (11): ', { bold: true }), run('aclar, anti-static-nylon, barrier, cleantuff, esd, extreme-low-outgassing (ULO), nylon, nylon-polyethylene, polyethylene, static-shielding, tyvek — at /materials/[material]-cleanroom-packaging')],
  [run('Products (15): ', { bold: true }), run('/products/cleanroom-bags (+7 children: bags-on-a-roll, bottom-seal, header, zipper, gusseted, high-heat-oven, tyvek-heat-sealing-pouches); /products/cleanroom-film (+4 children: rollstock, sheeting, square-bottom-covers, tubing); aluminum-foil-bags; medical-film-rolls; medical-grade-paper-rolls')],
  [run('Learning Center (10): ', { bold: true }), run('2 case studies (Boeing/Aclar®, FOUP high-density resin), 2 story pieces (organ transplant, outer space), 5 announcements, 1 how-to guide (medical device packaging)')],
  [run('Use: ', { bold: true }), run('this inventory is the optimization checklist for the Weeks 3–8 title/meta/schema/alt-text passes.')]
].forEach(c => body.push(bullet(c)));
body.push(spacer());
body.push(h2('Schema Code — Organization (site header, all pages) — DEPLOYED 2026-07-16'));
codeBlock(orgSchema).forEach(p => body.push(p));
body.push(para('All values client-verified 2026-07-16: logo URL, LinkedIn profile, and founding date (1990) confirmed. Client has no active Facebook, Instagram, or YouTube.', { run: { italics: true, size: 20, color: GRAY } }));
body.push(h2('Schema Code — LocalBusiness (/contact-us)'));
codeBlock(localSchema).forEach(p => body.push(p));
body.push(para('Verify geo coordinates and business hours with client before deploying.', { run: { italics: true, size: 20, color: GRAY } }));
body.push(h2('Schema Code — BreadcrumbList (example, product page)'));
codeBlock(breadcrumbSchema).forEach(p => body.push(p));
body.push(spacer());
body.push(h2('Additional Notes'));
[
  'Platform: HubSpot CMS — meta titles/descriptions edited per-page under Page Settings; sitewide schema goes in Settings → Website → Pages → Site Header HTML.',
  'Footer trust leak: the footer displays social icons for platforms the client is not active on — remove dead icons or link only LinkedIn.',
  'Tools recommended: Google Search Console (verify + submit sitemap), PageSpeed Insights for Core Web Vitals on the video-heavy homepage, Schema Markup Validator, Thomasnet profile audit.',
  'Watch-out: Email domain (cleanbags.com) differs from web domain (cleanroomfilm.com). Keep NAP citations identical everywhere; consider consolidating legacy cleanbags.com backlinks via redirects.'
].forEach(t => body.push(bullet(t)));

// ---------- DOC ----------
const doc = new Document({
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [{ level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 720, hanging: 360 } } } }]
      },
      {
        reference: 'nums',
        levels: [{ level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 720, hanging: 360 } } } }]
      }
    ]
  },
  styles: { default: { document: { run: { font: FONT, size: 22 } } } },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: convertInchesToTwip(1), bottom: convertInchesToTwip(1), left: convertInchesToTwip(1), right: convertInchesToTwip(1) }
      }
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          alignment: AlignmentType.RIGHT,
          children: [new TextRun({ text: 'Cleanroom Film & Bags — SEO Audit Report | Start Advertising', font: FONT, size: 18, color: GRAY })]
        })]
      })
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          tabStops: [{ type: TabStopType.RIGHT, position: TabStopPosition.MAX }],
          children: [
            new TextRun({ text: 'Confidential — Prepared Exclusively for Cleanroom Film & Bags', font: FONT, size: 18, color: GRAY }),
            new TextRun({ text: '\t', font: FONT, size: 18 }),
            new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 18, color: GRAY })
          ]
        })]
      })
    },
    children: [...cover, ...body]
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync('CFB-SEO-Report-2026-07-16-StartAdvertising-MASTER.docx', buf);
  console.log('MASTER docx written:', buf.length, 'bytes');
});
