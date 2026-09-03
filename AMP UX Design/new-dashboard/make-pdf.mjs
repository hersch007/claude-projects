import PDFDocument from 'pdfkit';
import { createWriteStream } from 'fs';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function makePDF({ outFile, topImg, tableImg, theme }) {
  return new Promise((resolve, reject) => {
    const W = 792, H = 612;

    const DARK = {
      bg: '#0D1117', card: '#161B22', border: '#30363D',
      white: '#E6EDF3', gray: '#7D8590', accent: '#388BFD',
      red: '#F85149', amber: '#D29922', green: '#3FB950',
      footerBg: '#161B22',
    };
    const LIGHT = {
      bg: '#F6F8FA', card: '#FFFFFF', border: '#D1D9E0',
      white: '#1A1F2E', gray: '#656D76', accent: '#0969DA',
      red: '#CF222E', amber: '#9A6700', green: '#1A7F37',
      footerBg: '#F6F8FA',
    };

    const C = theme === 'dark' ? DARK : LIGHT;

    const doc = new PDFDocument({ size: [W, H], margin: 0, autoFirstPage: false });
    const stream = createWriteStream(outFile);
    doc.pipe(stream);

    function fillBg(color) { doc.rect(0, 0, W, H).fill(color); }
    function pill(x, y, w, h, fillColor) { doc.roundedRect(x, y, w, h, h / 2).fill(fillColor); }

    // ── Page 1 ──────────────────────────────────────────────────────────────
    doc.addPage();
    fillBg(C.bg);
    doc.rect(0, 0, W, 3).fill(C.accent);
    doc.rect(0, 0, 4, H).fill(C.accent);

    // Logo square with wireless signal icon
    doc.roundedRect(40, 24, 36, 36, 8).fill(C.accent);
    const cx = 58, cy = 47;
    // Dot
    doc.circle(cx, cy, 2).fill('#FFFFFF');
    // Three signal arcs (small → large)
    doc.lineWidth(1.5).strokeColor('#FFFFFF');
    doc.path(`M ${cx-5},${cy-4} A 6,6 0 0,1 ${cx+5},${cy-4}`).stroke();
    doc.path(`M ${cx-9},${cy-8} A 10,10 0 0,1 ${cx+9},${cy-8}`).stroke();
    doc.path(`M ${cx-13},${cy-12} A 14,14 0 0,1 ${cx+13},${cy-12}`).stroke();

    // Title
    doc.fontSize(26).fillColor(C.white).font('Helvetica-Bold')
       .text('AMP Wireless Permitting System', 86, 26, { lineBreak: false });
    doc.fontSize(11).fillColor(C.gray).font('Helvetica')
       .text('Command Center Dashboard  —  Design Preview', 86, 57, { lineBreak: false });
    doc.fontSize(10).fillColor(C.gray)
       .text('June 2026', W - 100, 40, { lineBreak: false });

    // Theme badge
    const themeLabel = theme === 'dark' ? 'Dark Theme' : 'Light Theme';
    const badgeBg = theme === 'dark' ? '#388BFD22' : '#0969DA18';
    const badgeText = theme === 'dark' ? '#388BFD' : '#0969DA';
    doc.roundedRect(86, 72, 76, 16, 8).fill(badgeBg);
    doc.fontSize(9).fillColor(badgeText).font('Helvetica-Bold')
       .text(themeLabel, 94, 76, { lineBreak: false });

    doc.moveTo(40, 95).lineTo(W - 40, 95).lineWidth(0.5).strokeColor(C.border).stroke();

    const imgH1 = H - 95 - 20 - 10;
    const imgW1 = W - 80;
    doc.image(topImg, 40, 105, { width: imgW1, height: imgH1, fit: [imgW1, imgH1], align: 'center', valign: 'top' });
    doc.roundedRect(40, 105, imgW1, imgH1, 4).lineWidth(0.5).strokeColor(C.border).stroke();
    doc.fontSize(9).fillColor(C.gray).text('1 / 3', W - 50, H - 18, { lineBreak: false });

    // ── Page 2 ──────────────────────────────────────────────────────────────
    doc.addPage();
    fillBg(C.bg);
    doc.rect(0, 0, W, 3).fill(C.accent);
    doc.rect(0, 0, 4, H).fill(C.accent);

    doc.fontSize(22).fillColor(C.white).font('Helvetica-Bold')
       .text('Project Table', 40, 26, { lineBreak: false });
    doc.fontSize(11).fillColor(C.gray).font('Helvetica')
       .text('All Projects View  —  Sortable · Filterable · Urgency-coded', 40, 53, { lineBreak: false });

    // Status pills
    const statuses = [
      { label: 'NTP',          color: C.red },
      { label: 'Permitting',   color: C.amber },
      { label: 'Construction', color: C.accent },
      { label: 'Closeout',     color: C.green },
      { label: 'On Hold',      color: C.gray },
      { label: 'Submitted',    color: theme === 'dark' ? '#A371F7' : '#8250DF' },
    ];
    let lx = W - 40;
    [...statuses].reverse().forEach(s => {
      const tw = doc.fontSize(8).widthOfString(s.label);
      const pw = tw + 20;
      lx -= pw + 5;
      pill(lx, 30, pw, 16, s.color + '33');
      doc.fontSize(8).fillColor(s.color).font('Helvetica-Bold')
         .text(s.label, lx + 10, 35, { lineBreak: false });
    });

    doc.moveTo(40, 72).lineTo(W - 40, 72).lineWidth(0.5).strokeColor(C.border).stroke();

    const imgH2 = H - 82 - 20;
    const imgW2 = W - 80;
    doc.image(tableImg, 40, 82, { width: imgW2, height: imgH2, fit: [imgW2, imgH2], align: 'center', valign: 'top' });
    doc.roundedRect(40, 82, imgW2, imgH2, 4).lineWidth(0.5).strokeColor(C.border).stroke();
    doc.fontSize(9).fillColor(C.gray).text('2 / 3', W - 50, H - 18, { lineBreak: false });

    // ── Page 3 — Design Notes ───────────────────────────────────────────────
    doc.addPage();
    fillBg(C.bg);
    doc.rect(0, 0, W, 3).fill(C.accent);
    doc.rect(0, 0, 4, H).fill(C.accent);

    const LX = 60, RX = W / 2 + 20;

    doc.fontSize(26).fillColor(C.white).font('Helvetica-Bold')
       .text('Design Notes', LX, 36);
    doc.fontSize(11).fillColor(C.gray).font('Helvetica')
       .text('What changed and why it matters', LX, 68);
    doc.moveTo(LX, 86).lineTo(W - LX, 86).lineWidth(0.5).strokeColor(C.border).stroke();

    const themeNote = theme === 'dark'
      ? { title: 'Dark Theme', body: 'Near-black background reduces eye fatigue for ops staff who monitor this dashboard all day in controlled lighting environments. High-contrast urgency signals pop clearly.' }
      : { title: 'Light Theme with Dark Accents', body: 'Soft #F6F8FA surface with white cards — professional, high-contrast, and optimized for bright offices, vehicles, and outdoor tablet use in the field.' };

    const notes = [
      { color: C.red,    title: 'Shot Clock Rail',       body: 'Critical deadlines are the hero element — surfaced at the top, color-coded red/amber/green with a live progress bar. Zero clicks to see what\'s on fire today.' },
      { color: C.amber,  title: 'Action Required Panel', body: 'All pending forms and approvals in one panel, sorted by urgency. Replaces hunting through separate modules for what needs signing or submitting.' },
      { color: C.accent, title: 'Portfolio Snapshot',    body: 'Live stacked bar + per-stage breakdown shows where the pipeline bottleneck is at a glance — no report needed.' },
      { color: C.green,  title: 'Project Table',         body: 'Sortable, filterable with 6 focused columns replacing 10+ cramped ones. Quick-filter pills cut instantly to Critical, Construction, Permitting, etc.' },
      { color: theme === 'dark' ? '#A371F7' : '#8250DF', title: themeNote.title, body: themeNote.body },
      { color: C.gray,   title: 'Collapsible Sidebar',   body: 'Nav collapses to icon-only mode, maximizing the content area on 10" field tablets. Full navigation always one click away.' },
    ];

    function drawNotes(list, startX, startY) {
      let y = startY;
      list.forEach(n => {
        doc.circle(startX + 6, y + 8, 5).fill(n.color + '44');
        doc.circle(startX + 6, y + 8, 3).fill(n.color);
        doc.fontSize(12).fillColor(C.white).font('Helvetica-Bold')
           .text(n.title, startX + 18, y, { lineBreak: false });
        y += 18;
        doc.fontSize(9.5).fillColor(C.gray).font('Helvetica')
           .text(n.body, startX + 18, y, { width: W / 2 - LX - 16, lineBreak: true });
        y = doc.y + 14;
      });
    }

    drawNotes(notes.slice(0, 3), LX, 100);
    drawNotes(notes.slice(3), RX, 100);

    // Footer
    doc.rect(0, H - 34, W, 34).fill(C.footerBg);
    doc.moveTo(0, H - 34).lineTo(W, H - 34).lineWidth(0.5).strokeColor(C.border).stroke();
    doc.fontSize(9).fillColor(C.gray).font('Helvetica')
       .text(`AMP Wireless Permitting System  |  Command Center Redesign (${themeLabel})  |  Confidential Draft`, LX, H - 20, { lineBreak: false });
    doc.fontSize(9).fillColor(C.gray)
       .text('3 / 3', W - 50, H - 20, { lineBreak: false });

    doc.end();
    stream.on('finish', resolve);
    stream.on('error', reject);
  });
}

const base = path.join(__dirname, '..');

await makePDF({
  outFile: path.join(base, 'AMP Dashboard Preview - Dark.pdf'),
  topImg:   path.join(__dirname, 'screenshot-dark-top.png'),
  tableImg: path.join(__dirname, 'screenshot-dark-table.png'),
  theme: 'dark',
});
console.log('Saved Dark PDF');

await makePDF({
  outFile: path.join(base, 'AMP Dashboard Preview - Light.pdf'),
  topImg:   path.join(__dirname, 'screenshot-top.png'),
  tableImg: path.join(__dirname, 'screenshot-table.png'),
  theme: 'light',
});
console.log('Saved Light PDF');
