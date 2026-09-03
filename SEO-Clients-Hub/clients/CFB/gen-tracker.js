// CFB Existing URLs project tracker — models Garlock's Existing URLS.xlsx (23 columns).
// Seeds all 55 sitemap URLs; pulls audited/implemented status + recommended title/meta from pages-data.js.
// Run: NODE_PATH="C:/Users/richa/AppData/Roaming/npm/node_modules" node gen-tracker.js
const ExcelJS = require('exceljs');
const audited = require('./pages-data.js').pages;

// ---- All 55 URLs from sitemap.xml, grouped, with a page type ----
const P = 'https://www.cleanroomfilm.com';
const rows = [];
function add(name, path, type, priority) { rows.push({ name, url: P + path, type, priority }); }

// Core (8)
add('Homepage', '/', 'Homepage', 'High');
add('Products (hub)', '/products', 'Category', 'High');
add('Markets (hub)', '/markets', 'Category', 'High');
add('Materials (hub)', '/materials', 'Category', 'High');
add('Standards', '/standards', 'Info', 'High');
add('Our Story', '/our-story', 'Info', 'Medium');
add('Contact Us', '/contact-us', 'Info', 'Medium');
add('Learning Center (hub)', '/learning-center', 'Blog Hub', 'Medium');
// Markets (7)
['medical','semiconductor','aerospace','pharmaceutical','healthcare','electronic','food'].forEach(m =>
  add(m.charAt(0).toUpperCase()+m.slice(1)+' Cleanroom Packaging', '/markets/'+m+'-cleanroom-packaging', 'Market', 'High'));
// Materials (11)
[['aclar','Aclar'],['anti-static-nylon','Anti-Static Nylon'],['barrier','Barrier'],['cleantuff','CLEANTUFF'],
 ['esd','ESD'],['extreme-low-outgassing','Extreme Low Outgassing (ULO)'],['nylon-polyethylene','Nylon/Polyethylene'],
 ['polyethylene','Polyethylene'],['static-shielding','Static Shielding'],['tyvek','Tyvek']]
 .forEach(([slug,label]) => add(label+' Cleanroom Packaging', '/materials/'+slug+'-cleanroom-packaging', 'Material', 'Medium'));
add('Nylon', '/materials/nylon', 'Material', 'Medium');
// Products (15)
add('Cleanroom Bags (hub)', '/products/cleanroom-bags', 'Category', 'High');
[['bags-on-a-roll','Bags on a Roll'],['bottom-seal-bags','Bottom Seal Bags'],['cleanroom-header-bags','Cleanroom Header Bags'],
 ['cleanroom-zipper-bags','Cleanroom Zipper Bags'],['gusseted-bags','Gusseted Bags'],['high-heat-oven-bags','High Heat Oven Bags'],
 ['tyvek-heat-sealing-pouches','Tyvek Heat Sealing Pouches']]
 .forEach(([slug,label]) => add(label, '/products/cleanroom-bags/'+slug, 'Product', 'Medium'));
add('Cleanroom Film (hub)', '/products/cleanroom-film', 'Category', 'High');
[['cleanroom-rollstock','Cleanroom Rollstock'],['cleanroom-sheeting','Cleanroom Sheeting'],
 ['cleanroom-square-bottom-covers','Cleanroom Square Bottom Covers'],['cleanroom-tubing','Cleanroom Tubing']]
 .forEach(([slug,label]) => add(label, '/products/cleanroom-film/'+slug, 'Product', 'Medium'));
add('Aluminum Foil Bags', '/products/aluminum-foil-bags', 'Product', 'Medium');
add('Medical Film Rolls', '/products/medical-film-rolls', 'Product', 'Medium');
add('Medical Grade Paper Rolls', '/products/medical-grade-paper-rolls', 'Product', 'Medium');
// Learning Center (10)
[['case-study-cleanroom-film-bags-foup-packaging-using-high-density-resin','Case Study: FOUP Packaging (High-Density Resin)'],
 ['case-study-the-use-of-aclar-packaging-film-for-protecting-parts-at-boeing','Case Study: Aclar® Film at Boeing'],
 ['cfb-packaging-ensures-safe-organ-transplant','CFB Packaging Ensures Safe Organ Transplant'],
 ['cfb-packaging-strong-enough-for-outer-space','CFB Packaging Strong Enough for Outer Space'],
 ['cleanroom-film-bags-adds-capacity-to-serve-increasing-demand-for-cleanroom-nylon-films-and-bags','Adds Capacity for Nylon Films & Bags'],
 ['cleanroom-film-bags-adds-high-speed-converting-line-to-serve-increasing-demand-for-cleanroom-bags','Adds High-Speed Converting Line'],
 ['cfb-cleantronics-launch','Announces CFB CleanTronics™'], // URL shortened 2026-08-11 (was cleanroom-film-bags-announces-cfb-cleantronics-advanced-packaging-for-semiconductors-and-microelectronics)
 ['cleanroom-film-bags-expands-offering-of-customized-sterilizable-packaging','Expands Sterilizable Packaging'],
 ['cleanroom-film-bags-opens-state-of-the-art-plant-in-placentia','Opens State-of-the-Art Plant in Placentia'],
 ['how-to-choose-the-right-medical-device-packaging','How To Choose the Right Medical Device Packaging']]
 .forEach(([slug,label]) => add(label, '/learning-center/'+slug, 'Blog', 'Low'));

// ---- Merge in audit status from pages-data.js (match on live URL) ----
const byUrl = {};
audited.forEach(p => { byUrl[p.liveUrl.replace(/\/$/, '') || P] = p; });
function statusFor(url) {
  const key = url.replace(/\/$/, '') || P;
  const p = byUrl[key] || byUrl[url];
  if (!p) return null;
  const done = /^IMPLEMENTED/.test(p.status);
  const date = (p.status.match(/20\d\d-\d\d-\d\d/) || [''])[0];
  return { p, done, date };
}

const HEADERS = ['Page','Live URL','Page Type','Priority','AISEO (Title)','Meta Desc','Images Alt Text',
  'Schema','Internal Linking','Owner / Assigned To','Target Date','Date Completed','% Complete',
  'Notes / Team Comments','Recommended Page Title','Recommended Meta Description','Image Changed? - Review Alt',
  'Billed - SEO','Billed - Schema','STILL TO BILL','Bill Date'];

const wb = new ExcelJS.Workbook();
wb.creator = 'Start Advertising';
const ws = wb.addWorksheet('Tracker', { views: [{ state: 'frozen', ySplit: 1, xSplit: 1 }] });

const NAVY = 'FF003366', GOLD = 'FFC9A84C', GREEN = 'FFE2EFDA', AMBER = 'FFFFF2CC', LIGHT = 'FFF2F2F2';
const FONT = 'Arial';

// Header row
const hr = ws.addRow(HEADERS);
hr.eachCell(c => {
  c.font = { name: FONT, bold: true, color: { argb: 'FFFFFFFF' }, size: 10 };
  c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NAVY } };
  c.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
  c.border = { bottom: { style: 'thin', color: { argb: GOLD } } };
});
hr.height = 34;

let doneCount = 0, auditedCount = 0;
rows.forEach((r, i) => {
  const st = statusFor(r.url);
  let title = '', meta = '', aiseo = 'Not Started', metaS = 'Not Started', alt = 'Not Started',
      schema = 'Not Started', dateComp = '', pct = 0, notes = '', imgChanged = '';
  if (st) {
    auditedCount++;
    title = st.p.title.text; meta = st.p.meta.text;
    notes = st.p.notes ? st.p.notes.slice(0, 300) : '';
    if (st.done) {
      doneCount++;
      aiseo = metaS = alt = schema = 'Complete'; pct = 1; dateComp = st.date; imgChanged = 'Yes';
      notes = 'IMPLEMENTED ' + st.date + (notes ? ' — ' + notes : '');
    } else {
      aiseo = metaS = alt = schema = 'Audited'; pct = 0.5;
      notes = 'AUDITED — pending implementation' + (notes ? '. ' + notes : '');
    }
  }
  const row = ws.addRow([
    r.name, r.url, r.type, r.priority, aiseo, metaS, alt, schema, st ? (st.done ? 'Complete' : 'Audited') : 'Not Started',
    st ? 'Start Advertising' : '', '', dateComp, pct, notes, title, meta, imgChanged,
    st && st.done ? 'Billed' : (st ? 'Not Billed' : ''),
    st && st.done ? 'Billed' : (st ? 'Not Billed' : ''),
    st && st.done ? '-' : (st ? 'TBD' : ''), ''
  ]);
  row.eachCell({ includeEmpty: true }, (c, col) => {
    c.font = { name: FONT, size: 9 };
    c.alignment = { vertical: 'top', wrapText: col === 14 || col === 15 || col === 16 };
    c.border = { bottom: { style: 'hair', color: { argb: 'FFCCCCCC' } } };
    if (col === 13) c.numFmt = '0%';
  });
  // Row tint by status
  const fill = st ? (st.done ? GREEN : AMBER) : (i % 2 ? LIGHT : null);
  if (fill) row.eachCell({ includeEmpty: true }, c => { c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: fill } }; });
});

// Column widths
const widths = [30,52,12,9,12,11,12,11,12,16,12,13,10,46,40,46,14,12,13,12,11];
ws.columns.forEach((c, i) => { c.width = widths[i]; });

// Auto-filter across the header
ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: HEADERS.length } };

// ---- Summary sheet ----
const sum = wb.addWorksheet('Summary');
sum.getColumn(1).width = 34; sum.getColumn(2).width = 14;
const sTitle = sum.addRow(['Cleanroom Film & Bags — SEO Project Tracker']);
sTitle.getCell(1).font = { name: FONT, bold: true, size: 14, color: { argb: NAVY } };
sum.addRow(['Prepared by Start Advertising']).getCell(1).font = { name: FONT, italic: true, size: 10, color: { argb: 'FF595959' } };
sum.addRow([]);
const metrics = [
  ['Total pages (sitemap)', rows.length],
  ['Pages audited', auditedCount],
  ['Pages implemented', doneCount],
  ['Pages remaining', rows.length - auditedCount],
  ['% complete (implemented)', { formula: `ROUND(${doneCount}/${rows.length},3)` }]
];
metrics.forEach(([k, v]) => {
  const r = sum.addRow([k, v]);
  r.getCell(1).font = { name: FONT, bold: true, size: 10 };
  r.getCell(2).font = { name: FONT, size: 10 };
  if (k.startsWith('%')) r.getCell(2).numFmt = '0.0%';
});
sum.addRow([]);
const leg = sum.addRow(['Legend']); leg.getCell(1).font = { name: FONT, bold: true, size: 10, color: { argb: NAVY } };
[['Green rows','Implemented in HubSpot'],['Amber rows','Audited — pending implementation'],['Untinted','Not yet audited']]
  .forEach(([a,b]) => { const r = sum.addRow([a,b]); r.getCell(1).font = { name: FONT, size: 9 }; r.getCell(2).font = { name: FONT, size: 9 }; });

wb.xlsx.writeFile('CFB-Existing-URLs-Tracker.xlsx').then(() =>
  console.log(`Tracker written — ${rows.length} URLs, ${auditedCount} audited, ${doneCount} implemented`));
