// Generates the Master report as a Word document — the mechanical/technical
// content the app can honestly produce from crawl data (score, findings,
// quick wins, page table), matching what the HTML report already shows.
// Optionally also renders the narrative sections (E-E-A-T, local SEO,
// content strategy, etc.) when a `narrative` object from
// seo-tool/lib/narrative-report.js is passed in — those sections require
// reasoning about the business rather than mechanical derivation from a
// crawl, so they're generated separately and are optional here (a docx is
// still fully valid without them, matching the report Phase 1 shipped).
//
// Generated fresh from the in-memory crawl `results` right after a run
// completes (same lifecycle as the HTML report in webapp/server's `runs`
// Map) rather than reconstructed later from the database — Phase 1
// deliberately doesn't persist page-level results long-term (see
// audit-engine.js's runAudit doc comment / db-storage.js), so a Word export
// of an arbitrary past run isn't available, only of a run just completed.
const {
  Document, Packer, Paragraph, TextRun, ImageRun, AlignmentType,
  Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle,
  Header, Footer, PageNumber, Bookmark, InternalHyperlink,
} = require('docx');
const { getQuickWins, friendlyIssue } = require('./audit-engine');

// Body copy uses Word's own default (Calibri) — set explicitly rather than
// left implicit so it renders consistently in non-Word viewers (Google
// Docs/LibreOffice previews don't always fall back to the same default).
// Headings/titles/table headers use a serif pairing (Cambria, Word's own
// default heading font) so the report reads as designed rather than a
// single-typeface wall of bold text.
const BODY_FONT = 'Calibri';
const HEADING_FONT = 'Cambria';

const CELL_MARGIN = { top: 50, bottom: 50, left: 110, right: 110 };
const THIN_BORDER = { style: BorderStyle.SINGLE, size: 2, color: 'CCCCCC' };
const CELL_BORDERS = { top: THIN_BORDER, bottom: THIN_BORDER, left: THIN_BORDER, right: THIN_BORDER };
const NO_BORDER = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const NO_BORDERS = { top: NO_BORDER, bottom: NO_BORDER, left: NO_BORDER, right: NO_BORDER };

// Shared red/amber/green tiering for a score — used by both the cover's
// big score number and its progress bar below, so the two always agree.
function scoreTierColor(score) {
  return score >= 80 ? '22C55E' : score >= 60 ? 'F59E0B' : 'EF4444';
}

// A cover-page "progress bar" faked with a borderless two-cell table (docx
// has no native shape/rect drawing primitive) — a filled cell proportional
// to the score, next to an unfilled gray remainder. Width is a fraction of
// the page (not the bar's own percentage fill) — the cover uses a slim 40%
// so it reads as an accent under the score rather than a dominant element.
function scoreBarTable(score, widthPct = 60) {
  const pct = Math.max(0, Math.min(100, score));
  const fillColor = scoreTierColor(pct);
  const cell = (w, fill) => new TableCell({
    width: { size: Math.max(1, w), type: WidthType.PERCENTAGE },
    shading: { type: ShadingType.CLEAR, fill },
    borders: NO_BORDERS,
    margins: { top: 60, bottom: 60, left: 0, right: 0 },
    children: [new Paragraph({ children: [] })],
  });
  const cells = pct >= 100 ? [cell(100, fillColor)] : pct <= 0 ? [cell(100, 'E5E7EB')] : [cell(pct, fillColor), cell(100 - pct, 'E5E7EB')];
  return new Table({
    width: { size: widthPct, type: WidthType.PERCENTAGE },
    alignment: AlignmentType.CENTER,
    rows: [new TableRow({ children: cells })],
  });
}

// Running footer on every interior page (suppressed on the cover via the
// section's titlePage flag below) — a thin top rule, the client name/report
// title, and Word's live PageNumber fields so it always reads "Page 3 of 14"
// correctly regardless of final page count.
function reportFooter(client, brandHex) {
  return new Footer({
    children: [
      new Paragraph({
        alignment: AlignmentType.CENTER,
        border: { top: { color: 'E2E8F0', space: 6, style: BorderStyle.SINGLE, size: 4 } },
        spacing: { before: 120 },
        children: [
          new TextRun({ text: `${client.name} — SEO Audit Report`, size: 16, color: '94A3B8' }),
          new TextRun({ text: '   |   Page ', size: 16, color: '94A3B8' }),
          new TextRun({ children: [PageNumber.CURRENT], size: 16, color: '94A3B8' }),
          new TextRun({ text: ' of ', size: 16, color: '94A3B8' }),
          new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 16, color: '94A3B8' }),
        ],
      }),
    ],
  });
}

// Same borderless-table trick as scoreBarTable, but a single full-width
// cell — a thin colored rule closing out the cover, matching the HTML
// report cover's accent-colored bottom border (audit-engine.js's `.cover`).
function accentRuleTable(accentHex) {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [new TableRow({
      children: [new TableCell({
        shading: { type: ShadingType.CLEAR, fill: accentHex },
        borders: NO_BORDERS,
        margins: { top: 40, bottom: 40, left: 0, right: 0 },
        children: [new Paragraph({ children: [] })],
      })],
    })],
  });
}

// Cover logo, scaled to fit a small letterhead-sized box while preserving
// aspect ratio — docx's ImageRun needs explicit width/height (no built-in
// image-dimension reading), so it relies on the natural pixel size captured
// client-side when the logo was uploaded (referral-partners.html) rather
// than parsing the image buffer here. Returns null when there's no logo, or
// its data URL/type isn't one ImageRun can embed (mirrors the server's own
// upload-time allowlist, so this is a belt-and-suspenders check).
const LOGO_MAX_W = 160;
const LOGO_MAX_H = 56;
function coverLogoImageRun(provider) {
  const match = /^data:image\/(png|jpe?g|gif);base64,(.+)$/i.exec(provider.logo || '');
  if (!match || !provider.logoWidth || !provider.logoHeight) return null;
  const type = match[1].toLowerCase() === 'jpeg' ? 'jpg' : match[1].toLowerCase();
  const scale = Math.min(LOGO_MAX_W / provider.logoWidth, LOGO_MAX_H / provider.logoHeight, 1);
  return new ImageRun({
    type,
    data: Buffer.from(match[2], 'base64'),
    transformation: { width: Math.round(provider.logoWidth * scale), height: Math.round(provider.logoHeight * scale) },
  });
}

// Section spacing is intentionally generous (600 before / 240 after, plus a
// brand-colored underline) so sections read as clearly separated, scannable
// blocks in a client-facing report rather than a dense wall of headings.
// Deliberately does NOT use `heading: HeadingLevel.HEADING_1` — the
// built-in Heading 1 style's own color overrides the run's explicit brand
// color in some viewers (observed in Google Drive's docx preview), so the
// heading look is fully hand-formatted here instead of relying on a style.
// `outlineLevel: 0` still gets set (Word's Navigation Pane picks up any
// paragraph's outline level regardless of style, so headings show up there
// for free) but the actual Table of Contents is NOT built from a Word TOC
// field — a field-based TOC only populates once the viewer recalculates
// its fields, which real-world testing showed does not reliably happen on
// first open (Word's own "update fields on open" setting, non-Word
// viewers, etc. — it rendered blank). Instead, `bookmarkId` wraps the
// heading text in a Bookmark that a manually-built TOC (see
// buildDocxReport) links to directly with InternalHyperlink, so the TOC is
// always populated the instant the file is opened, in any viewer.
function sectionHeading(text, brandHex, { excludeFromToc, bookmarkId } = {}) {
  const textRun = new TextRun({ text, bold: true, size: 26, color: brandHex, font: HEADING_FONT });
  return new Paragraph({
    children: [bookmarkId ? new Bookmark({ id: bookmarkId, children: [textRun] }) : textRun],
    spacing: { before: 600, after: 240 },
    border: { bottom: { color: brandHex, space: 4, style: BorderStyle.SINGLE, size: 6 } },
    outlineLevel: excludeFromToc ? undefined : 0,
  });
}

// Every recommendation/finding is rendered as a short bold headline plus one
// lighter supporting sentence — never a single dense paragraph — matching
// {title, detail} objects from narrative-report.js.
function recItem(item) {
  return [
    new Paragraph({ children: [new TextRun({ text: item.title, bold: true, size: 21 })], spacing: { before: 140 } }),
    new Paragraph({ children: [new TextRun({ text: item.detail, size: 20, color: '444444' })], spacing: { after: 60 } }),
  ];
}

// A glyph-prefixed line used in place of Word's native round bullet for
// point-valued lists (score deductions, changes since last audit) — the
// previous version hand-typed a "-8" prefix on top of a separate native
// bullet character, so every line showed two markers. The glyph is colored
// by meaning (red minus for a point loss, green check for a resolution)
// and the paragraph uses a hanging indent so a wrapped second line aligns
// under the text rather than under the glyph.
function glyphLine(glyph, glyphColor, runs, spacing) {
  return new Paragraph({
    indent: { left: 260, hanging: 260 },
    spacing: spacing || { after: 40 },
    children: [new TextRun({ text: `${glyph}  `, bold: true, color: glyphColor, size: 20 }), ...runs],
  });
}

function headerCell(text, brandHex) {
  return new TableCell({
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: 'FFFFFF', size: 18, font: HEADING_FONT })] })],
    shading: { type: ShadingType.CLEAR, fill: brandHex },
    margins: CELL_MARGIN,
    borders: CELL_BORDERS,
  });
}

function bodyCell(text, shaded) {
  return new TableCell({
    children: [new Paragraph({ children: [new TextRun({ text: String(text ?? ''), size: 18 })] })],
    shading: shaded ? { type: ShadingType.CLEAR, fill: 'F2F2F2' } : undefined,
    margins: CELL_MARGIN,
    borders: CELL_BORDERS,
  });
}

function buildDocxReport({ client, results, scoreData, provider, date, crawledAt, narrative, changes, pageSpeed, gbp, dominantPhone, gscKeywords }) {
  const pages = results.filter(r => !r.error);
  const brandHex = (provider.brand || '#003366').replace('#', '');
  const brand2Hex = (provider.brand2 || provider.brand || '#003366').replace('#', '');
  const accentHex = (provider.accent || provider.brand2 || provider.brand || '#003366').replace('#', '');
  const dateLabel = new Date(date + 'T12:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  // The exact crawl time (not just the date) disambiguates which of
  // possibly several same-day runs this report reflects — shown in UTC
  // explicitly rather than implying the reader's own local time, since the
  // server and reader aren't necessarily in the same timezone.
  const crawlTimeLabel = crawledAt
    ? new Date(crawledAt).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', timeZone: 'UTC' }) + ' UTC'
    : null;
  const scoreLabel = scoreData.score >= 85 ? 'Good' : scoreData.score >= 70 ? 'Needs Improvement' : 'Needs Attention';
  const scoreColor = scoreTierColor(scoreData.score);

  const children = [];
  const logoImageRun = coverLogoImageRun(provider);

  // Every real section heading below is created through this instead of
  // calling sectionHeading directly, so it's automatically bookmarked and
  // recorded for the manual Table of Contents built after the whole body
  // is assembled (see the splice near the bottom of this function) — only
  // bothers when the full report (narrative present) will actually get a
  // TOC.
  const tocEntries = [];
  let tocSeq = 0;
  function heading(text) {
    if (!narrative) return sectionHeading(text, brandHex);
    const id = `toc${tocSeq++}`;
    tocEntries.push({ title: text, id });
    return sectionHeading(text, brandHex, { bookmarkId: id });
  }

  // ── Cover ── A restrained stat treatment (small caps eyebrow, one big
  // number, a short qualitative label, then a slim accent bar) reads as a
  // single designed unit rather than a bold sentence competing with its own
  // progress bar for attention. Spacing throughout is tighter than a first
  // pass at this cover — enough to separate elements, not so much that the
  // page feels like it's mostly whitespace.
  children.push(
    new Paragraph({ spacing: { before: logoImageRun ? 800 : 1200 }, children: [] }),
    ...(logoImageRun
      ? [new Paragraph({ alignment: AlignmentType.CENTER, children: [logoImageRun], spacing: { after: 160 } })]
      : []),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: client.name, bold: true, size: 52, color: brandHex, font: HEADING_FONT })],
      spacing: { after: 80 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'SEO Audit Report', size: 26, color: '595959', font: HEADING_FONT })],
      spacing: { after: 320 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: 'SEO HEALTH SCORE', size: 16, color: '94A3B8', characterSpacing: 30 })],
      spacing: { after: 60 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [
        new TextRun({ text: String(scoreData.score), bold: true, size: 64, color: scoreColor, font: HEADING_FONT }),
        new TextRun({ text: ' / 100', size: 26, color: '94A3B8' }),
      ],
      spacing: { after: 40 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: scoreLabel.toUpperCase(), bold: true, size: 20, color: scoreColor, characterSpacing: 20 })],
      spacing: { after: 220 },
    }),
    scoreBarTable(scoreData.score, 40),
    new Paragraph({ spacing: { after: 300 }, children: [] }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `${client.url}`, size: 22 })],
      spacing: { after: 80 },
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      children: [new TextRun({ text: `Audit Date: ${dateLabel}${crawlTimeLabel ? ` at ${crawlTimeLabel}` : ''}  |  Prepared by: ${provider.name}`, size: 20, color: '595959' })],
      spacing: { after: 320 },
    }),
    accentRuleTable(accentHex),
    new Paragraph({ children: [], pageBreakAfter: true }),
  );

  // Table of Contents (full report only — the quick mechanical report is a
  // handful of pages and doesn't need one) gets spliced in here, right
  // after the cover, once every section heading below has been recorded
  // into tocEntries — see the splice near the bottom of this function.
  const tocInsertionIndex = children.length;

  // ── Section: Score summary ──
  children.push(
    heading('Overall SEO Health Score'),
    new Paragraph({ children: [new TextRun({ text: `${scoreData.score} / 100 — ${scoreLabel}`, bold: true, size: 24 })], spacing: { after: 200 } }),
  );
  if (scoreData.deductions.length) {
    children.push(new Paragraph({ children: [new TextRun({ text: 'Score deductions:', bold: true })], spacing: { after: 100 } }));
    for (const d of scoreData.deductions) {
      children.push(glyphLine('−', 'DC2626', [
        new TextRun({ text: `${d.pts} pt${d.pts === 1 ? '' : 's'}`, bold: true, color: 'DC2626', size: 20 }),
        new TextRun({ text: `  ${d.label}`, size: 20, color: '333333' }),
      ]));
    }
  }

  // ── Section: Changes Since Last Audit ──
  if (changes) {
    const prevDateLabel = new Date(changes.previousDate + 'T12:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const delta = changes.scoreDelta;
    const deltaColor = delta > 0 ? '16A34A' : delta < 0 ? 'DC2626' : '64748B';
    const deltaArrow = delta > 0 ? '▲ ' : delta < 0 ? '▼ ' : '';
    const deltaText = delta === 0 ? 'No change' : `${delta > 0 ? '+' : ''}${delta} point${Math.abs(delta) === 1 ? '' : 's'}`;
    children.push(
      heading('Changes Since Last Audit'),
      new Paragraph({
        children: [new TextRun({ text: `${deltaArrow}${deltaText} vs ${prevDateLabel} (was ${changes.previousScore}/100)`, bold: true, size: 24, color: deltaColor })],
        spacing: { after: 200 },
      }),
    );
    if (changes.newIssues.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'New Since Last Audit', bold: true, color: 'DC2626' })], spacing: { after: 80 } }));
      for (const d of changes.newIssues) children.push(glyphLine('−', 'DC2626', [
        new TextRun({ text: `${d.pts} pt${d.pts === 1 ? '' : 's'}`, bold: true, color: 'DC2626', size: 20 }),
        new TextRun({ text: `  ${d.label}`, size: 20, color: '333333' }),
      ]));
    }
    if (changes.resolvedIssues.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'Resolved Since Last Audit', bold: true, color: '16A34A' })], spacing: { before: 160, after: 80 } }));
      for (const d of changes.resolvedIssues) children.push(glyphLine('✓', '16A34A', [
        new TextRun({ text: d.label, size: 20, color: '333333' }),
      ]));
    }
    if (!changes.newIssues.length && !changes.resolvedIssues.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'No individual issues changed since the last audit.', size: 20, color: '64748B' })] }));
    }
  }

  // ── Section: Core Web Vitals ──
  if (pageSpeed) {
    const ratingColor = (r) => r === 'good' ? '16A34A' : r === 'needs-improvement' ? 'D97706' : r === 'poor' ? 'DC2626' : '64748B';
    const ratingLabel = (r) => r === 'good' ? 'Good' : r === 'needs-improvement' ? 'Needs Improvement' : r === 'poor' ? 'Poor' : 'No data';
    const overallRating = pageSpeed.performanceScore == null ? null
      : pageSpeed.performanceScore >= 90 ? 'good' : pageSpeed.performanceScore >= 50 ? 'needs-improvement' : 'poor';
    children.push(
      heading('Core Web Vitals'),
      new Paragraph({
        children: [new TextRun({ text: `Overall Performance Score: ${pageSpeed.performanceScore != null ? pageSpeed.performanceScore : 'N/A'} / 100`, bold: true, size: 22, color: ratingColor(overallRating) })],
        spacing: { after: 100 },
      }),
      new Paragraph({ text: `Largest Contentful Paint: ${pageSpeed.lcp.value != null ? (pageSpeed.lcp.value / 1000).toFixed(1) + 's' : 'N/A'} (${ratingLabel(pageSpeed.lcp.rating)})`, bullet: { level: 0 } }),
      new Paragraph({ text: `Cumulative Layout Shift: ${pageSpeed.cls.value != null ? pageSpeed.cls.value.toFixed(2) : 'N/A'} (${ratingLabel(pageSpeed.cls.rating)})`, bullet: { level: 0 } }),
      new Paragraph({ text: `Interaction to Next Paint: ${pageSpeed.inp && pageSpeed.inp.value != null ? Math.round(pageSpeed.inp.value) + 'ms' : 'Not enough real-world traffic data'}${pageSpeed.inp ? ` (${ratingLabel(pageSpeed.inp.rating)})` : ''}`, bullet: { level: 0 } }),
      new Paragraph({ children: [new TextRun({ text: `Measured on: ${client.url} (homepage, ${pageSpeed.strategy})`, size: 18, italics: true, color: '94A3B8' })], spacing: { before: 100, after: 200 } }),
    );
  }

  // ── Section: Google Business Profile ── (mechanical facts, same
  // direct-display treatment as Core Web Vitals above — not filtered
  // through the LLM narrative, which gets the same data for its own
  // Local SEO commentary but is prose, not a source of record for numbers)
  if (gbp) {
    const phoneMatch = gbp.phoneDigits && dominantPhone ? gbp.phoneDigits === dominantPhone : null;
    children.push(
      heading('Google Business Profile'),
      new Paragraph({
        children: [new TextRun({ text: `Rating: ${gbp.rating != null ? gbp.rating + ' / 5' : 'N/A'}${gbp.reviewCount != null ? ` (${gbp.reviewCount} review${gbp.reviewCount === 1 ? '' : 's'})` : ''}`, bold: true, size: 22 })],
        spacing: { after: 100 },
      }),
      new Paragraph({ text: `Hours listed on profile: ${gbp.hasHours ? 'Yes' : 'No'}`, bullet: { level: 0 } }),
    );
    if (phoneMatch !== null) {
      children.push(new Paragraph({
        children: [new TextRun({
          text: phoneMatch
            ? 'NAP check: website phone number matches Google Business Profile.'
            : `NAP check: website phone number does NOT match Google Business Profile (${gbp.phone}).`,
          bold: !phoneMatch, color: phoneMatch ? '16A34A' : 'DC2626',
        })],
        spacing: { before: 60 },
      }));
    }
    children.push(new Paragraph({ children: [new TextRun({ text: 'Source: Google Business Profile (Places API)', size: 18, italics: true, color: '94A3B8' })], spacing: { before: 100, after: 200 } }));
  }

  // ── Section: Top Ranking Keywords ── (mechanical facts from Google
  // Search Console, search-volume-enriched when Keyword Planner succeeds —
  // same direct-display treatment as Core Web Vitals/GBP above. The
  // narrative's own Content & Keyword Strategy section further down uses
  // this same data for its recommendations, but this table is the source
  // of record for the actual numbers.)
  if (gscKeywords && gscKeywords.length) {
    children.push(
      heading('Top Ranking Keywords'),
      new Paragraph({ children: [new TextRun({ text: 'Last 28 days, by clicks — from Google Search Console', size: 18, italics: true, color: '94A3B8' })], spacing: { after: 120 } }),
    );
    const kwRows = [
      new TableRow({
        children: [headerCell('Keyword', brandHex), headerCell('Clicks', brandHex), headerCell('Impressions', brandHex), headerCell('Avg. Position', brandHex), headerCell('Monthly Searches', brandHex)],
        tableHeader: true,
      }),
      ...gscKeywords.slice(0, 10).map((k, i) => new TableRow({
        children: [
          bodyCell(k.keyword, i % 2 === 1), bodyCell(k.clicks, i % 2 === 1), bodyCell(k.impressions, i % 2 === 1),
          bodyCell(k.position != null ? k.position : 'N/A', i % 2 === 1),
          bodyCell(k.volume != null ? k.volume : '—', i % 2 === 1),
        ],
      })),
    ];
    children.push(new Table({ rows: kwRows, width: { size: 100, type: WidthType.PERCENTAGE } }), new Paragraph({ spacing: { after: 200 }, children: [] }));
  }

  // ── Section: Top Priorities (narrative) ──
  if (narrative && narrative.summary) {
    children.push(
      new Paragraph({ children: [new TextRun({ text: narrative.summary, italics: true, size: 22, color: '333333' })], spacing: { before: 200, after: 100 } }),
    );
  }
  if (narrative && narrative.top_priorities && narrative.top_priorities.length) {
    children.push(heading('Top Priorities'));
    const rows = [
      new TableRow({
        children: [headerCell('Priority', brandHex), headerCell('Impact', brandHex), headerCell('Effort', brandHex)],
        tableHeader: true,
      }),
      ...narrative.top_priorities.map((p, i) => new TableRow({
        children: [bodyCell(p.priority, i % 2 === 1), bodyCell(p.impact, i % 2 === 1), bodyCell(p.effort, i % 2 === 1)],
      })),
    ];
    children.push(new Table({ rows, width: { size: 100, type: WidthType.PERCENTAGE } }));
  }

  // ── Section: Quick Wins ──
  const wins = getQuickWins(pages);
  if (wins.length) {
    children.push(heading('Quick Wins & Recommendations'));
    for (const w of wins) {
      children.push(
        new Paragraph({
          indent: { left: 260, hanging: 260 },
          spacing: { before: 140 },
          children: [
            new TextRun({ text: '●  ', bold: true, color: brandHex, size: 20 }),
            new TextRun({ text: `${w.action} `, bold: true }),
            new TextRun({ text: `(${w.effort} effort, ${w.impact} impact)`, italics: true, color: '595959' }),
          ],
        }),
        new Paragraph({ indent: { left: 260 }, children: [new TextRun({ text: w.detail, size: 20, color: '444444' })], spacing: { after: 60 } }),
      );
    }
  }

  // ── Section: Medium/Long-term recommendations (narrative) ──
  if (narrative && narrative.medium_term && narrative.medium_term.length) {
    children.push(heading('Medium-Term Recommendations (2–8 Weeks)'));
    for (const item of narrative.medium_term) children.push(...recItem(item));
  }
  if (narrative && narrative.long_term && narrative.long_term.length) {
    children.push(heading('Long-Term / Strategic Recommendations'));
    for (const item of narrative.long_term) children.push(...recItem(item));
  }

  // ── Section: Findings by Page ──
  const issuePages = pages
    .filter(p => p.issues.length > 0 || p.warnings.length > 0)
    .sort((a, b) => (b.issues.length * 10 + b.warnings.length) - (a.issues.length * 10 + a.warnings.length));
  if (issuePages.length) {
    children.push(heading(`Findings by Page (${issuePages.length} pages need attention)`));
    for (const p of issuePages) {
      const slug = p.url.replace(client.url.replace(/\/$/, ''), '') || '/';
      children.push(new Paragraph({ children: [new TextRun({ text: slug, bold: true, font: 'Courier New', size: 20 })], spacing: { before: 200, after: 60 } }));
      const items = [...p.issues.map(i => friendlyIssue(i)), ...p.warnings.map(w => friendlyIssue(w))];
      for (const item of items) {
        children.push(new Paragraph({ text: `[${item.priority.toUpperCase()}] ${item.label}`, bullet: { level: 0 } }));
      }
    }
  }

  // ── Section: Content Quality Findings (narrative) ──
  // From actually reading each page's copy (narrative-report.js's
  // contentSample) — thin/generic writing, missing credentials, tone
  // mismatches. Grouped by page, same visual convention as the mechanical
  // "Findings by Page" section above (bold monospace page path), since a
  // page can have more than one content-quality finding.
  if (narrative && narrative.content_quality_findings && narrative.content_quality_findings.length) {
    children.push(heading('Content Quality Findings'));
    const findingsByPage = new Map();
    for (const item of narrative.content_quality_findings) {
      if (!findingsByPage.has(item.page)) findingsByPage.set(item.page, []);
      findingsByPage.get(item.page).push(item);
    }
    for (const [page, items] of findingsByPage) {
      const slug = page.replace(client.url.replace(/\/$/, ''), '') || page;
      children.push(new Paragraph({ children: [new TextRun({ text: slug, bold: true, font: 'Courier New', size: 20 })], spacing: { before: 200, after: 60 } }));
      for (const item of items) children.push(...recItem(item));
    }
  }

  // ── Section: Competitive Analysis (narrative) ──
  // Sorted by priority (impact/effort), same as Quick Wins — the point is
  // "what should I do first", not "what did each competitor prompt". The
  // competitor is cited underneath each item as supporting evidence, not
  // as the organizing structure.
  if (narrative && narrative.competitive_analysis && narrative.competitive_analysis.length) {
    children.push(heading('Competitive Analysis'));
    const impactOrder = { High: 0, Medium: 1, Low: 2 };
    const effortOrder = { Low: 0, Medium: 1, High: 2 };
    const sortedFindings = [...narrative.competitive_analysis].sort((a, b) =>
      impactOrder[a.impact] - impactOrder[b.impact] || effortOrder[a.effort] - effortOrder[b.effort]
    );
    for (const item of sortedFindings) {
      children.push(
        new Paragraph({
          children: [
            new TextRun({ text: `${item.title} `, bold: true, size: 21 }),
            new TextRun({ text: `(${item.effort} effort, ${item.impact} impact)`, italics: true, size: 18, color: '595959' }),
          ],
          spacing: { before: 140 },
        }),
        new Paragraph({ children: [new TextRun({ text: item.detail, size: 20, color: '444444' })], spacing: { after: 20 } }),
        new Paragraph({ children: [new TextRun({ text: `vs. ${item.competitor}`, italics: true, size: 16, color: '94A3B8' })], spacing: { after: 60 } }),
      );
    }
  }

  // ── Section: E-E-A-T & Local SEO (narrative) ──
  if (narrative && (narrative.eeat_signals_present || narrative.eeat_gaps || narrative.local_seo)) {
    children.push(heading('E-E-A-T & Local SEO Analysis'));
    if (narrative.eeat_signals_present && narrative.eeat_signals_present.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'E-E-A-T Signals Present', bold: true, color: brandHex })], spacing: { after: 100 } }));
      for (const item of narrative.eeat_signals_present) children.push(...recItem(item));
    }
    if (narrative.eeat_gaps && narrative.eeat_gaps.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'E-E-A-T Gaps', bold: true, color: brandHex })], spacing: { before: 240, after: 100 } }));
      for (const item of narrative.eeat_gaps) children.push(...recItem(item));
    }
    if (narrative.local_seo) {
      const l = narrative.local_seo;
      children.push(
        new Paragraph({ children: [new TextRun({ text: 'Local SEO', bold: true, color: brandHex })], spacing: { before: 240, after: 100 } }),
        new Paragraph({ text: `Google Business Profile: ${l.google_business_profile}`, bullet: { level: 0 } }),
        new Paragraph({ text: `NAP Consistency: ${l.nap_consistency}`, bullet: { level: 0 } }),
        new Paragraph({ text: `Geographic Targeting: ${l.geographic_targeting}`, bullet: { level: 0 } }),
        new Paragraph({ text: `Local Citations: ${l.local_citations}`, bullet: { level: 0 } }),
      );
    }
  }

  // ── Section: Content & Keyword Strategy (narrative) ──
  if (narrative && narrative.content_keyword_strategy) {
    const cks = narrative.content_keyword_strategy;
    children.push(heading('Content & Keyword Strategy Recommendations'));
    if (cks.keyword_opportunities && cks.keyword_opportunities.length) {
      const rows = [
        new TableRow({
          children: [headerCell('Keyword', brandHex), headerCell('Intent', brandHex), headerCell('Priority Page', brandHex)],
          tableHeader: true,
        }),
        ...cks.keyword_opportunities.map((k, i) => new TableRow({
          children: [bodyCell(k.keyword, i % 2 === 1), bodyCell(k.intent, i % 2 === 1), bodyCell(k.priority_page, i % 2 === 1)],
        })),
      ];
      children.push(new Table({ rows, width: { size: 100, type: WidthType.PERCENTAGE } }));
    }
    if (cks.content_gaps && cks.content_gaps.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'Content Gap Analysis', bold: true, color: brandHex })], spacing: { before: 240, after: 100 } }));
      for (const item of cks.content_gaps) children.push(...recItem(item));
    }
    if (cks.recommended_content && cks.recommended_content.length) {
      children.push(new Paragraph({ children: [new TextRun({ text: 'Recommended Content Pieces', bold: true, color: brandHex })], spacing: { before: 240, after: 100 } }));
      for (const item of cks.recommended_content) children.push(...recItem(item));
    }
  }

  // ── Section: Next Steps (narrative) ──
  if (narrative && narrative.next_steps && narrative.next_steps.length) {
    children.push(heading('Next Steps & Action Plan'));
    narrative.next_steps.forEach((step, i) => {
      children.push(
        new Paragraph({ children: [new TextRun({ text: `${i + 1}. ${step.title}`, bold: true, size: 21 })], spacing: { before: 140 } }),
        new Paragraph({ children: [new TextRun({ text: step.detail, size: 20, color: '444444' })], spacing: { after: 60 } }),
      );
    });
  }

  // ── Section: All Pages table ──
  children.push(heading('All Pages at a Glance'));
  const tableRows = [
    new TableRow({
      children: [headerCell('Page URL', brandHex), headerCell('Title', brandHex), headerCell('H1', brandHex), headerCell('Meta Desc', brandHex), headerCell('Schema', brandHex), headerCell('Image Alts', brandHex)],
      tableHeader: true,
    }),
    ...pages.map((p, i) => new TableRow({
      children: [
        bodyCell(p.url.replace(client.url.replace(/\/$/, ''), '') || '/', i % 2 === 1),
        bodyCell(p.title || 'Missing', i % 2 === 1),
        bodyCell(p.h1Count === 0 ? 'None' : p.h1Count > 1 ? `${p.h1Count} (multiple)` : 'OK', i % 2 === 1),
        bodyCell(p.metaDesc ? 'OK' : 'Missing', i % 2 === 1),
        bodyCell(p.schemaTypes.length ? p.schemaTypes.join(', ') : 'None', i % 2 === 1),
        bodyCell(p.imagesNoAlt > 0 ? `${p.imagesNoAlt} missing` : p.imagesEmptyAlt > 0 ? `${p.imagesEmptyAlt} empty` : 'OK', i % 2 === 1),
      ],
    })),
  ];
  children.push(new Table({ rows: tableRows, width: { size: 100, type: WidthType.PERCENTAGE } }));

  // ── Table of Contents (full report only) ── spliced in now that every
  // section heading above has registered itself into tocEntries. Built as
  // plain clickable links to each heading's Bookmark rather than a Word
  // TOC field — no page numbers (docx has no layout engine to compute
  // them), but every entry is populated and jumps correctly the instant
  // the file is opened, in any viewer, with no dependency on the reader's
  // Word settings.
  if (narrative && tocEntries.length) {
    children.splice(tocInsertionIndex, 0,
      sectionHeading('Table of Contents', brandHex, { excludeFromToc: true }),
      ...tocEntries.map(e => new Paragraph({
        spacing: { after: 100 },
        children: [new InternalHyperlink({
          anchor: e.id,
          children: [new TextRun({ text: e.title, color: brandHex, underline: {}, size: 21 })],
        })],
      })),
      new Paragraph({ children: [], pageBreakAfter: true }),
    );
  }

  // ── Footer note ──
  children.push(
    new Paragraph({
      spacing: { before: 600 },
      children: [new TextRun({ text: `Prepared by ${provider.name} — ${client.name} SEO Audit — ${dateLabel}. Questions? Contact ${provider.email}`, size: 18, color: '94A3B8', italics: true })],
    }),
  );

  const doc = new Document({
    // Without this, Word shows the footer's PAGE/NUMPAGES fields as a
    // stale/empty placeholder until the reader manually selects them and
    // presses F9 — this forces the update to happen automatically the
    // first time the file is opened. (The Table of Contents above is
    // deliberately NOT a Word field for the same reason — see
    // sectionHeading's doc comment.)
    features: { updateFields: true },
    styles: {
      default: {
        document: { run: { font: BODY_FONT, size: 20 } },
      },
    },
    sections: [{
      // titlePage lets the cover use its own (blank) footer below instead
      // of the running "Page X of Y" footer, which would look out of place
      // under the cover's own accent rule.
      properties: { titlePage: true },
      footers: {
        first: new Footer({ children: [new Paragraph({ children: [] })] }),
        default: reportFooter(client, brandHex),
      },
      children,
    }],
  });

  return Packer.toBuffer(doc);
}

// scoreTierColor/scoreBarTable/coverLogoImageRun are also reused by
// build-sales-report.js's Customer Audit Report, so its header stays
// visually identical to this report's own cover rather than drifting into
// a second, slightly-different implementation of the same look.
module.exports = { buildDocxReport, scoreTierColor, scoreBarTable, coverLogoImageRun, accentRuleTable, glyphLine, BODY_FONT, HEADING_FONT };
