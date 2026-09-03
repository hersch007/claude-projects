const fs   = require('fs');
const path = require('path');

const SRC  = 'C:/Users/richa/AppData/Local/Temp/cp-flex-unpacked';
const DEST = 'C:/Users/richa/AppData/Local/Temp/fruth-exec-unpacked';
const OUT  = 'C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Fruth/Fruth-SEO-Executive-Summary-July2026.pptx';

// ── 1. Copy source tree to working dir ───────────────────────────────────────
function copyDir(src, dst) {
  fs.mkdirSync(dst, { recursive: true });
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const s = path.join(src, entry.name);
    const d = path.join(dst, entry.name);
    if (entry.isDirectory()) copyDir(s, d);
    else fs.copyFileSync(s, d);
  }
}
if (fs.existsSync(DEST)) fs.rmSync(DEST, { recursive: true });
copyDir(SRC, DEST);

// ── 2. Edit slide1.xml ───────────────────────────────────────────────────────
let slide = fs.readFileSync(`${DEST}/ppt/slides/slide1.xml`, 'utf8');

// Colors: green → Fruth
slide = slide.replaceAll('215732', 'C41230');   // dark green → Fruth red
slide = slide.replaceAll('EAF3E6', 'EBF1F9');   // tile bg light green → light blue
slide = slide.replaceAll('C6E0B4', 'BDD7EE');   // tile border → blue border

// Header text
slide = slide.replace('C-P FLEXIBLE PACKAGING',  'FRUTH CUSTOM PACKAGING');
slide = slide.replace('SEO Progress — 30-Day Snapshot', 'SEO Progress — 47-Day Snapshot');
slide = slide.replace('Reporting period: June 24 – July 14, 2026   |   Prepared by Start Advertising',
                      'Reporting period: May 27 – July 14, 2026   |   Prepared by Start Advertising');

// KPI values
slide = slide.replace('>42<',    '>26<');
slide = slide.replace('>71%<',   '>62<');
slide = slide.replace('>130+<',  '>154<');
slide = slide.replace('>37<',    '>78/100<');

// KPI labels
slide = slide.replace('Pages Optimized &amp; Live',    'Product Pages Expanded');
slide = slide.replace('Site Coverage (of 59)',          'Organic Keywords (+87.9%)');
slide = slide.replace('Content Images Alt-Texted',      'Organic Traffic (+30.5%)');
slide = slide.replace('Structured-Data Blocks',         'On-Page SEO Score');

// Section head right column
slide = slide.replace('Pages Optimized by Category',   'Keyword Position Gains');

// Checklist — replace each line individually
slide = slide.replace('42 page titles rewritten (keyword-focused, ≤70 chars)',
                      '26 product pages rewritten with B2B-focused, keyword-rich copy');
slide = slide.replace('42 meta descriptions written (with CTAs, ≤155 chars)',
                      'FAQ sections added to all 26 product pages');
slide = slide.replace('130+ images given descriptive, keyword-aligned alt text',
                      'FAQ schema (JSON-LD structured data) added to all 26 pages');
slide = slide.replace('37 structured-data (schema) blocks added',
                      'Organization schema added to the homepage');
slide = slide.replace('Sitewide certification badges fixed — SQF · AIB · BRCGS',
                      'SEO issues reduced by 14.5% — from 76 open to 65; 14 pages live in HubSpot');

// IN PROGRESS banner text
slide = slide.replace('Content expansion with targeted keyword research and new product pages',
                      'H1 headings needed on 3 pages (/capabilities, /fruth-360, /our-story)  ·  “cleanroom packaging” at #20 and climbing — an internal link will push it to page one');

// Footer
slide = slide.replace('gcpflexpack.com   ·   Confidential — For C-P Flexible Packaging',
                      'fruth.com   ·   Confidential — For Fruth Custom Packaging Internal Use');

fs.writeFileSync(`${DEST}/ppt/slides/slide1.xml`, slide, 'utf8');

// ── 3. Edit chart1.xml ───────────────────────────────────────────────────────
let chart = fs.readFileSync(`${DEST}/ppt/charts/chart1.xml`, 'utf8');

// Bar color
chart = chart.replaceAll('215732', 'C41230');

// Series name
chart = chart.replace('<c:v>Pages</c:v>', '<c:v>Positions Gained</c:v>');

// Category labels (6 items, in order bottom-to-top as they appear)
chart = chart.replace('<c:v>Product</c:v>',       '<c:v>cleanroom packaging</c:v>');
chart = chart.replace('<c:v>Market</c:v>',        '<c:v>autoclavable bags</c:v>');
chart = chart.replace('<c:v>Capability</c:v>',    '<c:v>polypropylene grow bag</c:v>');
chart = chart.replace('<c:v>Sustainability</c:v>','<c:v>plastic bags for plants</c:v>');
chart = chart.replace('<c:v>Corporate</c:v>',     '<c:v>autoclaving bags</c:v>');
chart = chart.replace('<c:v>Resource</c:v>',      '<c:v>plastic grow bag</c:v>');

// Values (matching same order)
chart = chart.replace('<c:v>25</c:v>', '<c:v>80</c:v>');
chart = chart.replace('<c:v>8</c:v>',  '<c:v>11</c:v>');
chart = chart.replace('<c:v>4</c:v>',  '<c:v>6</c:v>');
chart = chart.replace('<c:v>2</c:v>',  '<c:v>4</c:v>');
// Two <c:v>2</c:v> entries — second one handled above; now the remaining 2→1 and 1→1
chart = chart.replace('<c:v>2</c:v>',  '<c:v>1</c:v>');
chart = chart.replace('<c:v>1</c:v>',  '<c:v>1</c:v>');  // already correct

fs.writeFileSync(`${DEST}/ppt/charts/chart1.xml`, chart, 'utf8');

// ── 4. Zip back to .pptx ─────────────────────────────────────────────────────
const { execSync } = require('child_process');
if (fs.existsSync(OUT)) fs.unlinkSync(OUT);

// Use PowerShell Compress-Archive (rename .pptx to .zip workaround)
const TMP_ZIP = OUT.replace('.pptx', '-tmp.zip');
execSync(`powershell -Command "Push-Location '${DEST}'; Compress-Archive -Path * -DestinationPath '${TMP_ZIP}' -Force; Pop-Location"`);
fs.renameSync(TMP_ZIP, OUT);

console.log('SUCCESS:', OUT);
