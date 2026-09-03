const pptxgen = require('C:/Users/richa/AppData/Roaming/npm/node_modules/pptxgenjs');
const pres = new pptxgen();
pres.defineLayout({ name: 'W', width: 13.333, height: 7.5 });
pres.layout = 'W';

const GREEN="215732", GREENL="EAF3E6", MID="C6E0B4", DARK="262626", GRAY="6B7280",
      AMBERB="FFF3D6", AMBERT="8A6D00", WHITE="FFFFFF";

const s = pres.addSlide();
s.background = { color: WHITE };

// ── Header ──────────────────────────────────────────────
s.addText("C-P FLEXIBLE PACKAGING", { x:0.5, y:0.38, w:9, h:0.3, fontFace:"Arial", fontSize:12, bold:true, color:GREEN, charSpacing:2, margin:0 });
s.addText("SEO Progress — 30-Day Snapshot", { x:0.5, y:0.66, w:12.3, h:0.6, fontFace:"Arial", fontSize:34, bold:true, color:DARK, margin:0 });
s.addText("June 24 – July 28, 2026   ·   New HubSpot site (pre-launch)   ·   Prepared by Start Advertising", { x:0.5, y:1.32, w:12.3, h:0.35, fontFace:"Arial", fontSize:13, color:GRAY, margin:0 });

// ── KPI cards ───────────────────────────────────────────
const cards = [
  ["59","Pages Optimized"],
  ["100%","Page-Set Coverage"],
  ["160+","Content Images Alt-Texted"],
  ["51","Structured-Data Blocks"],
];
const cw=2.87, gap=0.22, y0=1.9, ch=1.5;
cards.forEach((c,i)=>{
  const x = 0.5 + i*(cw+gap);
  s.addShape(pres.ShapeType.roundRect, { x, y:y0, w:cw, h:ch, rectRadius:0.09, fill:{color:GREENL}, line:{color:MID, width:1} });
  s.addText(c[0], { x, y:y0+0.18, w:cw, h:0.7, fontFace:"Arial", fontSize:40, bold:true, color:GREEN, align:"center", valign:"middle", margin:0 });
  s.addText(c[1], { x:x+0.1, y:y0+0.92, w:cw-0.2, h:0.5, fontFace:"Arial", fontSize:12, color:DARK, align:"center", valign:"top", margin:0 });
});

// ── Left: Work Delivered ───────────────────────────────
s.addText("Work Delivered This Period", { x:0.5, y:3.62, w:7, h:0.35, fontFace:"Arial", fontSize:16, bold:true, color:GREEN, margin:0 });
const items = [
  "59 page titles rewritten (keyword-focused, ≤70 chars)",
  "59 meta descriptions written (with CTAs, ≤155 chars)",
  "160+ images given descriptive, keyword-aligned alt text",
  "51 structured-data (schema) blocks added",
  "Sitewide certification badges fixed — SQF · AIB · BRCGS",
];
const runs=[];
items.forEach((t,i)=>{
  runs.push({ text:"✓  ", options:{ color:GREEN, bold:true, fontSize:14 } });
  runs.push({ text:t, options:{ color:DARK, fontSize:14, breakLine:true } });
});
s.addText(runs, { x:0.5, y:4.05, w:7.05, h:2.15, fontFace:"Arial", valign:"top", paraSpaceAfter:9, margin:0 });

// ── Right: category chart ──────────────────────────────
s.addText("Pages Optimized by Category", { x:7.95, y:3.62, w:4.9, h:0.35, fontFace:"Arial", fontSize:16, bold:true, color:GREEN, margin:0 });
s.addChart(pres.ChartType.bar, [{
  name:"Pages",
  labels:["Product","Market","Capability","Sustainability","Corporate","Resource"],
  values:[37,9,4,4,4,1],
}], {
  x:7.75, y:3.98, w:5.05, h:2.25, barDir:"bar",
  chartColors:[GREEN], showLegend:false, showTitle:false,
  showValue:true, dataLabelPosition:"outEnd", dataLabelColor:DARK, dataLabelFontSize:11, dataLabelFontBold:true,
  catAxisLabelColor:DARK, catAxisLabelFontSize:11, catGridLine:{style:"none"},
  valAxisHidden:true, valGridLine:{style:"none"}, valAxisLineShow:false,
  barGapWidthPct:45,
});

// ── In-progress callout ────────────────────────────────
s.addShape(pres.ShapeType.roundRect, { x:0.5, y:6.35, w:12.33, h:0.6, rectRadius:0.08, fill:{color:AMBERB}, line:{color:"F0D890", width:1} });
s.addText([
  { text:"PRE-LAUNCH   ", options:{ bold:true, color:AMBERT, fontSize:12 } },
  { text:"New site not yet live — measurement begins at launch.  Next: content expansion, keyword research & new product pages.", options:{ color:DARK, fontSize:12 } },
], { x:0.75, y:6.35, w:11.8, h:0.6, fontFace:"Arial", valign:"middle", margin:0 });

// ── Footer ─────────────────────────────────────────────
s.addText("Prepared by Start Advertising   ·   gcpflexpack.com   ·   Confidential — For C-P Flexible Packaging", { x:0.5, y:7.08, w:12.3, h:0.3, fontFace:"Arial", fontSize:10, color:GRAY, align:"left", margin:0 });

const OUT="C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Garlock/C-P-Flexible-Packaging-Exec-Summary.pptx";
pres.writeFile({ fileName: OUT }).then(f=>console.log("SUCCESS: "+f)).catch(e=>{console.error("FAILED: "+e.message);process.exit(1);});
