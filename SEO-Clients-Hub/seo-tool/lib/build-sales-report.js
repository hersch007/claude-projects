// Generates a short, one-page "SEO Snapshot" Word document meant to be sent
// to a prospect to get them to buy a full engagement — not the mechanical
// Master report build-docx-report.js produces. Deliberately built from only
// what a stored audit_runs row actually has (seo_health_score, deductions,
// pages_crawled, run_date — see webapp/server/index.js's GET
// .../audit-run/:id/sales-report route), so it can be regenerated for any
// past run, not just a crawl that just completed in memory. This means it
// can't use per-page detail (title/meta/alt text, which page did what) or
// narrative sections — Phase 1 never persists those — only the same
// score/deductions data already shown in the app's own history table.
//
// The whole point is to look credible (real, specific numbers) while
// showing only a taste of what was found — the top few point-costing
// issues, not the full list — so there's an obvious reason to buy the full
// audit rather than everything already being on the page for free.
const { Document, Packer, Paragraph, TextRun, AlignmentType, Footer } = require('docx');
const {
  scoreTierColor, scoreBarTable, coverLogoImageRun, accentRuleTable, glyphLine, BODY_FONT, HEADING_FONT,
} = require('./build-docx-report');

const TOP_FINDINGS_SHOWN = 3;

function buildSalesReport({ client, provider, score, pagesCrawled, deductions, date }) {
  const brandHex = (provider.brand || '#003366').replace('#', '');
  const accentHex = (provider.accent || provider.brand2 || provider.brand || '#003366').replace('#', '');
  const dateLabel = new Date(date + 'T12:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const scoreLabel = score >= 85 ? 'Good' : score >= 70 ? 'Needs Improvement' : 'Needs Attention';
  const scoreColor = scoreTierColor(score);
  const logoImageRun = coverLogoImageRun(provider);

  // Highest point-cost first — these are the findings most worth leading
  // with, and the ones a prospect is most likely to recognize as a real,
  // fixable problem rather than an obscure technicality.
  const sorted = [...(deductions || [])].sort((a, b) => b.pts - a.pts);
  const topFindings = sorted.slice(0, TOP_FINDINGS_SHOWN);
  const remainingCount = sorted.length - topFindings.length;

  const children = [
    new Paragraph({ spacing: { before: logoImageRun ? 500 : 700 }, children: [] }),
    ...(logoImageRun
      ? [new Paragraph({ alignment: AlignmentType.CENTER, children: [logoImageRun], spacing: { after: 140 } })]
      : []),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: client.name, bold: true, size: 44, color: brandHex, font: HEADING_FONT })],
      spacing: { after: 60 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'Free SEO Snapshot', size: 24, color: '595959', font: HEADING_FONT })],
      spacing: { after: 260 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'SEO HEALTH SCORE', size: 15, color: '94A3B8', characterSpacing: 30 })],
      spacing: { after: 50 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [
        new TextRun({ text: String(score), bold: true, size: 56, color: scoreColor, font: HEADING_FONT }),
        new TextRun({ text: ' / 100', size: 22, color: '94A3B8' }),
      ],
      spacing: { after: 30 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: scoreLabel.toUpperCase(), bold: true, size: 18, color: scoreColor, characterSpacing: 20 })],
      spacing: { after: 180 },
    }),
    scoreBarTable(score, 40),
    new Paragraph({ spacing: { after: 240 }, children: [] }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: client.url, size: 20 })],
      spacing: { after: 60 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `Scanned ${pagesCrawled} page${pagesCrawled === 1 ? '' : 's'} on ${dateLabel}`, size: 18, color: '595959' })],
      spacing: { after: 280 },
    }),
    accentRuleTable(accentHex),
    new Paragraph({ spacing: { before: 320, after: 160 }, children: [new TextRun({ text: 'What We Found', bold: true, size: 26, color: brandHex, font: HEADING_FONT })] }),
  ];

  if (topFindings.length) {
    children.push(new Paragraph({
      spacing: { after: 140 },
      children: [new TextRun({ text: `A quick scan already turned up ${sorted.length} issue${sorted.length === 1 ? '' : 's'} affecting search visibility. The most significant:`, size: 20, color: '333333' })],
    }));
    for (const d of topFindings) {
      children.push(glyphLine('−', 'DC2626', [
        new TextRun({ text: `${d.pts} pt${d.pts === 1 ? '' : 's'}`, bold: true, color: 'DC2626', size: 20 }),
        new TextRun({ text: `  ${d.label}`, size: 20, color: '333333' }),
      ]));
    }
    if (remainingCount > 0) {
      children.push(new Paragraph({
        spacing: { before: 140, after: 200 },
        children: [new TextRun({ text: `...plus ${remainingCount} more issue${remainingCount === 1 ? '' : 's'} identified, covered in a full audit.`, italics: true, size: 19, color: '64748B' })],
      }));
    }
  } else {
    children.push(new Paragraph({
      spacing: { after: 200 },
      children: [new TextRun({ text: 'Nothing costing points on this scan — a strong technical baseline. A full audit digs into content strategy, keyword opportunity, and competitive positioning, which this snapshot doesn\'t cover.', size: 20, color: '333333' })],
    }));
  }

  children.push(
    new Paragraph({ spacing: { before: 200, after: 100 }, children: [new TextRun({ text: 'Want the full picture?', bold: true, size: 22, color: brandHex })] }),
    new Paragraph({
      spacing: { after: 100 },
      children: [new TextRun({
        text: `A full ${provider.name} SEO audit covers every page, a prioritized fix list, and a plan to turn these into real ranking gains. Let's talk.`,
        size: 20, color: '333333',
      })],
    }),
    new Paragraph({
      children: [new TextRun({ text: provider.email || '', bold: true, size: 20, color: brandHex })],
    }),
  );

  const doc = new Document({
    styles: {
      default: {
        document: { run: { font: BODY_FONT, size: 20 } },
      },
    },
    sections: [{
      properties: { titlePage: true },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: `${provider.name} — Free SEO Snapshot — ${dateLabel}`, size: 15, color: '94A3B8' })],
          })],
        }),
      },
      children,
    }],
  });

  return Packer.toBuffer(doc);
}

module.exports = { buildSalesReport };
