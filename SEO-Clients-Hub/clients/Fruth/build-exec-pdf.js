const { PDFDocument, rgb, StandardFonts } = require('pdf-lib');
const fs = require('fs');

// ─── Page: 960×540 pt (16:9 presentation size) ───────────────────────────────
const W = 960, H = 540;

// ─── Colors ──────────────────────────────────────────────────────────────────
const C = {
  navy:    rgb(0.047, 0.133, 0.251),
  red:     rgb(0.769, 0.071, 0.188),
  white:   rgb(1, 1, 1),
  ground:  rgb(0.961, 0.957, 0.945),
  green:   rgb(0.102, 0.490, 0.263),
  blue:    rgb(0.122, 0.361, 0.600),
  amber:   rgb(0.608, 0.369, 0.0),
  slate:   rgb(0.361, 0.439, 0.502),
  dark:    rgb(0.102, 0.153, 0.200),
  border:  rgb(0.867, 0.851, 0.827),
  muted:   rgb(0.710, 0.686, 0.655),
  greenBg: rgb(0.922, 0.969, 0.941),
  blueBg:  rgb(0.929, 0.949, 0.980),
  amberBg: rgb(0.996, 0.965, 0.906),
  redBg:   rgb(0.996, 0.937, 0.941),
};

// ─── Layout constants ─────────────────────────────────────────────────────────
const HDR_H    = 34;    // header bar height
const LEFT_W   = 200;   // left narrative column width
const KPI_H    = 230;   // KPI tiles row height
const BODY_H   = H - HDR_H; // 506
const BTM_H    = BODY_H - KPI_H; // 276 — bottom row height
const RIGHT_W  = W - LEFT_W;    // 760
const KPI_W    = RIGHT_W / 4;   // 190 each
const HALF_W   = RIGHT_W / 2;   // 380 each

// PDF bottom = 0, top = H — all y coords from bottom
const HDR_Y    = 0;           // header sits at very bottom of page (pdf coords)
const BODY_TOP = H;           // top of page
const BODY_BOT = HDR_H;       // bottom of body (above header)
const KPI_TOP  = BODY_TOP;    // KPI row top
const KPI_BOT  = BODY_TOP - KPI_H;
const BTM_TOP  = KPI_BOT;
const BTM_BOT  = HDR_H;

// ─── Helpers ─────────────────────────────────────────────────────────────────
const rect = (page, x, y, w, h, color) =>
  page.drawRectangle({ x, y: H - y - h, width: w, height: h, color });

const hline = (page, x, y, w, color, thick=0.5) =>
  page.drawLine({ start: {x, y: H-y}, end: {x: x+w, y: H-y}, thickness: thick, color });

const vline = (page, x, y1, y2, color, thick=0.5) =>
  page.drawLine({ start: {x, y: H-y1}, end: {x, y: H-y2}, thickness: thick, color });

const text = (page, str, x, y, { font, size=10, color=C.dark }={}) =>
  page.drawText(str, { x, y: H - y, font, size, color });

// ─── Build ────────────────────────────────────────────────────────────────────
(async () => {
  const doc = await PDFDocument.create();

  const fReg  = await doc.embedFont(StandardFonts.Helvetica);
  const fBold = await doc.embedFont(StandardFonts.HelveticaBold);
  const fSerif= await doc.embedFont(StandardFonts.TimesRoman);
  const fSerifI= await doc.embedFont(StandardFonts.TimesRomanItalic);

  const page = doc.addPage([W, H]);

  // ── Page background ──────────────────────────────────────────────────────
  rect(page, 0, 0, W, H, C.ground);

  // ── Header bar (navy, at top) ────────────────────────────────────────────
  rect(page, 0, 0, W, HDR_H, C.navy);
  // Red accent stripe below header
  rect(page, 0, HDR_H, W, 3, C.red);

  // Header text
  text(page, 'FRUTH CUSTOM PACKAGING', 20, 21, { font: fBold, size: 8.5, color: C.white });
  // Red dot separators drawn as small rectangles
  rect(page, 168, 14, 5, 5, C.red);
  text(page, 'SEO PERFORMANCE SUMMARY', 179, 21, { font: fReg, size: 8, color: rgb(1,1,1) });
  rect(page, 340, 14, 5, 5, C.red);
  text(page, 'PREPARED BY START ADVERTISING', 350, 21, { font: fReg, size: 8, color: rgb(1,1,1) });
  text(page, 'MAY 27 – JULY 14, 2026', W - 136, 21, { font: fReg, size: 8, color: rgb(0.7,0.75,0.8) });

  // ── Left narrative column ────────────────────────────────────────────────
  rect(page, 0, HDR_H + 3, LEFT_W, BODY_H - 3, C.white);
  vline(page, LEFT_W, HDR_H + 3, H, C.border, 0.5);

  // Eyebrow
  text(page, 'EXECUTIVE SUMMARY', 18, 60, { font: fBold, size: 7, color: C.red });

  // Headline (serif, two lines)
  text(page, 'Content is working.', 18, 82, { font: fSerif, size: 17, color: C.navy });
  text(page, 'Every key metric is', 18, 103, { font: fSerifI, size: 17, color: C.blue });
  text(page, 'climbing.', 18, 123, { font: fSerifI, size: 17, color: C.blue });

  // Divider
  hline(page, 18, 136, 162, C.border, 0.5);

  // Narrative body
  const narLines = [
    'Twenty-six product pages were',
    'expanded with B2B-focused copy',
    'and structured schema markup.',
    'Google responded immediately --',
    'organic keywords nearly doubled',
    'and traffic rose 30% in 47 days.',
    '',
    'Ranking wins are emerging:',
    '"cleanroom packaging" jumped',
    "80 positions into Google's top 20.",
    '"Autoclavable bags" hit page one.',
    '',
    'As remaining pages go live,',
    'the trend accelerates.',
  ];
  narLines.forEach((line, i) => {
    const bold = line.includes('nearly doubled') || line.includes('30%') ||
                 line.includes('cleanroom') || line.includes('Autoclavable');
    text(page, line, 18, 150 + i * 13.5, {
      font: bold ? fBold : fReg,
      size: 8.5,
      color: bold ? C.dark : C.slate,
    });
  });

  // Attribution
  hline(page, 18, 460, 162, C.border, 0.5);
  text(page, 'DATA SOURCE: UBERSUGGEST', 18, 474, { font: fReg, size: 7, color: C.muted });
  text(page, 'BASELINE: MAY 27, 2026', 18, 486, { font: fReg, size: 7, color: C.muted });

  // ── KPI tiles (top-right zone) ───────────────────────────────────────────
  const kpis = [
    { delta: '+87.9% VS. MAY', value: '62',  label: ['ORGANIC KEYWORDS', 'INDEXED BY GOOGLE'], sub: 'Was ~33 in May', vc: C.green, bg: C.greenBg, accent: C.green, dir: 'up' },
    { delta: '+30.5% VS. MAY', value: '154', label: ['ORGANIC TRAFFIC',  'SESSIONS'],          sub: 'Monthly organic visits', vc: C.green, bg: C.greenBg, accent: C.green, dir: 'up' },
    { delta: '+2.6 PTS VS. MAY', value: '78', label: ['ON-PAGE SEO',     'HEALTH SCORE'],      sub: 'Rated "Great" - Ubersuggest', vc: C.blue, bg: C.blueBg, accent: C.blue, sub2: '/100', dir: 'up' },
    { delta: '-14.5% VS. MAY',  value: '65', label: ['OPEN SEO ISSUES',  ''],                  sub: 'Down from 76 in May', vc: C.amber, bg: C.amberBg, accent: rgb(0.769, 0.502, 0), dir: 'down' },
  ];

  kpis.forEach((k, i) => {
    const x  = LEFT_W + i * KPI_W;
    const tY = HDR_H + 3;
    // White tile bg
    rect(page, x, tY, KPI_W, KPI_H, C.white);
    // Accent top stripe
    rect(page, x, tY, KPI_W, 3, k.accent);
    // Right border
    if (i < 3) vline(page, x + KPI_W, tY, tY + KPI_H, C.border, 0.5);

    // Delta label
    text(page, k.delta, x + 12, tY + 20, { font: fBold, size: 6.5, color: k.vc });
    // Big value
    text(page, k.value, x + 12, tY + 55, { font: fBold, size: 40, color: k.vc });
    if (k.sub2) {
      const vw = fBold.widthOfTextAtSize(k.value, 40);
      text(page, k.sub2, x + 12 + vw + 2, tY + 68, { font: fReg, size: 14, color: C.muted });
    }
    // Labels
    text(page, k.label[0], x + 12, tY + 100, { font: fBold, size: 7, color: C.slate });
    if (k.label[1]) text(page, k.label[1], x + 12, tY + 111, { font: fBold, size: 7, color: C.slate });
    // Sub-label
    text(page, k.sub, x + 12, tY + 126, { font: fReg, size: 7, color: C.muted });
  });

  // Bottom divider between KPI and bottom row
  hline(page, LEFT_W, HDR_H + 3 + KPI_H, RIGHT_W, C.border, 0.5);

  // ── Keyword wins panel (bottom-left of right zone) ───────────────────────
  const wxL = LEFT_W;
  const wxR = LEFT_W + HALF_W;
  const bTop = HDR_H + 3 + KPI_H;

  rect(page, wxL, bTop, HALF_W, BTM_H, C.white);
  vline(page, wxR, bTop, bTop + BTM_H, C.border, 0.5);

  text(page, 'KEYWORD RANKING WINS — CURRENT GOOGLE POSITIONS', wxL + 14, bTop + 18, { font: fBold, size: 6.5, color: C.red });

  const wins = [
    { kw: 'fruth custom packaging',  pos: '#1',  gain: '',     fill: 0.99, color: C.green, gc: C.green },
    { kw: 'bottom seal bags',        pos: '#3',  gain: '',     fill: 0.97, color: C.green, gc: C.green },
    { kw: 'autoclavable bags',       pos: '#9',  gain: '+11',  fill: 0.91, color: C.green, gc: C.green },
    { kw: 'plastic bags for plants', pos: '#11', gain: '+4',   fill: 0.89, color: C.green, gc: C.green },
    { kw: 'cleanroom packaging',     pos: '#20', gain: '+80',  fill: 0.81, color: C.blue,  gc: C.blue  },
    { kw: 'polypropylene grow bag',  pos: '#21', gain: '+6',   fill: 0.80, color: C.blue,  gc: C.blue  },
  ];

  const barTrackW = 145;
  const kwColW    = 142;
  const posColX   = wxL + 14 + kwColW + barTrackW + 8;

  wins.forEach((w, i) => {
    const rowY = bTop + 36 + i * 32;
    // Keyword name
    text(page, w.kw, wxL + 14, rowY, { font: fReg, size: 8, color: C.dark });
    // Bar track
    const bx = wxL + 14 + kwColW;
    const by = rowY - 9;
    rect(page, bx, by - 3, barTrackW, 7, rgb(0.898, 0.882, 0.863));
    rect(page, bx, by - 3, barTrackW * w.fill, 7, w.color);
    // Position + gain
    text(page, w.pos, posColX, rowY, { font: fBold, size: 8, color: w.gc });
    if (w.gain) {
      const pw = fBold.widthOfTextAtSize(w.pos, 8);
      text(page, ' ' + w.gain, posColX + pw, rowY, { font: fBold, size: 6.5, color: w.gc });
    }
  });

  text(page, 'Bar length = rank strength. +N = positions gained since May 27.', wxL + 14, bTop + BTM_H - 12, { font: fReg, size: 6.5, color: C.muted });

  // ── Priorities panel (bottom-right of right zone) ────────────────────────
  rect(page, wxR, bTop, HALF_W, BTM_H, C.ground);

  text(page, 'IMMEDIATE PRIORITIES', wxR + 14, bTop + 18, { font: fBold, size: 6.5, color: C.red });

  const pills = [
    { pill: 'NOW',         pc: C.red,   pb: C.redBg,   head: 'Fix duplicate title tags',         body: 'Lay Flat Bags & Lip Tape Bags share the\nsame title — Google can\'t differentiate them.' },
    { pill: 'NOW',         pc: C.red,   pb: C.redBg,   head: 'Complete HubSpot implementation',  body: '12 pages remain — each one live unlocks\nnew keyword visibility.' },
    { pill: 'SOON',        pc: C.blue,  pb: C.blueBg,  head: 'Add H1 headings to 3 pages',       body: '/capabilities, /fruth-360, /our-story\nare missing heading structure.' },
    { pill: 'OPPORTUNITY', pc: C.green, pb: C.greenBg, head: '"cleanroom packaging" climbing',   body: 'At #20 — one internal link push\ncould move it to page one.' },
  ];

  pills.forEach((p, i) => {
    const rowY = bTop + 36 + i * 57;
    // Pill background
    const pillW = fBold.widthOfTextAtSize(p.pill, 5.5) + 10;
    rect(page, wxR + 14, rowY - 7, pillW, 12, p.pb);
    text(page, p.pill, wxR + 19, rowY + 1, { font: fBold, size: 5.5, color: p.pc });
    // Heading
    text(page, p.head, wxR + 14, rowY + 15, { font: fBold, size: 8, color: C.dark });
    // Body lines
    p.body.split('\n').forEach((line, li) => {
      text(page, line, wxR + 14, rowY + 26 + li * 12, { font: fReg, size: 7.5, color: C.slate });
    });
  });

  // ── Final output ─────────────────────────────────────────────────────────
  const OUT = 'C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/Fruth/Fruth-SEO-Executive-Summary-July2026.pdf';
  const buf = await doc.save();
  fs.writeFileSync(OUT, buf);
  console.log('SUCCESS:', OUT);
})().catch(err => {
  console.error('FAILED:', err.message);
  process.exit(1);
});
