// Generates the "Customer Audit Report" — a document meant to be shown to
// a prospect to make the case that they need help, not a teaser that
// withholds detail. It shows the score, how far that score is from
// "Good," the findings grouped into categories, and the actual highest-
// impact issues — so the case makes itself instead of relying on a wall
// of raw stat tiles.
//
// Rendered as HTML/CSS printed to PDF via headless Chromium (Puppeteer),
// not assembled from docx tables — a real report design (rounded cards,
// shadows, a gradient cover, an actual SVG score ring) needs real CSS.
// Word has no shape/rect/arc primitives, so every "card" or "ring" in the
// old docx version was a colored table cell faking a shape, and kept
// reading as "cheap"/"clunky" no matter how much styling effort went into
// it — that's this file's second full rewrite of this report for exactly
// that reason (see git history). The cover is one gradient page; every
// page after it is plain white — deliberately not a dark theme
// throughout, both for print cost/legibility and because one bold cover
// reads as designed without the whole document needing to carry it.
//
// Deliberately built from only what a stored audit_runs row actually has
// (seo_health_score, pages_crawled, html_report, deductions — see
// webapp/server/index.js's GET .../audit-run/:id/sales-report route), so
// it can be regenerated for any past run, not just a crawl that just
// completed in memory (see build-docx-report.js's own doc comment for why
// the Master report can't do this). The category grid isn't stored as
// structured data anywhere — it's parsed back out of the stored
// html_report, the one place every one of those counts already lives for
// a saved run.
const cheerio = require('cheerio');
const puppeteer = require('puppeteer-core');
// @sparticuz/chromium ships a Chromium build packaged specifically for
// restrictive managed containers (originally built for AWS Lambda, widely
// reused on Render/Railway/etc.) that don't have the desktop shared
// libraries (libnss3, libatk, ...) a normal Puppeteer-bundled Chromium
// download expects — plain `puppeteer` worked in local/sandbox testing but
// failed to launch once deployed to Render for exactly that reason. It's
// published as an ESM package; `.default` is its CJS interop export.
const chromium = require('@sparticuz/chromium').default;

const GOOD_THRESHOLD = 85; // matches audit-engine.js's own scoreLabel tiering

// The number of distinct check *types* calcScore() can flag — one count
// per `deduct()` call site in lib/audit-engine.js (missing title, duplicate
// H1, broken links, Open Graph, sitemap, etc.). No single source of truth
// exports this today, so it's a plain constant — re-count
// `grep -c "if (.*deduct(" lib/audit-engine.js` and update this if
// calcScore() gains or loses a check.
const TOTAL_CHECK_TYPES = 33;

// Same red/amber/green thresholds as build-docx-report.js's scoreTierColor
// — duplicated rather than imported since this file no longer shares any
// rendering code with the docx-based Master report (entirely different
// medium now), and it's three lines.
function scoreTierColor(score) {
  return score >= 80 ? '#16A34A' : score >= 60 ? '#D97706' : '#DC2626';
}

function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

// Pulls the exact same stat tiles a client already sees on the on-screen
// quick report back out of that report's stored HTML — `.snap-card` wraps
// a `.snap-num` (the count, classed good/warn/bad/neutral) and a
// `.snap-label` (its name). See audit-engine.js's buildReport() for the
// markup this depends on; if that ever changes, this needs to move with
// it (there's no other source for these per-category counts on a past
// run — see this file's top doc comment).
function parseStatGrid(html) {
  const $ = cheerio.load(html || '');
  const grid = [];
  $('.snap-card').each((i, el) => {
    const numEl = $(el).find('.snap-num').first();
    const value = numEl.text().trim();
    const label = $(el).find('.snap-label').first().text().trim();
    if (!label) return;
    const classes = (numEl.attr('class') || '').split(/\s+/);
    const status = classes.find(c => c !== 'snap-num') || 'neutral';
    grid.push({ label, value, status });
  });
  return grid;
}

// Groups the flat 27 pass/fail/warn tiles (see parseStatGrid above) into a
// handful of named categories, the same idea as a competitor report's
// weighted-category cards. There's no category taxonomy anywhere else in
// this codebase — the on-screen report only ever shows the flat grid — so
// this is a hand-built mapping from each tile's exact `.snap-label` text
// (audit-engine.js's buildReport()) to a category name; a label that isn't
// listed here falls through uncategorized and is dropped from the category
// cards (currently only "Fully Healthy Pages" and "Total Pages Audited",
// which are informational counts, not a pass/fail judgment, so they
// wouldn't belong in a category score anyway).
const CATEGORIES = [
  { name: 'Titles & Meta Tags', labels: ['Pages Missing Title', 'Missing Meta Description', 'Poorly Sized Title Tags', 'Duplicate Title Tags', 'Duplicate Meta Descriptions', 'Multiple Title/Meta Tags'] },
  { name: 'Headings', labels: ['Pages Missing H1', 'Pages w/ Multiple H1s', 'Duplicate H1 Tags', 'Skipped Heading Levels'] },
  { name: 'Structured Data', labels: ['Pages Without Schema', 'Invalid Schema Markup', 'Incomplete LocalBusiness Schema'] },
  { name: 'Images', labels: ['Pages w/ Missing Image Alt', 'Images Missing Dimensions'] },
  { name: 'Technical & Crawlability', labels: ['Missing Canonical Tag', 'Sitemap.xml Found', 'Pages Set to Noindex', 'Blocked by robots.txt', 'Missing Viewport Tag', 'Missing HTML Lang', 'No HTTP Compression', 'Mixed-Content Resources'] },
  { name: 'Links & Site Structure', labels: ['Pages w/ Broken Internal Links', 'Orphan Pages'] },
  { name: 'Content & Trust', labels: ['Difficult-to-Read Pages', 'Inconsistent Phone Number'] },
];

// A plain-English business-consequence sentence per category — deliberately
// never a fix, just what the flagged state is actually costing the
// business, since this report exists to make a prospect want the provider's
// help, not to hand them a free to-do list. Only shown when a category has
// at least one fail/warn (see categoryCardsHtml below); a clean category
// gets no editorializing, just its passing count.
const CATEGORY_CONSEQUENCE = {
  'Titles & Meta Tags': 'Google and your prospects see confusing, missing, or duplicated information before they even click — a lot of them never make it to your site at all.',
  'Headings': "Search engines can't tell what your pages are actually about, and neither can someone skimming your content — both leave confused.",
  'Structured Data': "Google can't verify who you are, what you do, or where you're located — so it shows a competitor's listing with real trust signals instead of yours.",
  'Images': "Search engines can't see what's in your images, and neither can visitors using assistive technology — that's lost visibility and lost customers.",
  'Technical & Crawlability': 'Parts of your site may be invisible to Google entirely — pages nobody can find in search, no matter how good the content is.',
  'Links & Site Structure': 'Visitors and search engines are hitting dead ends on your own website — trust and rankings leaking out with every broken click.',
  'Content & Trust': "Visitors land on your site and can't quite tell the business is legitimate — and they leave for one that looks like it is.",
};

// Builds each category's score (percent of its checks that came back
// "good") from the parsed grid — a straightforward, defensible proxy: it
// never asserts a verdict the grid's own good/warn/bad coloring didn't
// already make, just rolls several tiles' worth of it up into one number
// per category. Categories with zero matching tiles (a very old stored
// report whose label text predates one of these) are skipped rather than
// shown as a false 100.
function buildCategoryScores(grid) {
  const byLabel = new Map(grid.map(g => [g.label, g]));
  return CATEGORIES.map(cat => {
    const items = cat.labels.map(l => byLabel.get(l)).filter(Boolean);
    if (!items.length) return null;
    const fail = items.filter(i => i.status === 'bad').length;
    const warn = items.filter(i => i.status === 'warn').length;
    const good = items.filter(i => i.status === 'good').length;
    const total = items.length;
    const score = Math.round((good / total) * 100);
    // "2 fail · 4/6 passed" reads like a page count right under "113 pages
    // scanned" on the cover, but it's a count of check *types*, not pages —
    // confusing on its own. The real per-page impact of a category's
    // flagged checks (e.g. "12 pages" from a "12 page(s) missing meta
    // description" tile) is a much more concrete, and more persuasive,
    // number to put next to it. Non-numeric tile values (the "Yes"/"No" of
    // "Sitemap.xml Found," a site-wide flag, not a page count) contribute
    // 0 rather than NaN.
    const affectedPages = items
      .filter(i => i.status === 'bad' || i.status === 'warn')
      .reduce((sum, i) => { const n = parseInt(i.value, 10); return sum + (Number.isFinite(n) ? n : 0); }, 0);
    return { name: cat.name, fail, warn, good, total, score, affectedPages };
  }).filter(Boolean);
}

// Picks the single most damaging category — lowest score first, breaking a
// tie by whichever has more fail/warn tiles — so the report can lead with
// one prominent, scary headline finding instead of asking the reader to
// scan a grid of cards to figure out what's worst themselves. Returns null
// when every category is clean (score 100), since there's nothing to lead
// with in that case.
function pickWorstCategory(categories) {
  const flagged = categories.filter(c => c.score < 100);
  if (!flagged.length) return null;
  return flagged.sort((a, b) => a.score - b.score || (b.fail * 2 + b.warn) - (a.fail * 2 + a.warn))[0];
}

// A real SVG ring gauge for the cover's score badge — the one thing the
// previous docx version explicitly couldn't do (Word has no arc/donut
// primitive). stroke-dasharray draws the filled arc proportional to score;
// the unfilled remainder is a translucent white track.
function scoreRingSvg(score, color) {
  const r = 52;
  const circumference = 2 * Math.PI * r;
  const filled = Math.max(0, Math.min(100, score)) / 100 * circumference;
  return `
    <svg width="140" height="140" viewBox="0 0 120 120">
      <circle cx="60" cy="60" r="${r}" fill="none" stroke="rgba(255,255,255,0.18)" stroke-width="10" />
      <circle cx="60" cy="60" r="${r}" fill="none" stroke="${color}" stroke-width="10"
        stroke-linecap="round" stroke-dasharray="${filled.toFixed(1)} ${circumference.toFixed(1)}"
        transform="rotate(-90 60 60)" />
    </svg>`;
}

function coverStatsHtml(stats) {
  return stats.map(s => `
    <div class="cover-stat">
      <div class="cover-stat-label">${escapeHtml(s.label.toUpperCase())}</div>
      <div class="cover-stat-value">${escapeHtml(s.value)}</div>
    </div>`).join('');
}

function categoryCardsHtml(categories) {
  return categories.map(cat => {
    const tier = scoreTierColor(cat.score);
    const flagged = cat.fail > 0 || cat.warn > 0;
    const parts = [];
    if (flagged) {
      const flaggedChecks = cat.fail + cat.warn;
      parts.push(`${flaggedChecks} of ${cat.total} check${cat.total === 1 ? '' : 's'} flagged`);
      if (cat.affectedPages > 0) parts.push(`${cat.affectedPages} page${cat.affectedPages === 1 ? '' : 's'} affected`);
    } else {
      parts.push(`${cat.good}/${cat.total} passed`);
    }
    const consequence = flagged && CATEGORY_CONSEQUENCE[cat.name]
      ? `<div class="category-consequence">${escapeHtml(CATEGORY_CONSEQUENCE[cat.name])}</div>`
      : '';
    return `
      <div class="category-card" style="border-left-color:${tier}">
        <div class="category-score" style="color:${tier}">${cat.score}</div>
        <div class="category-body">
          <div class="category-name">${escapeHtml(cat.name)}</div>
          <div class="category-detail">${escapeHtml(parts.join(' · '))}</div>
          ${consequence}
        </div>
      </div>`;
  }).join('');
}

// The single biggest-risk finding, called out on its own before anything
// else on page 2 — one big scary headline lands harder than asking the
// reader to notice it buried in a grid of category cards. Reuses the same
// CATEGORY_CONSEQUENCE copy the card itself would show; this is just a
// louder, standalone presentation of it.
function worstFindingCallout(cat) {
  const consequence = CATEGORY_CONSEQUENCE[cat.name] || '';
  return `
    <div class="worst-finding">
      <div class="worst-finding-eyebrow">YOUR BIGGEST RISK RIGHT NOW</div>
      <div class="worst-finding-name">${escapeHtml(cat.name)}</div>
      <div class="worst-finding-consequence">${escapeHtml(consequence)}</div>
    </div>`;
}

function issueCalloutsHtml(issues) {
  return issues.map(d => {
    const severe = d.pts >= 10;
    const accent = severe ? '#DC2626' : '#D97706';
    return `
      <div class="issue-callout" style="border-left-color:${accent}">
        <div class="issue-title">${escapeHtml(d.label)}</div>
        <div class="issue-detail">-${d.pts} point${d.pts === 1 ? '' : 's'} off your SEO Health Score</div>
      </div>`;
  }).join('');
}

function renderHtml({ client, provider, brandHex, accentHex, score, scoreColor, scoreLabel, dateLabel, pagesCrawled, grid, categories, pillCounts, topIssues, totalChecksRun, problemCount }) {
  const logoImg = /^data:image\/(png|jpe?g|gif);base64,/i.test(provider.logo || '')
    ? `<img class="cover-logo" src="${provider.logo}" alt="" />`
    : '';

  const stats = [
    { label: 'Report Date', value: dateLabel },
    { label: 'Pages Scanned', value: String(pagesCrawled) },
    { label: 'Categories Flagged', value: grid.length ? `${problemCount} of ${grid.length}` : '—' },
    { label: 'Checks Performed', value: totalChecksRun.toLocaleString() },
  ];

  const whereYouShouldBeHtml = score < GOOD_THRESHOLD
    ? `Sites that rank well typically score <strong class="good-text">${GOOD_THRESHOLD}+ (Good)</strong>. Right now ${escapeHtml(client.name)} is <strong class="bad-text">${GOOD_THRESHOLD - score} point${GOOD_THRESHOLD - score === 1 ? '' : 's'} away</strong> from that threshold — every point below it is a real reason a competitor outranks you in search.`
    : (100 - score > 0
      ? `${escapeHtml(client.name)} is already in the <strong class="good-text">Good</strong> range — but there's still <strong class="bad-text">${100 - score} point${100 - score === 1 ? '' : 's'}</strong> between here and a perfect, fully-optimized site. Here's exactly what's left:`
      : `A perfect technical score — genuinely rare. The findings below are the remaining, lower-priority polish items.`);

  const categorySectionHtml = categories.length
    ? `<div class="category-grid">${categoryCardsHtml(categories)}</div>`
    : `<p class="muted-note">A detailed category breakdown isn't available for this specific run, but the score above reflects a real, full scan of the site.</p>`;

  const worstCategory = pickWorstCategory(categories);
  const worstFindingHtml = worstCategory ? worstFindingCallout(worstCategory) : '';

  const topIssuesSectionHtml = topIssues.length ? `
    <div class="section-band" style="background:${brandHex}">TOP ISSUES TO FIX</div>
    <div class="section-body">
      <div class="issue-list">${issueCalloutsHtml(topIssues)}</div>
    </div>` : '';

  return `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<style>
  @page { size: Letter; margin: 0; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Georgia, 'Times New Roman', serif; color: #1E293B; }
  .cover {
    width: 8.5in; height: 11in; padding: 0.7in 0.75in;
    background: linear-gradient(135deg, ${brandHex} 0%, #0B1D3A 100%);
    color: #fff; display: flex; flex-direction: column;
    page-break-after: always;
  }
  .cover-header { display: flex; justify-content: space-between; align-items: flex-start; }
  .eyebrow { font-size: 11px; letter-spacing: 2px; color: #B9C6DC; text-transform: uppercase; padding-top: 40px; }
  .score-badge { position: relative; width: 140px; height: 140px; text-align: center; }
  .score-badge-inner { position: absolute; top: 0; left: 0; width: 140px; height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
  .score-badge-num { font-size: 34px; font-weight: bold; font-family: Georgia, serif; line-height: 1; }
  .score-badge-label { font-size: 8px; letter-spacing: 1.5px; color: #DCE4F2; margin-top: 4px; }
  .score-badge-tier { font-size: 10px; font-weight: bold; margin-top: 3px; }
  .cover-middle { flex: 1; display: flex; flex-direction: column; justify-content: center; }
  .cover-logo { max-height: 48px; max-width: 200px; margin-bottom: 24px; }
  .client-name { font-size: 42px; font-weight: bold; margin: 0 0 8px; line-height: 1.1; }
  .client-url { font-size: 15px; color: #B9C6DC; }
  .cover-stats { display: flex; gap: 30px; }
  .cover-stat { flex: 1; }
  .cover-stat-label { font-size: 10px; letter-spacing: 1.5px; color: #8CA0C4; margin-bottom: 6px; }
  .cover-stat-value { font-size: 20px; font-weight: bold; }

  .page { padding: 0.5in 0.7in; }
  .pill-row { display: flex; gap: 12px; margin-top: 26px; max-width: 4.6in; }
  .pill { flex: 1; text-align: center; border-radius: 10px; padding: 10px 8px; font-weight: bold; font-size: 14px; }
  .pill-bad { background: #FEE2E2; color: #DC2626; }
  .pill-warn { background: #FEF3C7; color: #D97706; }
  .pill-good { background: #DCFCE7; color: #16A34A; }
  .pill span.n { font-size: 18px; }

  .section-band {
    color: #fff; font-weight: bold; font-size: 13px; letter-spacing: 2px;
    padding: 10px 18px; border-radius: 8px; margin: 20px 0 12px;
  }
  .section-body p { font-size: 14px; line-height: 1.5; color: #333; margin: 0 0 12px; }
  .good-text { color: #16A34A; }
  .bad-text { color: #DC2626; }
  .muted-note { font-size: 13px; color: #595959; font-style: italic; }

  .category-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
  .category-card {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border: 1px solid #E2E8F0; border-left: 5px solid;
    border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    break-inside: avoid;
  }
  .category-score { font-size: 24px; font-weight: bold; font-family: Georgia, serif; min-width: 42px; }
  .category-name { font-weight: bold; font-size: 13px; margin-bottom: 2px; }
  .category-detail { font-size: 11px; color: #64748B; }
  .category-consequence { font-size: 11px; color: #7A1F1F; margin-top: 5px; line-height: 1.4; font-style: italic; }

  .worst-finding {
    background: #FEE2E2; border: 2px solid #DC2626; border-radius: 12px;
    padding: 20px 24px; margin-bottom: 26px; text-align: center;
    break-inside: avoid;
  }
  .worst-finding-eyebrow { font-size: 11px; font-weight: bold; letter-spacing: 2px; color: #DC2626; margin-bottom: 8px; }
  .worst-finding-name { font-size: 20px; font-weight: bold; color: #7A1F1F; margin-bottom: 8px; font-family: Georgia, serif; }
  .worst-finding-consequence { font-size: 14px; color: #7A1F1F; line-height: 1.5; max-width: 6in; margin: 0 auto; }

  .issue-list { display: flex; flex-direction: column; gap: 7px; }
  .issue-callout {
    background: #FAFAFA; border-left: 5px solid; border-radius: 8px;
    padding: 9px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    break-inside: avoid;
  }
  .issue-title { font-weight: bold; font-size: 13px; margin-bottom: 2px; }
  .issue-detail { font-size: 11px; color: #64748B; }

  .cta-box {
    background: linear-gradient(135deg, ${accentHex}, ${brandHex});
    color: #fff; border-radius: 14px; padding: 26px 32px; text-align: center;
    margin-top: 22px; box-shadow: 0 4px 14px rgba(0,0,0,0.15);
    break-inside: avoid;
  }
  .cta-urgency { font-size: 12px; color: rgba(255,255,255,0.85); margin-bottom: 10px; font-style: italic; }
  .cta-title { font-size: 20px; font-weight: bold; margin-bottom: 8px; }
  .cta-body { font-size: 13px; line-height: 1.5; margin-bottom: 12px; max-width: 5.5in; margin-left: auto; margin-right: auto; }
  .cta-email { font-size: 14px; font-weight: bold; }

  .footer { text-align: center; font-size: 10px; color: #94A3B8; margin-top: 24px; }
</style>
</head>
<body>

  <section class="cover">
    <div class="cover-header">
      <div class="eyebrow">${escapeHtml(provider.name.toUpperCase())} &bull; SEO AUDIT</div>
      <div class="score-badge">
        ${scoreRingSvg(score, scoreColor)}
        <div class="score-badge-inner">
          <div class="score-badge-num">${score}</div>
          <div class="score-badge-label">SEO HEALTH SCORE</div>
          <div class="score-badge-tier" style="color:${scoreColor}">${escapeHtml(scoreLabel)}</div>
        </div>
      </div>
    </div>
    <div class="cover-middle">
      ${logoImg}
      <h1 class="client-name">${escapeHtml(client.name)}</h1>
      <div class="client-url">${escapeHtml(client.url)}</div>
      ${grid.length ? `
      <div class="pill-row">
        <div class="pill pill-bad"><span class="n">${pillCounts.bad}</span> failed</div>
        <div class="pill pill-warn"><span class="n">${pillCounts.warn}</span> warnings</div>
        <div class="pill pill-good"><span class="n">${pillCounts.good}</span> passed</div>
      </div>` : ''}
    </div>
    <div class="cover-stats">${coverStatsHtml(stats)}</div>
  </section>

  <section class="page">
    ${worstFindingHtml}

    <div class="section-band" style="background:${brandHex}">WHERE YOU SHOULD BE</div>
    <div class="section-body"><p>${whereYouShouldBeHtml}</p></div>

    <div class="section-band" style="background:${brandHex}">BY CATEGORY</div>
    <div class="section-body">${categorySectionHtml}</div>

    ${topIssuesSectionHtml}

    <div class="cta-box">
      <div class="cta-urgency">Every week this goes unaddressed, competitors who are already fixing these exact issues pull further ahead.</div>
      <div class="cta-title">Ready to fix this?</div>
      <div class="cta-body">${escapeHtml(provider.name)} turns this list into a prioritized, done-for-you fix plan — most of what's above is fixable in weeks, not months.</div>
      <div class="cta-email">${escapeHtml(provider.email || '')}</div>
    </div>

    <div class="footer">${escapeHtml(provider.name)} — Customer Audit Report — ${escapeHtml(dateLabel)}</div>
  </section>

</body>
</html>`;
}

// @sparticuz/chromium.executablePath() decompresses its ~50MB bundled
// Chromium binary to /tmp on first call — real work, not a cheap lookup.
// Doing that fresh on every single report request (as the first version of
// this function did) adds real latency on top of an already-slow cold
// browser launch, on a modest Render instance that's also running the rest
// of this app; a request slow enough can get its response cut off
// mid-stream by a platform-level timeout, which is indistinguishable from
// the client's side as "downloaded a file that won't open" — exactly what
// was seen in production. The binary's location can't change during this
// process's lifetime, so resolving it once and reusing the same path for
// every request removes that repeated cost.
let executablePathPromise;
function getExecutablePath() {
  if (!executablePathPromise) executablePathPromise = chromium.executablePath();
  return executablePathPromise;
}

async function renderPdf(html) {
  const browser = await puppeteer.launch({
    headless: true,
    args: chromium.args,
    executablePath: await getExecutablePath(),
  });
  let buffer;
  try {
    const page = await browser.newPage();
    await page.setContent(html, { waitUntil: 'load' });
    // printBackground is the easy-to-miss flag here — without it Chromium
    // drops every CSS background-color/gradient (the cover, the pills, the
    // CTA box) and prints plain white, silently.
    //
    // page.pdf() resolves with a plain Uint8Array, not a Node Buffer — and
    // that distinction actually matters downstream: Express's res.send()
    // only treats a value as a raw binary body when Buffer.isBuffer() is
    // true, which is false for a plain Uint8Array (Buffer.isBuffer checks
    // for Buffer instances specifically, not just any typed array). Left
    // unconverted, res.send() silently falls through to res.json(), which
    // — since the route already sets Content-Type to application/pdf —
    // doesn't touch that header but replaces the body with a JSON dump of
    // the byte array. That shipped to production: a 200 response labeled
    // application/pdf whose actual body was JSON, which downloads fine
    // and then fails to open. Buffer.from() here is what actually fixes
    // that, not just the integrity check below.
    buffer = Buffer.from(await page.pdf({ format: 'Letter', printBackground: true }));
  } finally {
    await browser.close();
  }
  // Belt-and-suspenders: a genuinely truncated/partial response (e.g. cut
  // off mid-stream by a platform timeout) could still reach this point
  // without page.pdf() itself throwing. A real PDF always starts with
  // this magic-byte header and is never this small; catching it here
  // turns that failure mode into the same clear "Failed to generate PDF
  // report" response the route already sends for an outright exception,
  // instead of a downloaded file that silently won't open.
  if (buffer.length < 1000 || buffer.subarray(0, 5).toString('latin1') !== '%PDF-') {
    throw new Error(`Generated PDF failed integrity check (${buffer.length} bytes)`);
  }
  return buffer;
}

async function buildSalesReport({ client, provider, score, pagesCrawled, htmlReport, deductions, date }) {
  const brandHex = provider.brand || '#003366';
  const accentHex = provider.accent || provider.brand2 || provider.brand || '#003366';
  const dateLabel = new Date(date + 'T12:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const scoreLabel = score >= 85 ? 'Good' : score >= 70 ? 'Needs Improvement' : 'Needs Attention';
  const scoreColor = scoreTierColor(score);
  const grid = parseStatGrid(htmlReport);
  const problemCount = grid.filter(g => g.status === 'bad' || g.status === 'warn').length;
  // "neutral" tiles (Sitemap.xml Found, Total Pages Audited) are
  // informational counts, not a pass/fail judgment the on-screen report
  // itself made — left out of the pills so this always sums to a subset
  // of grid.length, never double-counts, and never claims a verdict the
  // grid's own coloring didn't already make.
  const pillCounts = { bad: 0, warn: 0, good: 0 };
  for (const g of grid) if (pillCounts[g.status] !== undefined) pillCounts[g.status]++;
  const totalChecksRun = TOTAL_CHECK_TYPES * Math.max(1, pagesCrawled || 1);
  const categories = buildCategoryScores(grid);
  // Highest point-value deductions first — the ones actually worth a
  // client's attention — capped at 6 so this reads as "the headline
  // issues," not another full dump of every finding.
  const topIssues = Array.isArray(deductions)
    ? [...deductions].filter(d => d.pts > 0).sort((a, b) => b.pts - a.pts).slice(0, 6)
    : [];

  const html = renderHtml({
    client, provider, brandHex, accentHex, score, scoreColor, scoreLabel, dateLabel,
    pagesCrawled, grid, categories, pillCounts, topIssues, totalChecksRun, problemCount,
  });

  return renderPdf(html);
}

module.exports = { buildSalesReport, parseStatGrid, buildCategoryScores };
