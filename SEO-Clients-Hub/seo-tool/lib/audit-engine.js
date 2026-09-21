// Reentrant audit engine — extracted from ../audit.js so a crawl can be
// triggered from something other than the CLI (e.g. the Phase 0 web app)
// without going through process.argv, readline prompts, or module-level
// mutable state. Behavior/output is unchanged from audit.js; this is a
// reorganization, not a rewrite. See ../../webapp/PHASE0-DESIGN.md §3.
const fetch = require('node-fetch');
const cheerio = require('cheerio');
const fs = require('fs');
const path = require('path');
const { getGSCMetrics } = require('../gsc');

// ─── Provider companies (who this audit is prepared/billed under) ─────────────
// All four sets of colors are confirmed real brand values.
const PROVIDERS = {
  '1': { name: 'Start Advertising', email: 'RBStart@StartAdvertising.com', brand: '#ED1C24', brand2: '#212121' },
  '2': { name: 'Start Performance', email: 'RBStart@StartPerformance.com', brand: '#ED1C24', brand2: '#212121' },
  '3': { name: 'Parts of Practice', email: 'Richard@PartsofPractice.com', brand: '#003366', brand2: '#f59e0b' },
  '4': { name: 'GroupRB', email: 'Richard@GroupRB.com', brand: '#0B0B0C', brand2: '#1D4ED8' },
};

// schema.org @type values worth checking for the LocalBusiness-specific
// recommended fields below (address/telephone/hours) — not exhaustive, but
// covers the subtypes an actual small-business/professional-services
// client is likely to use (every current client's schema uses
// ProfessionalService specifically).
const LOCAL_BUSINESS_TYPES = new Set([
  'LocalBusiness', 'ProfessionalService', 'MedicalBusiness', 'Physician', 'Dentist',
  'LegalService', 'Restaurant', 'Store', 'HomeAndConstructionBusiness', 'AutomotiveBusiness',
  'HealthAndBeautyBusiness', 'FinancialService', 'RealEstateAgent',
]);

function resolveProvider(provider) {
  if (!provider) return PROVIDERS['1'];
  if (typeof provider === 'object' && provider.name) return provider;
  const pinned = PROVIDERS[provider] ||
    Object.values(PROVIDERS).find(p => p.name.toLowerCase() === String(provider).toLowerCase());
  if (!pinned) throw new Error(`Unknown provider: ${provider} (expected 1-4 or a provider name)`);
  return pinned;
}

// ─── Crawler primitives (no per-client state — safe at module scope) ──────────

const IGNORE_EXTENSIONS = /\.(jpg|jpeg|png|gif|webp|svg|pdf|zip|doc|docx|xls|xlsx|mp4|mp3|css|js|woff|woff2|ttf)(\?|$)/i;

const BROWSER_HEADERS = {
  'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
  'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
  'Accept-Language': 'en-US,en;q=0.9',
  'Accept-Encoding': 'gzip, deflate, br',
  'Cache-Control': 'no-cache',
};

function normalizeUrl(href, base) {
  try {
    const u = new URL(href, base);
    if (u.hostname !== new URL(base).hostname) return null;
    u.hash = '';
    u.search = '';
    return u.href.replace(/\/$/, '') || u.href;
  } catch {
    return null;
  }
}

// `cacheBust` is a single value generated once per crawl() call (not a
// fresh one per request) — appended to the actual outbound request only,
// never to the returned `url`, which is this page's identity for
// everything downstream (link graph, broken-link destinations, dedup, the
// "Findings by Page" slug). Confirmed on a real client site: re-running an
// audit kept returning stale content (old word counts, resolved issues
// still showing) even right after the client purged their host's cache —
// most managed WordPress hosts layer more than one cache (e.g.
// SiteGround's own Dynamic Cache is separate from the SG Optimizer
// plugin's own full-page cache, and purging one doesn't purge the other),
// so "purge cache" not reaching every layer is a common, easy-to-miss
// failure mode. A query string is the standard, broadly-supported escape
// hatch — WP Super Cache, W3 Total Cache, SG Optimizer, and WP Rocket all
// treat any query string as "dynamic, don't serve from cache" by
// convention — so this makes every crawled page bypass essentially any
// URL-keyed cache layer, whichever one the client forgot to purge, not
// just this one. One value per run (rather than one per request) keeps
// every request in a given audit traceable back to that run and avoids
// looking like scanning/probing traffic to a host's security layer.
function bustedUrl(url, cacheBust) {
  return cacheBust ? url + (url.includes('?') ? '&' : '?') + '_seoaudit=' + cacheBust : url;
}

async function fetchPage(url, cacheBust) {
  try {
    const res = await fetch(bustedUrl(url, cacheBust), { headers: BROWSER_HEADERS, timeout: 12000, redirect: 'follow' });
    if (res.status === 403 && cacheBust) {
      // A 403 specifically (as opposed to 404/5xx) sometimes means a
      // security plugin or WAF is flagging the cache-busting query string
      // itself as suspicious, rather than the page genuinely being
      // inaccessible — confirmed as a real possibility on a client site
      // where a page that loads normally for visitors came back 403 only
      // from the audit. One plain retry without the query param settles
      // it: if that succeeds, this was a false alarm from our own
      // cache-busting and the page is fine; if it's still 403, the page
      // really is being blocked for this kind of request.
      const retry = await fetch(url, { headers: BROWSER_HEADERS, timeout: 12000, redirect: 'follow' });
      if (retry.ok) {
        const html = await retry.text();
        return { url, html, status: retry.status, contentEncoding: retry.headers.get('content-encoding') || '' };
      }
      return { url, error: retry.status === 403 ? 'HTTP 403 (blocked)' : `HTTP ${retry.status}` };
    }
    if (!res.ok) return { url, error: `HTTP ${res.status}` };
    const html = await res.text();
    return { url, html, status: res.status, contentEncoding: res.headers.get('content-encoding') || '' };
  } catch (e) {
    return { url, error: e.message };
  }
}

// Minimal robots.txt parser — only "Disallow:" prefix rules under
// "User-agent: *" (the common case for these small business sites), no
// "Allow:" override handling. Good enough to catch the real failure mode
// we care about (a page fully built but accidentally blocked from
// crawling), not meant to be a spec-complete robots.txt implementation.
async function fetchDisallowedPrefixes(baseUrl) {
  try {
    const res = await fetch(`${baseUrl}/robots.txt`, { headers: BROWSER_HEADERS, timeout: 10000 });
    if (!res.ok) return [];
    const text = await res.text();
    const lines = text.split('\n').map(l => l.trim());
    let inWildcardBlock = false;
    const prefixes = [];
    for (const line of lines) {
      const [rawKey, ...rest] = line.split(':');
      const key = (rawKey || '').trim().toLowerCase();
      const value = rest.join(':').trim();
      if (key === 'user-agent') inWildcardBlock = (value === '*');
      else if (key === 'disallow' && inWildcardBlock && value) prefixes.push(value);
    }
    return prefixes;
  } catch {
    return [];
  }
}

async function parseSitemap(url, depth = 0) {
  if (depth > 3) return [];
  try {
    const res = await fetch(url, { headers: BROWSER_HEADERS, timeout: 10000 });
    if (!res.ok) return [];
    const xml = await res.text();
    // Sitemap index — contains <sitemap><loc>...</loc></sitemap> pointing to child sitemaps
    const isSitemapIndex = xml.includes('<sitemapindex');
    const locs = [...xml.matchAll(/<loc>(.*?)<\/loc>/gi)].map(m => m[1].trim());
    if (isSitemapIndex) {
      const all = [];
      for (const childUrl of locs) {
        const childLocs = await parseSitemap(childUrl, depth + 1);
        all.push(...childLocs);
      }
      return all;
    }
    return locs;
  } catch { return []; }
}

// Groups of (non-error) pages that share the exact same non-empty value for
// a field, e.g. two pages with the identical <title>. Google can't tell
// duplicates apart even when each page individually looks fine, and this
// can only be found by comparing pages to each other — analyzePage() above
// only ever sees one page at a time.
function findDuplicateGroups(pages, field) {
  const seen = new Map();
  pages.forEach(p => {
    const val = (p[field] || '').trim().toLowerCase();
    if (!val) return; // empty values are already caught as "missing" per-page
    if (!seen.has(val)) seen.set(val, []);
    seen.get(val).push(p.url);
  });
  return [...seen.values()].filter(urls => urls.length > 1);
}

// US phone numbers only (every current client is US-based) — strips
// formatting down to a canonical 10-digit string so "(555) 123-4567" and
// "555.123.4567" compare as identical instead of registering as a false
// NAP-consistency mismatch. Returns null for anything that isn't a plain
// 10 or 11-digit (leading 1) US number.
function normalizePhone(raw) {
  const digits = String(raw || '').replace(/\D/g, '');
  const trimmed = digits.length === 11 && digits.startsWith('1') ? digits.slice(1) : digits;
  return trimmed.length === 10 ? trimmed : null;
}
function formatPhone(digits) {
  return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
}

// Annotates affected pages' `warnings` in place so the page table, Findings
// cards, and score deductions all agree on which pages have a duplicate
// title/meta description/H1 — same warnings array the per-page checks in
// analyzePage() already populate, just filled in after the fact once every
// page has been crawled.
function annotateDuplicates(results) {
  const pages = results.filter(r => !r.error);
  for (const [field, label] of [['title', 'title tag'], ['metaDesc', 'meta description'], ['h1', 'H1 tag']]) {
    for (const group of findDuplicateGroups(pages, field)) {
      for (const url of group) {
        const page = pages.find(p => p.url === url);
        const othersCount = group.length - 1;
        page.warnings.push(`Duplicate ${label} (shared with ${othersCount} other page${othersCount > 1 ? 's' : ''})`);
      }
    }
  }
}

// Approximate syllable count for a single English word — no dictionary
// lookup, just the standard vowel-group heuristic used by most readability
// tools (Yoast, Hemingway, etc.). Good enough for a Flesch Reading Ease
// estimate; not meant to be linguistically exact.
function countSyllables(word) {
  const w = word.toLowerCase().replace(/[^a-z]/g, '');
  if (!w) return 0;
  if (w.length <= 3) return 1;
  const trimmed = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, '');
  const matches = trimmed.match(/[aeiouy]{1,2}/g);
  return matches ? matches.length : 1;
}

// Flesch Reading Ease score (0-100, higher = easier to read) — a standard,
// well-known formula, not something we invented. Returns null when there
// isn't enough text to make the estimate meaningful.
//
// Returns the full breakdown (not just the score) rather than a bare
// number — an implausible score (very negative, or swinging wildly
// between two runs on content that "looks" unchanged) is otherwise
// impossible to diagnose without re-deriving these numbers by hand. In
// particular, a high wordsPerSentence average is a strong, self-evident
// signal that something non-prose (a list/label/widget with no
// punctuation) is still leaking into the extracted text, as opposed to
// genuinely dense vocabulary, which shows up as high syllablesPerWord
// instead with a normal wordsPerSentence.
function calcReadability(text) {
  const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
  const words = text.split(/\s+/).filter(w => w.length > 0);
  if (sentences.length < 2 || words.length < 30) return null;
  const syllables = words.reduce((sum, w) => sum + countSyllables(w), 0);
  const wordsPerSentence = words.length / sentences.length;
  const syllablesPerWord = syllables / words.length;
  const score = 206.835 - 1.015 * wordsPerSentence - 84.6 * syllablesPerWord;
  return {
    score: Math.round(score),
    wordCount: words.length,
    sentenceCount: sentences.length,
    wordsPerSentence: Math.round(wordsPerSentence * 10) / 10,
    syllablesPerWord: Math.round(syllablesPerWord * 100) / 100,
  };
}

// ─── Score ────────────────────────────────────────────────────────────────────

function calcScore(results, { hasSitemap = true } = {}) {
  const pages = results.filter(r => !r.error);
  if (!pages.length) return { score: 0, deductions: [{ pts: 0, label: 'No pages could be crawled' }] };

  let score = 100;
  const docked = [];

  const errorPages = results.filter(r => r.error);
  const notFound = errorPages.filter(r => r.error && r.error.includes('404')).length;
  const otherErrors = errorPages.filter(r => r.error && !r.error.includes('404')).length;

  const noTitle = pages.filter(p => !p.title).length;
  const noMeta = pages.filter(p => !p.metaDesc).length;
  const noH1 = pages.filter(p => p.h1Count === 0).length;
  const noSchema = pages.filter(p => p.schemaTypes.length === 0).length;
  const noCanonical = pages.filter(p => !p.canonical).length;
  const missingAlt = pages.filter(p => p.imagesNoAlt > 0).length;
  const badTitleLength = pages.filter(p => p.title && (p.titleLen < 30 || p.titleLen > 65)).length;
  const multipleH1 = pages.filter(p => p.h1Count > 1).length;
  const noindexed = pages.filter(p => p.issues.includes('Meta robots tag set to noindex')).length;
  const robotsBlocked = pages.filter(p => p.issues.includes('Blocked by robots.txt')).length;
  const totalPages = pages.length;

  const deduct = (pts, label) => { score -= pts; docked.push({ pts, label }); };

  // Critical issue deductions (broken/missing required elements)
  if (notFound) deduct(Math.min(15, notFound * 3), `${notFound} page(s) returning 404 errors`);
  if (otherErrors) deduct(Math.min(10, otherErrors * 2), `${otherErrors} page(s) with HTTP errors`);
  if (noTitle) deduct(Math.min(25, Math.round((noTitle / totalPages) * 25)), `${noTitle} page(s) missing title`);
  if (noMeta) deduct(Math.min(20, Math.round((noMeta / totalPages) * 20)), `${noMeta} page(s) missing meta description`);
  if (noH1) deduct(Math.min(20, Math.round((noH1 / totalPages) * 20)), `${noH1} page(s) missing H1`);
  if (noSchema) deduct(Math.min(15, Math.round((noSchema / totalPages) * 15)), `${noSchema} page(s) missing schema`);
  if (noCanonical) deduct(Math.min(8, Math.round((noCanonical / totalPages) * 8)), `${noCanonical} page(s) missing canonical`);
  if (missingAlt) deduct(Math.min(10, Math.round((missingAlt / totalPages) * 10)), `${missingAlt} page(s) have images without alt`);
  // A page can pass every check above and still never show up in search —
  // weighted as heavily as a missing title for exactly that reason.
  if (noindexed) deduct(Math.min(25, Math.round((noindexed / totalPages) * 25)), `${noindexed} page(s) set to noindex (excluded from search results)`);
  if (robotsBlocked) deduct(Math.min(15, Math.round((robotsBlocked / totalPages) * 15)), `${robotsBlocked} page(s) blocked by robots.txt`);

  // A page can have a present-but-wrong title/H1 (too short, too long, duplicated) —
  // that's not "missing" so it wouldn't be caught above, but it's still a real quality problem.
  if (badTitleLength) deduct(Math.min(12, Math.max(1, Math.round((badTitleLength / totalPages) * 12))), `${badTitleLength} page(s) with a poorly sized title tag (too short or too long)`);
  if (multipleH1) deduct(Math.min(5, Math.max(1, Math.round((multipleH1 / totalPages) * 5))), `${multipleH1} page(s) with multiple H1 tags`);

  // Cross-page checks: a page can individually have a present, correctly
  // sized title/meta and still be a real SEO problem if another page on the
  // same site reuses the exact same value — Google can't tell the pages
  // apart. These are scored on their own (not lumped into the catch-all
  // bucket below) since they're a distinct, higher-weight issue, same as
  // multipleH1/badTitleLength above. Requires results (not just pages) to
  // already have been annotated via annotateDuplicates().
  const duplicateTitlePages = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate title tag'))).length;
  const duplicateMetaPages = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate meta description'))).length;
  const duplicateH1Pages = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate H1 tag'))).length;
  if (duplicateTitlePages) deduct(Math.min(10, Math.round((duplicateTitlePages / totalPages) * 10)), `${duplicateTitlePages} page(s) with a duplicate title tag`);
  if (duplicateMetaPages) deduct(Math.min(8, Math.round((duplicateMetaPages / totalPages) * 8)), `${duplicateMetaPages} page(s) with a duplicate meta description`);
  if (duplicateH1Pages) deduct(Math.min(8, Math.round((duplicateH1Pages / totalPages) * 8)), `${duplicateH1Pages} page(s) with a duplicate H1 tag`);

  // Mobile-friendliness / accessibility / performance basics — real
  // ranking-adjacent signals, but each one alone is minor, so weighted
  // lightly relative to the critical/duplicate checks above.
  const missingViewport = pages.filter(p => p.warnings.includes('Missing viewport meta tag')).length;
  const missingLang = pages.filter(p => p.warnings.includes('Missing html lang attribute')).length;
  const missingCompression = pages.filter(p => p.warnings.some(w => w.startsWith('No HTTP compression'))).length;
  if (missingViewport) deduct(Math.min(8, Math.round((missingViewport / totalPages) * 8)), `${missingViewport} page(s) missing a viewport meta tag`);
  if (missingLang) deduct(Math.min(4, Math.round((missingLang / totalPages) * 4)), `${missingLang} page(s) missing an html lang attribute`);
  if (missingCompression) deduct(Math.min(6, Math.round((missingCompression / totalPages) * 6)), `${missingCompression} page(s) served without HTTP compression`);

  // Markup/link integrity — a page can be structurally fine but still have
  // broken or malformed pieces that hurt trust or indexing.
  const multipleTitleTags = pages.filter(p => p.warnings.some(w => w.startsWith('Multiple title tags'))).length;
  const multipleMetaTags = pages.filter(p => p.warnings.some(w => w.startsWith('Multiple meta description tags'))).length;
  const invalidSchemaPages = pages.filter(p => p.issues.some(i => i.includes('invalid (malformed) JSON-LD'))).length;
  const mixedContentPages = pages.filter(p => p.issues.some(i => i.includes('mixed-content resource'))).length;
  const brokenLinkPages = pages.filter(p => p.issues.some(i => i.startsWith('Links to') && i.includes('broken internal'))).length;
  const missingImageDimensionPages = pages.filter(p => p.warnings.some(w => w.includes('missing width/height attributes'))).length;
  if (multipleTitleTags) deduct(Math.min(6, Math.round((multipleTitleTags / totalPages) * 6)), `${multipleTitleTags} page(s) with multiple title tags`);
  if (multipleMetaTags) deduct(Math.min(5, Math.round((multipleMetaTags / totalPages) * 5)), `${multipleMetaTags} page(s) with multiple meta description tags`);
  if (invalidSchemaPages) deduct(Math.min(6, Math.round((invalidSchemaPages / totalPages) * 6)), `${invalidSchemaPages} page(s) with invalid (malformed) schema markup`);
  if (mixedContentPages) deduct(Math.min(8, Math.round((mixedContentPages / totalPages) * 8)), `${mixedContentPages} page(s) with mixed-content (http://) resources`);
  if (brokenLinkPages) deduct(Math.min(10, Math.round((brokenLinkPages / totalPages) * 10)), `${brokenLinkPages} page(s) link to a broken internal page`);
  if (missingImageDimensionPages) deduct(Math.min(6, Math.round((missingImageDimensionPages / totalPages) * 6)), `${missingImageDimensionPages} page(s) have images missing width/height attributes`);

  // Minor completeness checks — each individually low-severity, but kept
  // as their own itemized deductions (rather than melting into the
  // generic catch-all bucket below) for the same transparency every other
  // check on this list gets: a client should be able to see exactly what
  // "88/100" is made of, not a vague "other issues" bucket.
  const missingOgTags = pages.filter(p => p.warnings.some(w => w.startsWith('Missing Open Graph tags'))).length;
  const missingCharset = pages.filter(p => p.warnings.includes('Missing character encoding declaration')).length;
  const missingDoctype = pages.filter(p => p.warnings.includes('Missing doctype declaration')).length;
  if (missingOgTags) deduct(Math.min(4, Math.round((missingOgTags / totalPages) * 4)), `${missingOgTags} page(s) missing Open Graph tags`);
  if (missingCharset) deduct(Math.min(3, Math.round((missingCharset / totalPages) * 3)), `${missingCharset} page(s) missing a character encoding declaration`);
  if (missingDoctype) deduct(Math.min(2, Math.round((missingDoctype / totalPages) * 2)), `${missingDoctype} page(s) missing a doctype declaration`);

  // Internal-linking / content-quality / structured-data-completeness
  // signals — none of these are "broken" in the strict sense, but each is
  // a real ranking-adjacent factor that's checkable for free.
  const orphanPages = pages.filter(p => p.warnings.includes('Orphan page (no internal links point to it)')).length;
  const skippedHeadingPages = pages.filter(p => p.warnings.some(w => w.startsWith('Heading hierarchy skips'))).length;
  const difficultReadingPages = pages.filter(p => p.warnings.some(w => w.startsWith('Difficult to read'))).length;
  const missingLocalBusinessFieldPages = pages.filter(p => p.warnings.some(w => w.startsWith('LocalBusiness schema missing'))).length;
  // NAP consistency — a page whose shown phone number doesn't match the
  // site's dominant one (see crawl()'s post-crawl pass). A real trust/local-
  // SEO signal: Google cross-checks a business's listed number against its
  // Google Business Profile, and a mismatched footer number (usually left
  // over from an old template edit) is exactly the kind of thing that
  // erodes that match.
  const inconsistentPhonePages = pages.filter(p => p.warnings.some(w => w.startsWith("Phone number doesn't match"))).length;
  if (orphanPages) deduct(Math.min(8, Math.round((orphanPages / totalPages) * 8)), `${orphanPages} orphan page(s) (no internal links point to them)`);
  if (skippedHeadingPages) deduct(Math.min(4, Math.round((skippedHeadingPages / totalPages) * 4)), `${skippedHeadingPages} page(s) with a skipped heading level`);
  if (difficultReadingPages) deduct(Math.min(6, Math.round((difficultReadingPages / totalPages) * 6)), `${difficultReadingPages} page(s) with difficult-to-read copy`);
  if (missingLocalBusinessFieldPages) deduct(Math.min(6, Math.round((missingLocalBusinessFieldPages / totalPages) * 6)), `${missingLocalBusinessFieldPages} page(s) with incomplete LocalBusiness schema (missing phone/address/hours)`);
  if (inconsistentPhonePages) deduct(Math.min(6, Math.round((inconsistentPhonePages / totalPages) * 6)), `${inconsistentPhonePages} page(s) showing a phone number that doesn't match the rest of the site`);

  // Advisory warning deduction: pages with *other* warnings (not already scored above) and no critical issues (-0.5 each, max -15)
  // "...the auditor was blocked from reaching" is deliberately listed here
  // with no corresponding deduct() call of its own — it's an unconfirmed,
  // possibly-false-alarm finding (see fetchPage's 403 retry logic), and a
  // link that's broken on every page is one unique destination, not N
  // separate problems, so it shouldn't silently rack up points through
  // this generic bucket just for not matching a more specific pattern
  // above. It still appears in Findings by Page for a human to verify —
  // it just doesn't cost score until it's confirmed.
  const scoredWarningPatterns = [
    /^Title too (short|long)/, /^Multiple H1 tags/, /^Duplicate title tag/, /^Duplicate meta description/,
    /^Duplicate H1 tag/, /^Missing viewport meta tag/, /^Missing html lang attribute/, /^No HTTP compression/,
    /^Multiple title tags/, /^Multiple meta description tags/, /missing width\/height attributes/,
    /^Missing Open Graph tags/, /^Missing character encoding declaration/, /^Missing doctype declaration/,
    /^Orphan page/, /^Heading hierarchy skips/, /^Difficult to read/, /^LocalBusiness schema missing/,
    /^Phone number doesn't match/, /the auditor was blocked from reaching/, /^Borderline readability/,
  ];
  const warningOnlyPages = pages.filter(p => {
    if (p.issues.length > 0) return false;
    const remaining = p.warnings.filter(w => !scoredWarningPatterns.some(re => re.test(w)));
    return remaining.length > 0;
  }).length;
  if (warningOnlyPages) {
    const warnPts = Math.min(15, Math.round(warningOnlyPages * 0.5));
    deduct(warnPts, `${warningOnlyPages} page(s) with other improvement opportunities`);
  }

  // Site-wide (not per-page): no sitemap.xml means search engines and our
  // own crawler can only discover pages by following internal links, so
  // anything not linked from the nav goes unaudited and unindexed.
  if (!hasSitemap) deduct(5, 'No sitemap.xml found');

  return { score: Math.max(0, score), deductions: docked };
}

// ─── Friendly label maps ──────────────────────────────────────────────────────

const ISSUE_LABELS = {
  'Missing title tag': { label: 'Page is missing a title tag', why: 'Google uses the title tag as the clickable headline in search results. Without one, Google will generate its own — often poorly.', priority: 'critical' },
  'No H1 tag found': { label: 'Page has no main heading (H1)', why: 'The H1 tells search engines what the page is about. Every page should have exactly one.', priority: 'critical' },
  'Missing meta description': { label: 'Missing meta description', why: 'Meta descriptions appear as the summary text in search results. Missing ones lead to lower click-through rates.', priority: 'high' },
  'Meta robots tag set to noindex': { label: 'Page is set to noindex', why: 'This tells Google not to show the page in search results at all — usually leftover from a staging environment. Every other SEO improvement on this page is wasted until this is removed.', priority: 'critical' },
  'Blocked by robots.txt': { label: 'Page is blocked by robots.txt', why: 'robots.txt is telling search engines not to crawl this page at all, so it can\'t be indexed regardless of how well-built the page itself is.', priority: 'critical' },
};

const WARN_LABELS = {
  'No canonical tag': { label: 'No canonical tag set', why: 'Canonical tags prevent duplicate content issues and tell Google which version of a page to index.', priority: 'medium' },
  'No JSON-LD schema found': { label: 'No structured data (schema) on this page', why: 'Schema markup helps Google understand your content and can unlock rich results like star ratings, FAQs, and product details in search.', priority: 'medium' },
};

function friendlyIssue(text) {
  if (ISSUE_LABELS[text]) return ISSUE_LABELS[text];
  if (text.match(/image.*missing alt/i)) return { label: `Images missing alt text (${text.match(/\d+/)?.[0] || ''})`, why: 'Alt text helps Google understand images and is required for accessibility compliance.', priority: 'high' };
  if (text.match(/Multiple H1/i)) return { label: text, why: 'Having more than one H1 dilutes the page focus signal for search engines.', priority: 'medium' };
  if (text.match(/Title too short/i)) return { label: text, why: 'Short titles miss keyword opportunities. Aim for 50-65 characters.', priority: 'medium' };
  if (text.match(/Title too long/i)) return { label: text, why: 'Long titles get cut off in search results. Aim for 50-65 characters.', priority: 'medium' };
  if (text.match(/Meta description short/i)) return { label: text, why: 'Short meta descriptions waste valuable space to attract clicks from search results.', priority: 'low' };
  if (text.match(/Meta description long/i)) return { label: text, why: 'Long meta descriptions get truncated in search results.', priority: 'low' };
  if (text.match(/Low word count/i)) return { label: text, why: 'Pages with very little content are harder for Google to rank.', priority: 'medium' };
  if (text.match(/Duplicate title tag/i)) return { label: text, why: 'Google can\'t tell duplicate-titled pages apart, which hurts both pages\' ability to rank for their intended keywords.', priority: 'medium' };
  if (text.match(/Duplicate meta description/i)) return { label: text, why: 'Search results for these pages will show the same snippet, making it harder for users to tell them apart and choose the right one.', priority: 'medium' };
  if (text.match(/Duplicate H1 tag/i)) return { label: text, why: 'Reusing the same main heading across pages dilutes the topical signal Google uses to tell them apart.', priority: 'medium' };
  if (text.match(/Missing viewport meta tag/i)) return { label: text, why: 'Without a viewport tag, mobile browsers may render the page at desktop width, hurting mobile usability and rankings.', priority: 'medium' };
  if (text.match(/Missing html lang attribute/i)) return { label: text, why: 'The lang attribute tells search engines and screen readers what language the page is in — a basic accessibility and SEO signal.', priority: 'low' };
  if (text.match(/No HTTP compression/i)) return { label: text, why: 'Serving pages without gzip/br compression means slower load times than necessary, which affects both user experience and Core Web Vitals.', priority: 'low' };
  if (text.match(/Multiple title tags/i)) return { label: text, why: 'Browsers and Google will only use one of these title tags, and it\'s unpredictable which — this should be a single, deliberate title.', priority: 'medium' };
  if (text.match(/Multiple meta description tags/i)) return { label: text, why: 'Only one meta description will actually be used in search results, and it may not be the one intended.', priority: 'medium' };
  if (text.match(/invalid \(malformed\) JSON-LD/i)) return { label: text, why: 'Malformed schema markup is ignored by Google entirely — it provides none of the benefit of valid structured data.', priority: 'medium' };
  if (text.match(/mixed-content resource/i)) return { label: text, why: 'Browsers block or warn on http:// resources loaded on an https:// page, which can visibly break the page or trigger a security warning.', priority: 'high' };
  if (text.match(/Links to \d+ broken internal/i)) return { label: text, why: 'A link on this page points to a page that no longer exists, creating a dead end for visitors and search engines.', priority: 'high' };
  if (text.match(/the auditor was blocked from reaching/i)) return { label: text, why: 'The destination returned "Forbidden" specifically, not "not found" — that often means a security plugin or firewall rule denied this kind of automated request, not that the page is actually broken for real visitors. Worth a manual check before treating it as a dead link.', priority: 'medium' };
  if (text.match(/missing width\/height attributes/i)) return { label: text, why: 'Without explicit dimensions, the browser doesn\'t know how much space to reserve for the image, causing content to jump around as it loads (a Core Web Vitals penalty).', priority: 'medium' };
  if (text.match(/Missing Open Graph tags/i)) return { label: text, why: 'Without these tags, links to this page shared on social media or messaging apps show a generic or broken preview instead of a proper title/image.', priority: 'low' };
  if (text.match(/Missing character encoding declaration/i)) return { label: text, why: 'Without a declared character encoding, special characters can render incorrectly in some browsers.', priority: 'low' };
  if (text.match(/Missing doctype declaration/i)) return { label: text, why: 'Without a doctype, browsers may render the page in a legacy "quirks mode" with inconsistent behavior.', priority: 'low' };
  if (text.match(/Orphan page/i)) return { label: text, why: 'No other page on the site links to this one, so visitors browsing normally can\'t find it and it gets little to no internal link authority.', priority: 'medium' };
  if (text.match(/Heading hierarchy skips/i)) return { label: text, why: 'Skipping a heading level breaks the logical document outline search engines and screen readers rely on.', priority: 'low' };
  if (text.match(/Difficult to read/i)) return { label: text, why: 'Dense, hard-to-read copy loses visitors and gives Google less clear signal about what the page is actually about.', priority: 'medium' };
  if (text.match(/Borderline readability/i)) return { label: text, why: 'Close to the readability cutoff either way — not a meaningful problem on its own, and clinical/technical terminology naturally scores lower here regardless of how well it\'s written.', priority: 'low' };
  if (text.match(/LocalBusiness schema missing/i)) return { label: text, why: 'Missing phone/address/hours in structured data means Google has less to work with for map listings and knowledge panels, even though the schema is technically present and valid.', priority: 'medium' };
  if (text.match(/Phone number doesn't match/i)) return { label: text, why: 'An inconsistent phone number (a NAP signal) makes it harder for Google to confirm this is the same business as your Google Business Profile listing, and confuses visitors about which number is current.', priority: 'medium' };
  return { label: text, why: '', priority: 'low' };
}

// ─── Score/keyword/metrics history (file-based — Phase 0 storage, see design doc §5) ──

function loadMetrics(outDir) {
  const p = path.join(outDir, 'metrics.json');
  try { return JSON.parse(fs.readFileSync(p, 'utf8')); } catch { return null; }
}

function loadKeywordHistory(outDir) {
  const p = path.join(outDir, 'keyword-history.json');
  try { return JSON.parse(fs.readFileSync(p, 'utf8')); } catch { return []; }
}

function saveKeywordHistory(outDir, date, keywords) {
  const p = path.join(outDir, 'keyword-history.json');
  const history = loadKeywordHistory(outDir);
  // Remove existing entry for same date if re-running
  const filtered = history.filter(h => h.date !== date);
  filtered.push({ date, keywords });
  // Keep last 12 months of snapshots
  filtered.sort((a, b) => a.date.localeCompare(b.date));
  const trimmed = filtered.slice(-12);
  fs.writeFileSync(p, JSON.stringify(trimmed, null, 2), 'utf8');
}

function loadHistory(outDir) {
  const p = path.join(outDir, 'score-history.json');
  try { return JSON.parse(fs.readFileSync(p, 'utf8')); } catch { return []; }
}

function saveHistory(outDir, history) {
  const p = path.join(outDir, 'score-history.json');
  fs.writeFileSync(p, JSON.stringify(history, null, 2), 'utf8');
}

// ─── Storage adapter ──────────────────────────────────────────────────────────
// runAudit() below persists history/metrics through this interface instead of
// calling the file functions directly, so a caller can swap in a DB-backed
// adapter (webapp/server) without changing the CLI's behavior at all — see
// webapp/PHASE1-DESIGN.md §5. fileStorage is the default and is exactly what
// audit.js has always done; each method receives the client object (not just
// outDir) so a DB adapter can key off client.slug/client.id instead.
const fileStorage = {
  loadHistory: (client) => loadHistory(client.output_dir || process.cwd()),
  saveHistory: (client, history) => saveHistory(client.output_dir || process.cwd(), history),
  loadKeywordHistory: (client) => loadKeywordHistory(client.output_dir || process.cwd()),
  saveKeywordHistory: (client, date, keywords) => saveKeywordHistory(client.output_dir || process.cwd(), date, keywords),
  loadMetrics: (client) => loadMetrics(client.output_dir || process.cwd()),
};

// ─── Quick wins ───────────────────────────────────────────────────────────────

function getQuickWins(pages, { hasSitemap = true } = {}) {
  const wins = [];

  const noCanonical = pages.filter(p => !p.canonical).length;
  const noSchema = pages.filter(p => p.schemaTypes.length === 0).length;
  const shortTitle = pages.filter(p => p.title && p.titleLen < 30).length;
  const longTitle = pages.filter(p => p.title && p.titleLen > 65).length;
  const shortMeta = pages.filter(p => p.metaDesc && p.metaLen < 100).length;
  const longMeta = pages.filter(p => p.metaDesc && p.metaLen > 165).length;
  const emptyAlt = pages.filter(p => p.imagesEmptyAlt > 0).length;
  const lowWord = pages.filter(p => p.wordCount < 150).length;
  const multiH1 = pages.filter(p => p.h1Count > 1).length;
  const duplicateTitle = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate title tag'))).length;
  const duplicateMeta = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate meta description'))).length;
  const duplicateH1 = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate H1 tag'))).length;
  const noindexed = pages.filter(p => p.issues.includes('Meta robots tag set to noindex')).length;
  const robotsBlocked = pages.filter(p => p.issues.includes('Blocked by robots.txt')).length;
  const missingViewport = pages.filter(p => p.warnings.includes('Missing viewport meta tag')).length;
  const missingLang = pages.filter(p => p.warnings.includes('Missing html lang attribute')).length;
  const missingCompression = pages.filter(p => p.warnings.some(w => w.startsWith('No HTTP compression'))).length;
  const multipleTitleTags = pages.filter(p => p.warnings.some(w => w.startsWith('Multiple title tags'))).length;
  const multipleMetaTags = pages.filter(p => p.warnings.some(w => w.startsWith('Multiple meta description tags'))).length;
  const invalidSchema = pages.filter(p => p.issues.some(i => i.includes('invalid (malformed) JSON-LD'))).length;
  const mixedContent = pages.filter(p => p.issues.some(i => i.includes('mixed-content resource'))).length;
  const brokenLinks = pages.filter(p => p.issues.some(i => i.startsWith('Links to') && i.includes('broken internal'))).length;
  const missingImageDimensions = pages.filter(p => p.warnings.some(w => w.includes('missing width/height attributes'))).length;
  const orphanPages = pages.filter(p => p.warnings.includes('Orphan page (no internal links point to it)')).length;
  const skippedHeadings = pages.filter(p => p.warnings.some(w => w.startsWith('Heading hierarchy skips'))).length;
  const difficultReading = pages.filter(p => p.warnings.some(w => w.startsWith('Difficult to read'))).length;
  const missingLocalBusinessFields = pages.filter(p => p.warnings.some(w => w.startsWith('LocalBusiness schema missing'))).length;
  const inconsistentPhone = pages.filter(p => p.warnings.some(w => w.startsWith("Phone number doesn't match"))).length;

  // critical: true always sorts these to the very top, ahead of effort/
  // impact — a page that can't be indexed at all outranks every other win
  // on this list regardless of how many pages the others affect.
  if (noindexed) wins.push({ effort: 'Low', impact: 'High', critical: true, pages: noindexed, action: `Remove noindex from ${noindexed} page${noindexed > 1 ? 's' : ''}`, detail: 'These pages are set to noindex, so Google won\'t show them in search results at all — usually leftover from a staging environment. This is a one-line fix per page.' });
  if (robotsBlocked) wins.push({ effort: 'Low', impact: 'High', critical: true, pages: robotsBlocked, action: `Un-block ${robotsBlocked} page${robotsBlocked > 1 ? 's' : ''} in robots.txt`, detail: 'robots.txt is currently telling search engines not to crawl these pages at all, regardless of how well-built the pages themselves are.' });
  if (mixedContent) wins.push({ effort: 'Low', impact: 'High', critical: true, pages: mixedContent, action: `Fix mixed-content resources on ${mixedContent} page${mixedContent > 1 ? 's' : ''}`, detail: 'These pages load http:// resources on an https:// page, which browsers actively block or warn about — usually just needs the resource URL switched to https://.' });
  if (noCanonical) wins.push({ effort: 'Low', impact: 'Medium', pages: noCanonical, action: `Add canonical tags to ${noCanonical} page${noCanonical > 1 ? 's' : ''}`, detail: 'Canonical tags are a single line of code. They tell Google which version of a page to index and prevent duplicate content penalties.' });
  if (emptyAlt) wins.push({ effort: 'Low', impact: 'Medium', pages: emptyAlt, action: `Fill in empty image alt text on ${emptyAlt} page${emptyAlt > 1 ? 's' : ''}`, detail: 'Alt text is already in the code but blank. Adding keyword-relevant descriptions takes minutes and improves both accessibility and image search rankings.' });
  if (shortTitle || longTitle) { const n = shortTitle + longTitle; wins.push({ effort: 'Low', impact: 'High', pages: n, action: `Optimize title tag length on ${n} page${n > 1 ? 's' : ''}`, detail: `${shortTitle ? shortTitle + ' titles are under 30 characters (missing keyword opportunities). ' : ''}${longTitle ? longTitle + ' titles exceed 65 characters and will be cut off in search results.' : ''}` }); }
  if (shortMeta || longMeta) { const n = shortMeta + longMeta; wins.push({ effort: 'Low', impact: 'Medium', pages: n, action: `Rewrite meta descriptions on ${n} page${n > 1 ? 's' : ''}`, detail: `${shortMeta ? shortMeta + ' meta descriptions are too short to persuade clicks. ' : ''}${longMeta ? longMeta + ' are too long and get truncated in search results.' : ''}` }); }
  if (multiH1) wins.push({ effort: 'Low', impact: 'Medium', pages: multiH1, action: `Fix multiple H1 tags on ${multiH1} page${multiH1 > 1 ? 's' : ''}`, detail: 'Each page should have exactly one H1. Multiple H1s dilute the main topic signal Google uses to understand the page.' });
  if (noSchema) wins.push({ effort: 'Medium', impact: 'High', pages: noSchema, action: `Add structured data (schema) to ${noSchema} page${noSchema > 1 ? 's' : ''}`, detail: 'Schema markup helps Google display rich results (star ratings, FAQs, product details). Product and service pages especially benefit from this.' });
  if (lowWord) wins.push({ effort: 'High', impact: 'High', pages: lowWord, action: `Expand thin content on ${lowWord} page${lowWord > 1 ? 's' : ''}`, detail: 'Pages under 150 words give Google very little to rank. Adding descriptive copy, FAQs, or specifications strengthens these pages significantly.' });
  if (duplicateTitle) wins.push({ effort: 'Low', impact: 'High', pages: duplicateTitle, action: `Write unique title tags for ${duplicateTitle} page${duplicateTitle > 1 ? 's' : ''}`, detail: 'These pages currently share an identical title tag with another page, so Google can\'t tell them apart in search results.' });
  if (duplicateMeta) wins.push({ effort: 'Low', impact: 'Medium', pages: duplicateMeta, action: `Write unique meta descriptions for ${duplicateMeta} page${duplicateMeta > 1 ? 's' : ''}`, detail: 'These pages currently share an identical meta description with another page, producing duplicate-looking search snippets.' });
  if (duplicateH1) wins.push({ effort: 'Low', impact: 'Medium', pages: duplicateH1, action: `Write unique H1 headings for ${duplicateH1} page${duplicateH1 > 1 ? 's' : ''}`, detail: 'These pages currently share an identical main heading with another page, diluting the topic signal for both.' });
  if (missingViewport) wins.push({ effort: 'Low', impact: 'Medium', pages: missingViewport, action: `Add a viewport meta tag to ${missingViewport} page${missingViewport > 1 ? 's' : ''}`, detail: 'Without this tag, mobile browsers may render the page at desktop width, hurting mobile usability and rankings.' });
  if (missingLang) wins.push({ effort: 'Low', impact: 'Low', pages: missingLang, action: `Add an html lang attribute to ${missingLang} page${missingLang > 1 ? 's' : ''}`, detail: 'A one-line fix that tells search engines and screen readers what language the page is in.' });
  if (missingCompression) wins.push({ effort: 'Medium', impact: 'Medium', pages: missingCompression, action: `Enable HTTP compression on ${missingCompression} page${missingCompression > 1 ? 's' : ''}`, detail: 'Serving pages without gzip/br compression means slower load times than necessary — usually a one-time hosting/server config change.' });
  if (brokenLinks) wins.push({ effort: 'Medium', impact: 'High', pages: brokenLinks, action: `Fix broken internal links on ${brokenLinks} page${brokenLinks > 1 ? 's' : ''}`, detail: 'These pages link to another page on the site that no longer exists — a dead end for visitors and search engines alike.' });
  if (invalidSchema) wins.push({ effort: 'Low', impact: 'Medium', pages: invalidSchema, action: `Fix invalid schema markup on ${invalidSchema} page${invalidSchema > 1 ? 's' : ''}`, detail: 'The structured data on these pages is malformed JSON, so Google ignores it entirely — usually a small syntax fix.' });
  if (multipleTitleTags || multipleMetaTags) { const n = multipleTitleTags + multipleMetaTags; wins.push({ effort: 'Low', impact: 'Medium', pages: n, action: `Remove duplicate title/meta tags on ${n} page${n > 1 ? 's' : ''}`, detail: 'These pages have more than one title or meta description tag — only one is actually used, and it may not be the intended one.' }); }
  if (missingImageDimensions) wins.push({ effort: 'Medium', impact: 'Medium', pages: missingImageDimensions, action: `Add width/height to images on ${missingImageDimensions} page${missingImageDimensions > 1 ? 's' : ''}`, detail: 'Without explicit dimensions, images cause content to jump around as the page loads — a Core Web Vitals (layout shift) penalty.' });
  if (orphanPages) wins.push({ effort: 'Low', impact: 'Medium', pages: orphanPages, action: `Add internal links to ${orphanPages} orphan page${orphanPages > 1 ? 's' : ''}`, detail: 'No other page on the site links to these pages, so visitors browsing normally can\'t find them and they get little internal link authority.' });
  if (missingLocalBusinessFields) wins.push({ effort: 'Low', impact: 'Medium', pages: missingLocalBusinessFields, action: `Fill in missing LocalBusiness schema fields on ${missingLocalBusinessFields} page${missingLocalBusinessFields > 1 ? 's' : ''}`, detail: 'Phone, address, or hours are missing from the structured data — a quick addition that gives Google more to work with for map listings and knowledge panels.' });
  if (difficultReading) wins.push({ effort: 'Medium', impact: 'Medium', pages: difficultReading, action: `Review readability and clinical terminology on ${difficultReading} page${difficultReading > 1 ? 's' : ''}`, detail: 'These pages score as difficult to read (Flesch reading ease under 25) — check whether shorter sentences would genuinely help, or whether the score mainly reflects necessary clinical/technical vocabulary that shouldn\'t be diluted.' });
  if (skippedHeadings) wins.push({ effort: 'Low', impact: 'Low', pages: skippedHeadings, action: `Fix heading hierarchy on ${skippedHeadings} page${skippedHeadings > 1 ? 's' : ''}`, detail: 'A heading level (H2 or H3) is being skipped, breaking the logical outline of the page.' });
  if (inconsistentPhone) wins.push({ effort: 'Low', impact: 'Medium', pages: inconsistentPhone, action: `Fix the mismatched phone number on ${inconsistentPhone} page${inconsistentPhone > 1 ? 's' : ''}`, detail: 'These pages show a different phone number than the rest of the site — usually a footer template that wasn\'t updated after a number change. Inconsistent contact info (NAP) is a local-SEO trust signal Google checks against your Google Business Profile.' });
  if (!hasSitemap) wins.push({ effort: 'Low', impact: 'High', pages: 0, action: 'Add an XML sitemap', detail: 'No sitemap.xml was found. Without one, search engines (and this audit) can only discover pages that are linked from the site\'s navigation — anything else may go unindexed.' });

  // Sort: critical (unindexable pages) first, then low effort first, then by pages affected
  const effortOrder = { Low: 0, Medium: 1, High: 2 };
  wins.sort((a, b) => (b.critical ? 1 : 0) - (a.critical ? 1 : 0) || effortOrder[a.effort] - effortOrder[b.effort] || b.pages - a.pages);

  return wins.slice(0, 5);
}

// ─── Score trend chart (SVG) ──────────────────────────────────────────────────

function buildTrendChart(history) {
  if (history.length < 2) return '';
  const W = 520, H = 110, PAD = { top: 14, right: 20, bottom: 28, left: 36 };
  const chartW = W - PAD.left - PAD.right;
  const chartH = H - PAD.top - PAD.bottom;
  const minScore = Math.max(0, Math.min(...history.map(h => h.score)) - 10);
  const maxScore = Math.min(100, Math.max(...history.map(h => h.score)) + 5);
  const xStep = chartW / (history.length - 1);
  const yScale = v => chartH - ((v - minScore) / (maxScore - minScore)) * chartH;

  const points = history.map((h, i) => ({ x: PAD.left + i * xStep, y: PAD.top + yScale(h.score), ...h }));
  const polyline = points.map(p => `${p.x},${p.y}`).join(' ');
  const area = `${points[0].x},${PAD.top + chartH} ` + points.map(p => `${p.x},${p.y}`).join(' ') + ` ${points[points.length-1].x},${PAD.top + chartH}`;

  const labels = points.map(p => {
    const d = new Date(p.date + 'T12:00:00');
    const label = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    return `<text x="${p.x}" y="${H - 6}" text-anchor="middle" font-size="9" fill="rgba(255,255,255,0.55)">${label}</text>`;
  }).join('');

  const dots = points.map((p, i) => {
    const isLast = i === points.length - 1;
    return `
      <circle cx="${p.x}" cy="${p.y}" r="${isLast ? 5 : 3.5}" fill="${isLast ? '#fff' : 'rgba(255,255,255,0.7)'}" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
      <text x="${p.x}" y="${p.y - 9}" text-anchor="middle" font-size="${isLast ? 11 : 9}" font-weight="${isLast ? '700' : '400'}" fill="rgba(255,255,255,${isLast ? '1' : '0.75'})">${p.score}</text>
    `;
  }).join('');

  return `
  <div class="cover-trend">
    <div class="trend-label">Score History</div>
    <svg viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" style="width:100%;max-width:${W}px;overflow:visible">
      <defs>
        <linearGradient id="tg" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="rgba(255,255,255,0.18)"/>
          <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
        </linearGradient>
      </defs>
      <polygon points="${area}" fill="url(#tg)"/>
      <polyline points="${polyline}" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linejoin="round"/>
      ${dots}
      ${labels}
    </svg>
  </div>`;
}

// ─── Per-client engine: everything below genuinely needs BASE_URL/IGNORE/client
// closure, so it's scoped inside createEngine() instead of at module level —
// this is what makes runAudit() reentrant/safe to call more than once per
// process, unlike the original audit.js which used module-level globals. ────

function createEngine(client) {
  // `.origin` (not just a trailing-slash strip) matters here: every link
  // discovered during the crawl is normalized through normalizeUrl()'s
  // `new URL(...).href`, which the URL spec always lowercases the hostname
  // of. If a client's configured URL has any uppercase letters in its
  // hostname (e.g. "https://Maxwellsplumbing.com", typed exactly as the
  // business's own name is capitalized — a completely normal thing to
  // type during onboarding), every `abs.startsWith(BASE_URL)` link filter
  // below would silently fail on the case mismatch alone, dropping every
  // single real internal link as "external" — confirmed in production: a
  // real, healthy 40+ page WordPress site crawled as if it were a 1-page
  // site, with no error to explain why (the homepage itself still fetches
  // fine either way, since it's never compared against BASE_URL).
  let BASE_URL = new URL(client.url).origin;
  const MAX_PAGES = client.max_pages || 50;
  const IGNORE = client.ignore_paths || [];

  function shouldIgnore(url) {
    if (IGNORE_EXTENSIONS.test(url)) return true;
    return IGNORE.some(p => url.includes(p));
  }

  function analyzePage(url, html, contentEncoding = '') {
    const $ = cheerio.load(html);
    const issues = [];
    const warnings = [];

    // Meta robots noindex — checked first and treated as critical: a page
    // can pass every other check here and still never appear in search if
    // this is set, usually left over from a staging environment.
    const robotsMeta = ($('meta[name="robots"]').attr('content') || '').toLowerCase();
    if (robotsMeta.includes('noindex')) issues.push('Meta robots tag set to noindex');

    // Mobile-friendliness / accessibility basics — cheap to check since the
    // page is already parsed, and both are checks Ubersuggest/SEObility run.
    if (!$('meta[name="viewport"]').attr('content')) warnings.push('Missing viewport meta tag');
    if (!$('html').attr('lang')) warnings.push('Missing html lang attribute');
    if (!contentEncoding) warnings.push('No HTTP compression (gzip/br) on this page');
    if (!$('meta[charset]').attr('charset') && !$('meta[http-equiv="Content-Type"]').attr('content')) warnings.push('Missing character encoding declaration');
    if (!/<!doctype\s+html/i.test(html)) warnings.push('Missing doctype declaration');

    // Title
    const titleTags = $('title');
    const title = titleTags.first().text().trim();
    const titleLen = title.length;
    if (titleTags.length > 1) warnings.push(`Multiple title tags (${titleTags.length})`);
    if (!title) issues.push('Missing title tag');
    else if (titleLen < 30) warnings.push(`Title too short (${titleLen} chars)`);
    else if (titleLen > 65) warnings.push(`Title too long (${titleLen} chars, aim for 50-65)`);

    // Meta description
    const metaDescTags = $('meta[name="description"]');
    const metaDesc = metaDescTags.first().attr('content') || '';
    const metaLen = metaDesc.trim().length;
    if (metaDescTags.length > 1) warnings.push(`Multiple meta description tags (${metaDescTags.length})`);
    if (!metaDesc.trim()) issues.push('Missing meta description');
    else if (metaLen < 100) warnings.push(`Meta description short (${metaLen} chars)`);
    else if (metaLen > 165) warnings.push(`Meta description long (${metaLen} chars, aim for 140-160)`);

    // Open Graph — affects how the page looks when shared on social/
    // messaging apps rather than search rankings directly, but it's a
    // completeness signal most audit tools check and costs nothing extra
    // to look for since the <head> is already parsed. Combined into one
    // warning rather than three, since sites either have the full set or
    // none of it.
    const hasFullOpenGraph = $('meta[property="og:title"]').attr('content')
      && $('meta[property="og:description"]').attr('content')
      && $('meta[property="og:image"]').attr('content');
    if (!hasFullOpenGraph) warnings.push('Missing Open Graph tags (social sharing preview)');

    // H1
    const h1s = $('h1').map((i, el) => $(el).text().trim()).get();
    if (h1s.length === 0) issues.push('No H1 tag found');
    else if (h1s.length > 1) warnings.push(`Multiple H1 tags (${h1s.length})`);

    // H2s
    const h2s = $('h2').map((i, el) => $(el).text().trim()).get();

    // Heading hierarchy — a skipped level (H3 with no H2 above it, H4 with
    // no H3) confuses the document outline search engines and screen
    // readers build from headings, even when H1 itself is fine. Structural
    // chrome headings are excluded first: WooCommerce's own loop template
    // renders every product title as an H3 inside .products/.product (or a
    // woocommerce-loop-product__title class) regardless of theme, and most
    // themes title their footer widgets with H4 ("Quick Links",
    // "Newsletter", etc.) — neither is part of the page's actual content
    // outline. Confirmed on a real client site: a product/category page
    // whose only H3 was a product card (already excluded) plus two footer
    // widget H4s falsely reported "skips H3" with no real heading problem
    // anywhere in the page's own content.
    // `.products .product` (a card nested inside a grid) is deliberately
    // scoped narrower than a bare `.product` — WooCommerce also wraps a
    // SINGLE product page's entire real content in its own top-level
    // `<div class="product">` (not nested inside `.products`), and
    // excluding that too would silently drop that page's actual content
    // headings from the hierarchy check, not just card titles.
    const isChromeHeading = (el) => $(el).closest('.products .product, [class*="loop-product"], nav, footer').length > 0;
    const h3Count = $('h3').filter((i, el) => !isChromeHeading(el)).length;
    const h4Count = $('h4').filter((i, el) => !isChromeHeading(el)).length;
    if (h3Count > 0 && h2s.length === 0) warnings.push('Heading hierarchy skips H2 (H3 used with no H2 on the page)');
    if (h4Count > 0 && h3Count === 0) warnings.push('Heading hierarchy skips H3 (H4 used with no H3 on the page)');

    // Images without alt
    const images = $('img').toArray();
    const imagesNoAlt = images.filter(img => {
      const alt = $(img).attr('alt');
      return alt === undefined || alt === null;
    });
    const imagesEmptyAlt = images.filter(img => {
      const alt = $(img).attr('alt');
      return alt !== undefined && alt !== null && alt.trim() === '';
    });
    if (imagesNoAlt.length) issues.push(`${imagesNoAlt.length} image(s) missing alt attribute`);
    if (imagesEmptyAlt.length) warnings.push(`${imagesEmptyAlt.length} image(s) have empty alt text`);

    // Images missing explicit width/height — a real Core Web Vitals (CLS)
    // risk, checkable for free from the markup alone without needing a
    // live PageSpeed Insights run.
    const imagesMissingDimensions = images.filter(img => !$(img).attr('width') || !$(img).attr('height'));
    if (imagesMissingDimensions.length) warnings.push(`${imagesMissingDimensions.length} image(s) missing width/height attributes`);

    // Mixed content — an http:// resource loaded on an https:// page.
    // Browsers actively warn/block on this, so it's a real trust and
    // sometimes functional issue, not just a style nit.
    let mixedContentCount = 0;
    if (url.startsWith('https://')) {
      $('img[src]').each((i, el) => { if (($(el).attr('src') || '').startsWith('http://')) mixedContentCount++; });
      $('script[src]').each((i, el) => { if (($(el).attr('src') || '').startsWith('http://')) mixedContentCount++; });
      $('link[rel="stylesheet"][href]').each((i, el) => { if (($(el).attr('href') || '').startsWith('http://')) mixedContentCount++; });
    }
    if (mixedContentCount) issues.push(`${mixedContentCount} mixed-content resource(s) (http:// loaded on an https:// page)`);

    // Canonical
    const canonical = $('link[rel="canonical"]').attr('href') || '';
    if (!canonical) warnings.push('No canonical tag');

    // Phone number(s) shown on this page — from tel: links (a clickable
    // "call us" number is near-always the real contact line, unlike raw
    // digits that might appear in testimonial text or an image) plus
    // LocalBusiness schema's telephone field below. Feeds a cross-page
    // NAP-consistency check after the whole site is crawled (see crawl()).
    const phoneNumbers = new Set();
    $('a[href^="tel:"]').each((i, el) => {
      const norm = normalizePhone(($(el).attr('href') || '').replace('tel:', ''));
      if (norm) phoneNumbers.add(norm);
    });

    // Schema / JSON-LD
    const schemas = $('script[type="application/ld+json"]').toArray();
    const missingLocalBusinessFields = new Set();
    const schemaTypes = schemas.map(s => {
      try {
        const parsed = JSON.parse($(s).html());
        // Many CMS/SEO plugins (e.g. Yoast) nest multiple types under an @graph array
        // instead of a single top-level @type — unwrap that so type detection isn't just "Unknown".
        const items = Array.isArray(parsed['@graph']) ? parsed['@graph'] : [parsed];
        const types = items
          .map(it => it && it['@type'])
          .filter(Boolean)
          .flatMap(t => Array.isArray(t) ? t : [t]);
        // Presence/validity alone doesn't mean the schema is actually
        // useful — a LocalBusiness-type block with no phone/address gives
        // Google (and the LLM narrative, which was catching this manually
        // before) nothing to build a knowledge panel or map listing from.
        for (const item of items) {
          if (!item) continue;
          const itemTypes = Array.isArray(item['@type']) ? item['@type'] : [item['@type']];
          if (!itemTypes.some(t => LOCAL_BUSINESS_TYPES.has(t))) continue;
          if (!item.telephone) missingLocalBusinessFields.add('telephone');
          else { const norm = normalizePhone(item.telephone); if (norm) phoneNumbers.add(norm); }
          if (!item.address) missingLocalBusinessFields.add('address');
          if (!item.priceRange && !item.openingHours && !item.openingHoursSpecification) missingLocalBusinessFields.add('priceRange/openingHours');
        }
        return types.length ? [...new Set(types)].join('+') : 'Unknown';
      } catch { return 'Invalid JSON-LD'; }
    });
    if (schemas.length === 0) issues.push('No JSON-LD schema found');
    // A page can have schema present but malformed — that previously never
    // got flagged at all (schemas.length was non-zero, so the check above
    // silently passed even though the schema is broken and useless to
    // Google). Worth catching as its own, separate issue.
    const invalidSchemaCount = schemaTypes.filter(t => t === 'Invalid JSON-LD').length;
    if (invalidSchemaCount) issues.push(`${invalidSchemaCount} invalid (malformed) JSON-LD schema block(s)`);
    if (missingLocalBusinessFields.size) warnings.push(`LocalBusiness schema missing recommended field(s): ${[...missingLocalBusinessFields].join(', ')}`);

    // Internal links — collected before nav/footer/header get stripped
    // below (they're removed only to keep word-count/readability from
    // counting boilerplate as body content). Most real sites put their
    // primary navigation inside <nav> or <header>, so running this after
    // that removal — as it previously did — meant the crawler practically
    // never discovered any of a normal site's own nav links, only
    // whatever <a> tags happened to sit directly in the body/main content.
    const internalLinks = $('a[href]').toArray()
      .map(a => $(a).attr('href'))
      .filter(h => h && !h.startsWith('http') || (h && h.startsWith(BASE_URL)))
      .filter(h => !h.startsWith('mailto:') && !h.startsWith('tel:'))
      .length;

    // Collect outgoing links for crawl
    const links = [];
    $('a[href]').each((i, el) => {
      const href = $(el).attr('href');
      const abs = normalizeUrl(href, url);
      if (abs && abs.startsWith(BASE_URL) && !shouldIgnore(abs)) {
        links.push(abs);
      }
    });

    // Word count (body text only). Only the FIRST <header> is removed, not
    // every <header> in the document — confirmed on a real client site:
    // WooCommerce's default archive template wraps a category's actual
    // description text in `<header class="woocommerce-products-header">`
    // (many other themes do the same for blog post titles, e.g.
    // `<header class="entry-header">`), so blanket-removing every <header>
    // was deleting real body copy along with the intended target — the
    // site's own chrome header (logo/nav), which is reliably the first
    // <header> in DOM order across virtually every theme/page builder.
    // This was silently truncating both the mechanical word count below
    // AND bodyText, which narrative-report.js's content-quality pass reads
    // directly — so a page like this could have been judged "thin" by the
    // LLM using less than half its actual copy.
    // Product-card containers (`.products .product` — a card nested inside
    // a grid, same narrower scope as isChromeHeading above; a single
    // product page's own top-level `.product` wrapper holds its real
    // content and is deliberately left alone) are removed here too, not
    // just excluded from the heading-hierarchy count above — their price/
    // SKU/"Add to cart" boilerplate is short, punctuation-free label text
    // that (even with the sentence-boundary fix below) can still distort
    // word count and readability on a page with a shop/product-teaser
    // section, the same way nav/footer chrome would.
    $('script, style, nav, footer, .products .product, [class*="loop-product"]').remove();
    $('header').first().remove();

    // A period is appended after each block-level element's own text, when
    // it doesn't already end in sentence punctuation, before the DOM
    // collapses to one flat string below — otherwise a punctuation-free
    // list (a specialty/service tag list, badge row, anything styled as
    // short label fragments rather than prose) merges into one giant
    // run-on "sentence" once everything joins on a single space. Confirmed
    // on a real client homepage: without this, genuinely reasonable
    // content — real paragraphs plus an unpunctuated specialty list —
    // scored -14 on the Flesch scale (impossible to read as "the writing
    // is that bad"); with sentence boundaries preserved at block edges,
    // the same content scored 18. This also makes bodyText itself clearer
    // for narrative-report.js's LLM content-quality pass, which reads it
    // directly — list items no longer run together into one phrase.
    $('p, li, h1, h2, h3, h4, h5, h6, td, blockquote').each((i, el) => {
      const t = $(el).text().trim();
      if (t && !/[.!?]$/.test(t)) $(el).append('.');
    });
    // A leaf <div> (no element children of its own — just text) gets the
    // same treatment: a stats/badge row ("15+ Years Experience", "EMDRIA
    // Certified") is almost always built from a handful of plain <div>s,
    // a tag this project's own CMSes reach for constantly, and none of the
    // specific tags above catch it. Deliberately NOT extended to <span> —
    // that's routinely used purely for inline styling in the middle of a
    // real sentence ("Some <span class='highlight'>important</span>
    // text"), and punctuating every leaf span would corrupt that. A
    // wrapping <div> with its own element children (e.g. a product card,
    // already excluded above) is left alone here too, since its children
    // get their own boundaries and double-punctuating would just add
    // noise.
    $('div').filter((i, el) => $(el).children().length === 0).each((i, el) => {
      const t = $(el).text().trim();
      if (t && !/[.!?]$/.test(t)) $(el).append('.');
    });
    const bodyText = $('body').text().replace(/\s+/g, ' ').trim();
    const wordCount = bodyText.split(' ').filter(w => w.length > 1).length;
    if (wordCount < 150) warnings.push(`Low word count (${wordCount} words)`);

    // Readability (Flesch Reading Ease) — an objective, numeric complement
    // to the LLM's subjective content-quality read. Only flagged when
    // genuinely "very difficult" (score < 30 on the standard 0-100 scale)
    // to avoid nagging about normal professional/clinical language that
    // just isn't a magazine article.
    const readability = calcReadability(bodyText);
    const readabilityScore = readability ? readability.score : null;
    if (readability !== null && readability.score < 30) {
      // The sentence/words-per-sentence/syllables-per-word breakdown is
      // included directly in the finding text (not just the bare score) so
      // an implausible result is self-diagnosing without needing to dig
      // through server logs: a wordsPerSentence average in the
      // double-or-triple digits means something non-prose (a list/label/
      // widget with no punctuation) is still leaking into the extracted
      // text — a genuine extraction problem — whereas a normal
      // wordsPerSentence with high syllablesPerWord means the vocabulary
      // itself is just dense clinical/technical language, a real, if
      // lower-priority, finding rather than a bug.
      //
      // 25-29 gets a separate, deliberately non-scored "Borderline
      // readability" label rather than "Difficult to read" — a single
      // point on either side of an arbitrary 30-point cutoff isn't a
      // meaningful pass/fail line, and this tier is excluded from the
      // scoring patterns below (see scoredWarningPatterns) so it doesn't
      // cost score at all, only genuinely low scores (<25) do. It still
      // appears in Findings by Page either way.
      const label = readability.score >= 25 ? 'Borderline readability' : 'Difficult to read';
      warnings.push(`${label} (Flesch reading ease: ${readability.score}/100 — ${readability.sentenceCount} sentences, ${readability.wordsPerSentence} words/sentence average, ${readability.syllablesPerWord} syllables/word)`);
      // Full diagnostic detail, including the actual extracted text, goes
      // to the server console rather than the client-facing report — the
      // report is for the client, this is for us to verify exactly what
      // the crawler read when a score looks implausible.
      console.log(`  [readability] ${url}: score=${readability.score} words=${readability.wordCount} sentences=${readability.sentenceCount} words/sentence=${readability.wordsPerSentence} syllables/word=${readability.syllablesPerWord}`);
      console.log(`  [readability] extracted text (first 500 chars): ${bodyText.slice(0, 500)}`);
    }

    return {
      url,
      title,
      titleLen,
      metaDesc: metaDesc.trim(),
      metaLen,
      h1: h1s[0] || '',
      h1Count: h1s.length,
      h2s,
      imageCount: images.length,
      imagesNoAlt: imagesNoAlt.length,
      imagesEmptyAlt: imagesEmptyAlt.length,
      canonical,
      schemaTypes,
      phoneNumbers: [...phoneNumbers],
      wordCount,
      readabilityScore,
      // Kept (not just wordCount) so narrative-report.js's content-quality
      // pass can read actual page copy — judging thin/generic writing,
      // missing credentials, or tone requires the real text, not just a
      // count of it. Never rendered directly by this file's own HTML/docx
      // report; those still only use the structural fields above.
      bodyText,
      internalLinks,
      issues,
      warnings,
      links
    };
  }

  async function crawl(onProgress) {
    // Many CMSes (WordPress included) pick one canonical domain — www vs
    // non-www, or http vs https — and 301-redirect the other to it.
    // fetch() follows that redirect transparently, but if the client's
    // configured URL is the non-canonical form, every one of the site's
    // own internal links (generated by the CMS using its canonical
    // domain) would otherwise get silently rejected as "off-site" by
    // normalizeUrl()'s hostname check below — the crawl finds the
    // homepage, discovers zero links worth following, and stops there,
    // even on an otherwise perfectly healthy multi-page site. Resolve the
    // real origin once, up front, before anything else (sitemap seeding
    // and robots.txt are also subject to the same mismatch) so the rest
    // of this crawl uses the domain the site actually serves from.
    try {
      const probe = await fetch(BASE_URL + '/', { headers: BROWSER_HEADERS, timeout: 12000, redirect: 'follow' });
      const resolvedOrigin = new URL(probe.url).origin;
      if (resolvedOrigin !== new URL(BASE_URL).origin) {
        console.log(`  Configured URL redirects to ${resolvedOrigin} — using that as the site's domain for this crawl.`);
        BASE_URL = resolvedOrigin;
      }
    } catch {
      // Fall through with the configured BASE_URL — the main crawl loop's
      // own fetch of the homepage below will surface the real error either way.
    }

    // One cache-busting value for this whole crawl (see fetchPage/bustedUrl)
    // rather than a fresh one per request — every request in this run
    // still gets a unique, cache-defeating URL relative to any other run,
    // but requests within the same run share a single identifiable value.
    const cacheBust = Date.now();
    console.log(`  Crawl run cache-bust value: ${cacheBust}`);

    const visited = new Set();
    const queue = [BASE_URL + '/'];
    const results = [];
    // target URL -> Set of page URLs that link to it — lets a 404'd page
    // be attributed to whichever page(s) actually reference it, instead of
    // just being counted as "a broken page exists" with no actionable
    // source. Populated as links are discovered below.
    const linkSources = new Map();

    async function seedFromSitemap() {
      const sitemapUrl = `${BASE_URL}/sitemap.xml`;
      try {
        const locs = await parseSitemap(sitemapUrl);
        if (!locs.length) { console.log(`  (sitemap not found, crawling by links only)`); return false; }
        let added = 0;
        for (const loc of locs) {
          const norm = normalizeUrl(loc, BASE_URL);
          if (norm && norm.startsWith(BASE_URL) && !shouldIgnore(norm) && !visited.has(norm)) {
            queue.push(norm);
            added++;
          }
        }
        console.log(`  Sitemap: found ${locs.length} URLs, queued ${added} new pages`);
        return true;
      } catch (e) {
        console.log(`  (sitemap fetch failed: ${e.message})`);
        return false;
      }
    }

    console.log(`\nAuditing: ${client.name} (${BASE_URL})`);
    console.log(`Max pages: ${MAX_PAGES}\n`);

    // Kicked off alongside the sitemap fetch (both are one-time, cheap
    // requests) rather than sequentially, to avoid adding to crawl time.
    const disallowedPrefixesPromise = fetchDisallowedPrefixes(BASE_URL);
    const hasSitemap = await seedFromSitemap();

    let count = 0;
    while (queue.length > 0 && count < MAX_PAGES) {
      const url = queue.shift();
      const normalized = normalizeUrl(url, BASE_URL) || url;
      if (visited.has(normalized)) continue;
      visited.add(normalized);

      process.stdout.write(`[${count + 1}] ${normalized.replace(BASE_URL, '')} ... `);

      const { html, error, contentEncoding } = await fetchPage(normalized, cacheBust);
      if (error) {
        console.log(`ERROR: ${error}`);
        results.push({ url: normalized, error });
        count++;
        if (onProgress) onProgress({ pagesCrawled: count, totalQueued: queue.length + count, currentUrl: normalized, error });
        continue;
      }

      const data = analyzePage(normalized, html, contentEncoding);
      results.push(data);

      const issueCount = data.issues.length;
      const warnCount = data.warnings.length;
      const flag = issueCount > 0 ? '!' : (warnCount > 0 ? '~' : 'OK');
      console.log(`${flag}  (${issueCount} issues, ${warnCount} warnings, ${data.wordCount} words)`);

      // Queue new links
      for (const link of data.links) {
        if (!linkSources.has(link)) linkSources.set(link, new Set());
        linkSources.get(link).add(normalized);
        if (!visited.has(link)) queue.push(link);
      }

      count++;
      if (onProgress) onProgress({ pagesCrawled: count, totalQueued: queue.length + count, currentUrl: normalized, issueCount, warnCount });
      await new Promise(r => setTimeout(r, 300)); // polite crawl
    }

    // A page can be perfectly built and still never get indexed if
    // robots.txt disallows its path — can only be checked after the full
    // crawl (fetchDisallowedPrefixes) is done, same as the cross-page
    // duplicate checks in annotateDuplicates().
    const disallowedPrefixes = await disallowedPrefixesPromise;
    if (disallowedPrefixes.length) {
      for (const page of results) {
        if (page.error) continue;
        const path = page.url.replace(BASE_URL, '') || '/';
        if (disallowedPrefixes.some(prefix => path.startsWith(prefix))) {
          page.issues.push('Blocked by robots.txt');
        }
      }
    }

    // Attribute broken internal links to whichever page(s) actually link to
    // them — a raw count of 404'd URLs isn't actionable on its own; knowing
    // "the /about page links to something broken" is what someone can fix.
    // The actual destination and its response (e.g. "HTTP 404") are both
    // included — a link that's broken on every single page is almost
    // always one shared template element (nav/footer/a logged-in-only
    // admin toolbar leaking into a cached page), and there's no way to
    // tell which without seeing exactly where it points and how it failed.
    //
    // A confirmed-blocked destination (fetchPage's retry-without-cache-bust
    // still got 403 — see fetchPage) goes in a separate, lower-certainty
    // bucket and a warning rather than an issue: the page might load fine
    // for real visitors and only be denied to this kind of automated
    // request (a security plugin/WAF rule), so it shouldn't carry the same
    // "this is definitely broken" weight as a genuine 404/5xx — confirmed
    // on a real client site where a Privacy Policy page that loads
    // normally for visitors came back 403 for the auditor specifically.
    for (const errored of results.filter(r => r.error)) {
      const sources = linkSources.get(errored.url);
      if (!sources) continue;
      const bucket = errored.error.includes('(blocked)') ? '_blockedLinks' : '_brokenLinks';
      for (const sourceUrl of sources) {
        const sourcePage = results.find(p => p.url === sourceUrl && !p.error);
        if (sourcePage) {
          if (!sourcePage[bucket]) sourcePage[bucket] = new Map();
          sourcePage[bucket].set(errored.url, errored.error);
        }
      }
    }
    function describeLinks(map) {
      const entries = [...map].map(([u, err]) => `${u.replace(BASE_URL, '') || '/'} (${err.replace(' (blocked)', '')})`);
      return { count: entries.length, text: `${entries.slice(0, 3).join(', ')}${entries.length > 3 ? ` and ${entries.length - 3} more` : ''}` };
    }
    for (const page of results) {
      if (page._brokenLinks && page._brokenLinks.size) {
        const { count, text } = describeLinks(page._brokenLinks);
        page.issues.push(`Links to ${count} broken internal page${count > 1 ? 's' : ''}: ${text}`);
        delete page._brokenLinks;
      }
      if (page._blockedLinks && page._blockedLinks.size) {
        const { count, text } = describeLinks(page._blockedLinks);
        page.warnings.push(`Links to ${count} internal page${count > 1 ? 's' : ''} the auditor was blocked from reaching: ${text} — may be a security/bot-blocking rule rather than a genuinely broken link; verify manually`);
        delete page._blockedLinks;
      }
    }

    // Orphan pages: crawled successfully (whether found via sitemap or a
    // link) but no other page on the site actually links to it. Reuses
    // linkSources from the broken-link check above — a page only appears
    // there if some other page's <a href> pointed at it, so an absent
    // entry means it's reachable only via the sitemap or a direct URL,
    // never through normal site navigation.
    // normalizeUrl() strips trailing slashes (see above), so the homepage's
    // actual stored page.url is BASE_URL with no trailing slash — comparing
    // against a literal `${BASE_URL}/` here would never match and every
    // audit's homepage would wrongly get flagged as its own orphan page.
    const homepageUrl = normalizeUrl(`${BASE_URL}/`, BASE_URL) || `${BASE_URL}/`;
    for (const page of results) {
      if (page.error || page.url === homepageUrl) continue;
      const sources = linkSources.get(page.url);
      if (!sources || sources.size === 0) {
        page.warnings.push('Orphan page (no internal links point to it)');
      }
    }

    // NAP consistency: if more than one distinct phone number shows up
    // across the site, find the one appearing on the most pages (the
    // site's "real" number) and flag every page whose own number(s) don't
    // include it — e.g. a footer template that never got updated after a
    // number change. Pages with no phone number at all aren't flagged;
    // this only catches an actual mismatch, not an absence.
    const phoneCounts = new Map();
    for (const page of results) {
      if (page.error) continue;
      for (const ph of page.phoneNumbers || []) phoneCounts.set(ph, (phoneCounts.get(ph) || 0) + 1);
    }
    let dominantPhone = null;
    if (phoneCounts.size > 1) {
      dominantPhone = [...phoneCounts.entries()].sort((a, b) => b[1] - a[1])[0][0];
      for (const page of results) {
        if (page.error || !page.phoneNumbers || !page.phoneNumbers.length) continue;
        if (!page.phoneNumbers.includes(dominantPhone)) {
          page.warnings.push(`Phone number doesn't match the site's primary number (${formatPhone(dominantPhone)})`);
        }
      }
    } else if (phoneCounts.size === 1) {
      dominantPhone = [...phoneCounts.keys()][0];
    }

    console.log(`\nCrawled ${results.length} pages.`);
    return { results, hasSitemap, dominantPhone };
  }

  function buildReport(results, scoreData, history = [], metrics = null, liveGSC = false, keywordHistory = [], provider = PROVIDERS['1'], hasSitemap = true) {
    const pages = results.filter(r => !r.error);
    const errors = results.filter(r => r.error);
    const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    // The provider (agency issuing the report) drives the letterhead colors;
    // client.brand_color, if set, is kept only as a fallback for older configs.
    const brand = provider.brand || client.brand_color || '#003366';
    const brand2 = provider.brand2 || brand;
    const accent = provider.accent || brand2;

    const scoreColor = scoreData.score >= 80 ? '#22c55e' : scoreData.score >= 60 ? '#f59e0b' : '#ef4444';
    const scoreLabel = scoreData.score >= 85 ? 'Good' : scoreData.score >= 70 ? 'Needs Improvement' : 'Needs Attention';

    // Score ring gauge on the cover — history is ascending by date with
    // today's just-computed score appended last (see runAudit), so the
    // previous run (if any) is one entry back.
    const RING_R = 46;
    const RING_CIRC = 2 * Math.PI * RING_R;
    const ringOffset = RING_CIRC * (1 - Math.max(0, Math.min(100, scoreData.score)) / 100);
    const prevScore = history.length >= 2 ? history[history.length - 2].score : null;
    const scoreDelta = prevScore !== null ? scoreData.score - prevScore : null;
    const scoreDeltaBadge = scoreDelta === null ? '' : `
      <div class="score-delta-badge ${scoreDelta > 0 ? 'up' : scoreDelta < 0 ? 'down' : 'flat'}">
        ${scoreDelta > 0 ? '&#9650;' : scoreDelta < 0 ? '&#9660;' : '&#8212;'} ${scoreDelta === 0 ? 'No change' : `${scoreDelta > 0 ? '+' : ''}${scoreDelta}`}
      </div>`;

    // Aggregate issues across all pages for summary
    const criticalPages = pages.filter(p => p.issues.length > 0);
    const warningPages = pages.filter(p => p.warnings.length > 0);
    const healthyPages = pages.filter(p => p.issues.length === 0 && p.warnings.length === 0);

    const totalIssues = pages.reduce((a, p) => a + p.issues.length, 0);
    const totalWarnings = pages.reduce((a, p) => a + p.warnings.length, 0);
    const noSchemaPgs = pages.filter(p => p.schemaTypes.length === 0);
    const noMetaPgs = pages.filter(p => !p.metaDesc);
    const noTitlePgs = pages.filter(p => !p.title);
    const noH1Pgs = pages.filter(p => p.h1Count === 0);
    const missingAltPgs = pages.filter(p => p.imagesNoAlt > 0);
    const badTitlePgs = pages.filter(p => p.title && (p.titleLen < 30 || p.titleLen > 65));
    const multipleH1Pgs = pages.filter(p => p.h1Count > 1);
    const duplicateTitlePgs = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate title tag')));
    const duplicateMetaPgs = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate meta description')));
    const duplicateH1Pgs = pages.filter(p => p.warnings.some(w => w.startsWith('Duplicate H1 tag')));
    const noindexedPgs = pages.filter(p => p.issues.includes('Meta robots tag set to noindex'));
    const robotsBlockedPgs = pages.filter(p => p.issues.includes('Blocked by robots.txt'));
    const missingViewportPgs = pages.filter(p => p.warnings.includes('Missing viewport meta tag'));
    const missingLangPgs = pages.filter(p => p.warnings.includes('Missing html lang attribute'));
    const missingCompressionPgs = pages.filter(p => p.warnings.some(w => w.startsWith('No HTTP compression')));
    const multipleTitleTagsPgs = pages.filter(p => p.warnings.some(w => w.startsWith('Multiple title tags')));
    const multipleMetaTagsPgs = pages.filter(p => p.warnings.some(w => w.startsWith('Multiple meta description tags')));
    const invalidSchemaPgs = pages.filter(p => p.issues.some(i => i.includes('invalid (malformed) JSON-LD')));
    const mixedContentPgs = pages.filter(p => p.issues.some(i => i.includes('mixed-content resource')));
    const brokenLinkPgs = pages.filter(p => p.issues.some(i => i.startsWith('Links to') && i.includes('broken internal')));
    const missingImageDimensionPgs = pages.filter(p => p.warnings.some(w => w.includes('missing width/height attributes')));
    const missingOgTagsPgs = pages.filter(p => p.warnings.some(w => w.startsWith('Missing Open Graph tags')));
    const missingCharsetPgs = pages.filter(p => p.warnings.includes('Missing character encoding declaration'));
    const missingDoctypePgs = pages.filter(p => p.warnings.includes('Missing doctype declaration'));
    const orphanPgs = pages.filter(p => p.warnings.includes('Orphan page (no internal links point to it)'));
    const skippedHeadingPgs = pages.filter(p => p.warnings.some(w => w.startsWith('Heading hierarchy skips')));
    const difficultReadingPgs = pages.filter(p => p.warnings.some(w => w.startsWith('Difficult to read')));
    const missingLocalBusinessFieldPgs = pages.filter(p => p.warnings.some(w => w.startsWith('LocalBusiness schema missing')));
    const inconsistentPhonePgs = pages.filter(p => p.warnings.some(w => w.startsWith("Phone number doesn't match")));

    function priorityBadge(p) {
      if (p === 'critical') return `<span class="priority critical">Critical</span>`;
      if (p === 'high') return `<span class="priority high">High</span>`;
      if (p === 'medium') return `<span class="priority medium">Medium</span>`;
      return `<span class="priority low">Low</span>`;
    }

    function pageRow(p) {
      const slug = p.url.replace(BASE_URL, '') || '/';
      const status = p.issues.length ? 'critical' : p.warnings.length ? 'warn' : 'ok';
      const statusIcon = p.issues.length ? '&#9888;' : p.warnings.length ? '&#9651;' : '&#10003;';
      return `
      <tr>
        <td class="status-cell ${status}">${statusIcon}</td>
        <td class="url-cell"><a href="${p.url}" target="_blank">${slug || '/'}</a></td>
        <td>${p.title || '<span class="missing">Missing</span>'}</td>
        <td class="center">${p.h1Count === 0 ? '<span class="missing">None</span>' : p.h1Count > 1 ? `<span class="warn-text">${p.h1Count}</span>` : '&#10003;'}</td>
        <td class="center">${p.metaDesc ? '&#10003;' : '<span class="missing">Missing</span>'}</td>
        <td class="center">${p.schemaTypes.length ? p.schemaTypes.join(', ') : '<span class="warn-text">None</span>'}</td>
        <td class="center">${p.imagesNoAlt > 0 ? `<span class="missing">${p.imagesNoAlt} missing</span>` : p.imagesEmptyAlt > 0 ? `<span class="warn-text">${p.imagesEmptyAlt} empty</span>` : '&#10003;'}</td>
      </tr>`;
    }

    function findingBlock(p) {
      const slug = p.url.replace(BASE_URL, '') || '/';
      const allItems = [
        ...p.issues.map(i => ({ ...friendlyIssue(i), type: 'issue' })),
        ...p.warnings.map(w => ({ ...friendlyIssue(w), type: 'warn' }))
      ];
      return `
      <div class="finding-card ${p.issues.length ? 'has-critical' : 'has-warn'}">
        <div class="finding-url">${slug}</div>
        ${allItems.map(item => `
          <div class="finding-item">
            <div class="finding-top">
              ${priorityBadge(item.priority)}
              <span class="finding-label">${item.label}</span>
            </div>
            ${item.why ? `<div class="finding-why">${item.why}</div>` : ''}
          </div>
        `).join('')}
      </div>`;
    }

    const allIssuePages = pages
      .filter(p => p.issues.length > 0 || p.warnings.length > 0)
      .sort((a, b) => (b.issues.length * 10 + b.warnings.length) - (a.issues.length * 10 + a.warnings.length));

    const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SEO Audit: ${client.name} — ${date}</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6fa; color: #1e293b; font-size: 13px; line-height: 1.5; }

  /* ── Cover Page ── */
  .cover { background: ${brand}; color: white; min-height: 220px; padding: 48px 56px 44px; display: flex; flex-direction: column; page-break-after: always; border-bottom: 4px solid ${accent}; }
  .cover-header { display: flex; justify-content: space-between; align-items: flex-start; }
  .cover-agency { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; opacity: .7; display: flex; align-items: center; gap: 8px; }
  .cover-logo { height: 22px; max-width: 120px; object-fit: contain; }
  .cover-score-box { background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.4); border-radius: 16px; padding: 14px 20px; text-align: center; flex-shrink: 0; }
  .score-ring-wrap { position: relative; width: 100px; height: 100px; margin: 0 auto; }
  .score-ring-wrap svg { display: block; }
  .score-ring-num { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
  .score-ring-num .n { font-size: 28px; font-weight: 800; line-height: 1; }
  .score-ring-num .d { font-size: 9px; opacity: .75; margin-top: 1px; }
  .cover-score-label { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; opacity: .8; margin-top: 6px; }
  .score-delta-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; margin-top: 6px; }
  .score-delta-badge.up { background: rgba(74,222,128,.25); color: #dcfce7; }
  .score-delta-badge.down { background: rgba(248,113,113,.25); color: #fee2e2; }
  .score-delta-badge.flat { background: rgba(255,255,255,.15); color: rgba(255,255,255,.85); }
  .cover-body { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 40px 0 32px; }
  .cover-title { font-size: 34px; font-weight: 800; margin-bottom: 8px; line-height: 1.15; }
  .cover-sub { font-size: 14px; opacity: .7; }
  .cover-divider { height: 2px; background: rgba(255,255,255,.2); margin: 28px 0; }
  .cover-meta { display: flex; gap: 0; }
  .cover-meta-item { flex: 1; padding-right: 16px; border-right: 1px solid rgba(255,255,255,.15); margin-right: 16px; }
  .cover-meta-item:last-child { border: none; margin: 0; }
  .cover-meta-item .cm-label { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; opacity: .55; }
  .cover-meta-item .cm-val { font-size: 15px; font-weight: 700; margin-top: 4px; }
  .cover-note { margin-top: 28px; font-size: 10.5px; opacity: .55; max-width: 580px; line-height: 1.6; }

  /* ── Layout ── */
  .container { max-width: 960px; margin: 0 auto; padding: 40px 32px; }
  .section { margin-bottom: 40px; page-break-inside: avoid; }
  .section-title { font-size: 15px; font-weight: 700; color: #0f172a; padding-bottom: 10px; border-bottom: 2px solid ${brand}; margin-bottom: 18px; text-transform: uppercase; letter-spacing: .06em; }

  /* ── Snapshot Cards ── */
  .snapshot { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 36px; }
  .snap-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.07); text-align: center; }
  .snap-num { font-size: 30px; font-weight: 800; }
  .snap-num.good { color: #16a34a; }
  .snap-num.warn { color: #d97706; }
  .snap-num.bad { color: #dc2626; }
  .snap-num.neutral { color: ${brand}; }
  .snap-label { font-size: 11px; color: #64748b; margin-top: 4px; }

  /* ── Summary Bullets ── */
  .summary-box { background: white; border-radius: 10px; padding: 24px 28px; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
  .summary-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
  .summary-row:last-child { border: none; }
  .summary-row .sr-label { color: #374151; }
  .summary-row .sr-val { font-weight: 700; }
  .sr-val.bad { color: #dc2626; }
  .sr-val.warn { color: #d97706; }
  .sr-val.good { color: #16a34a; }

  /* ── Priority badges ── */
  .priority { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
  .priority.critical { background: #fee2e2; color: #b91c1c; }
  .priority.high { background: #ffedd5; color: #c2410c; }
  .priority.medium { background: #fef9c3; color: #92400e; }
  .priority.low { background: #f1f5f9; color: #475569; }

  /* ── Findings ── */
  .finding-card { background: white; border-radius: 10px; padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 12px; border-left: 4px solid #e2e8f0; page-break-inside: avoid; }
  .finding-card.has-critical { border-left-color: #dc2626; }
  .finding-card.has-warn { border-left-color: #f59e0b; }
  .finding-url { font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 10px; font-family: monospace; }
  .finding-item { padding: 7px 0; border-bottom: 1px solid #f8fafc; }
  .finding-item:last-child { border: none; padding-bottom: 0; }
  .finding-top { display: flex; align-items: center; gap: 10px; margin-bottom: 3px; }
  .finding-label { font-size: 13px; font-weight: 600; color: #1e293b; }
  .finding-why { font-size: 12px; color: #64748b; padding-left: 2px; margin-top: 2px; }

  /* ── Page Table ── */
  .page-table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); font-size: 12px; }
  th { background: #f8fafc; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: #64748b; border-bottom: 2px solid #e2e8f0; }
  th.center, td.center { text-align: center; }
  td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  tr:last-child td { border: none; }
  tr:hover td { background: #f8fafc; }
  td a { color: ${brand}; text-decoration: none; font-family: monospace; font-size: 11px; }
  td a:hover { text-decoration: underline; }
  .status-cell { width: 28px; text-align: center; font-size: 14px; }
  .status-cell.critical { color: #dc2626; }
  .status-cell.warn { color: #d97706; }
  .status-cell.ok { color: #16a34a; }
  .url-cell { max-width: 220px; }
  .missing { color: #dc2626; font-weight: 600; }
  .warn-text { color: #d97706; font-weight: 600; }

  /* ── Score trend ── */
  .cover-trend { margin-top: 20px; }
  .trend-label { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; opacity: .55; margin-bottom: 6px; }

  /* ── Quick wins ── */
  .qw-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
  .qw-card { background: white; border-radius: 10px; padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-top: 3px solid ${brand2}; }
  .qw-top { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
  .qw-action { font-size: 13px; font-weight: 700; color: #0f172a; }
  .qw-detail { font-size: 12px; color: #64748b; line-height: 1.5; }
  .qw-badge { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
  .qw-badge.effort-Low { background: #dcfce7; color: #166534; }
  .qw-badge.effort-Medium { background: #fef9c3; color: #92400e; }
  .qw-badge.effort-High { background: #ffedd5; color: #c2410c; }

  /* ── Performance metrics ── */
  .perf-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
  .perf-period { font-size: 12px; color: #64748b; }
  .perf-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
  .perf-card { background: white; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-bottom: 3px solid ${brand2}; }
  .perf-value { font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1.1; }
  .perf-label { font-size: 11px; color: #64748b; margin-top: 4px; margin-bottom: 8px; }
  .perf-change { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
  .perf-change.up   { background: #dcfce7; color: #166534; }
  .perf-change.down { background: #fee2e2; color: #991b1b; }
  .perf-arrow { font-size: 10px; }

  /* ── Keyword tracker ── */
  .kw-change.improved { color: #16a34a; font-weight: 700; }
  .kw-change.declined { color: #dc2626; font-weight: 700; }
  .kw-change.new      { color: #7c3aed; font-weight: 700; }
  .kw-change.same     { color: #94a3b8; }
  .kw-pos             { font-weight: 700; color: #0f172a; }

  /* ── Footer ── */
  .report-footer { text-align: center; color: #94a3b8; font-size: 11px; padding: 32px 0 20px; border-top: 1px solid #e2e8f0; margin-top: 48px; }

  /* ── Print ── */
  @media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    @page { margin: 0.55in 0.65in; }
    @page :left { margin-left: 0.65in; }
    @page :right { margin-right: 0.65in; }

    body { background: white !important; font-size: 11px; line-height: 1.45; }

    /* Cover: fill entire first page, force new page after */
    .cover {
      min-height: 96vh;
      padding: 48px 56px 44px;
      page-break-after: always;
      break-after: page;
    }

    /* Container tighter in print */
    .container { padding: 20px 0; }

    /* Snapshot grid: 4-up stays, just tighter */
    .snapshot { gap: 10px; margin-bottom: 24px; }
    .snap-card { padding: 14px 10px; box-shadow: none !important; border: 1px solid #e2e8f0; }
    .snap-num { font-size: 24px; }

    /* Summary */
    .summary-box { box-shadow: none !important; border: 1px solid #e2e8f0; padding: 16px 20px; }
    .summary-row { padding: 6px 0; }

    /* Section headings: start a new page only for major sections */
    .section { margin-bottom: 24px; }
    .section-title { font-size: 12px; padding-bottom: 7px; margin-bottom: 12px; }

    /* Keep finding cards together but allow section to break */
    .finding-card {
      page-break-inside: avoid;
      break-inside: avoid;
      box-shadow: none !important;
      border: 1px solid #e2e8f0;
      border-left-width: 4px;
      padding: 12px 16px;
      margin-bottom: 8px;
    }

    /* Table: allow rows to flow across pages, keep header visible */
    table { box-shadow: none !important; border: 1px solid #e2e8f0; font-size: 10px; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; break-inside: avoid; }
    td, th { padding: 6px 8px; }
    .page-table-wrap { overflow: visible; }

    /* Links: plain text */
    a { color: inherit !important; text-decoration: none !important; }
    td a { color: ${brand2} !important; font-size: 10px; }

    /* Page numbers via CSS counters */
    body { counter-reset: page-num; }
    .container::after {
      counter-increment: page-num;
    }
    .report-footer {
      margin-top: 24px;
      padding: 16px 0 8px;
    }

    /* Hide hover effects */
    tr:hover td { background: transparent !important; }
  }
</style>
</head>
<body>

<!-- COVER -->
<div class="cover">
  <div class="cover-header">
    <div class="cover-agency">${provider.logo ? `<img src="${provider.logo}" alt="${provider.name}" class="cover-logo">` : ''}${provider.name} &bull; SEO Audit</div>
    <div class="cover-score-box">
      <div class="score-ring-wrap">
        <svg viewBox="0 0 100 100" width="100" height="100">
          <circle cx="50" cy="50" r="${RING_R}" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="8"/>
          <circle cx="50" cy="50" r="${RING_R}" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round"
            stroke-dasharray="${RING_CIRC.toFixed(2)}" stroke-dashoffset="${ringOffset.toFixed(2)}"
            transform="rotate(-90 50 50)"/>
        </svg>
        <div class="score-ring-num">
          <span class="n">${scoreData.score}</span>
          <span class="d">/ 100</span>
        </div>
      </div>
      <div class="cover-score-label">SEO Health Score</div>
      <div style="font-size:11px;margin-top:4px;opacity:.9">${scoreLabel}</div>
      ${scoreDeltaBadge}
    </div>
  </div>
  <div class="cover-body">
    <div class="cover-title">${client.name}</div>
    <div class="cover-sub">${BASE_URL}</div>
    <div class="cover-divider"></div>
    <div class="cover-meta">
      <div class="cover-meta-item"><div class="cm-label">Report Date</div><div class="cm-val">${date}</div></div>
      <div class="cover-meta-item"><div class="cm-label">Pages Audited</div><div class="cm-val">${pages.length}</div></div>
      <div class="cover-meta-item"><div class="cm-label">Critical Issues</div><div class="cm-val">${totalIssues === 0 ? 'None' : totalIssues}</div></div>
      <div class="cover-meta-item"><div class="cm-label">Improvement Opportunities</div><div class="cm-val">${warningPages.length} of ${pages.length} pages</div></div>
      <div class="cover-meta-item"><div class="cm-label">Fully Optimized</div><div class="cm-val">${healthyPages.length} of ${pages.length} pages</div></div>
    </div>
  </div>
  ${buildTrendChart(history)}
  <div class="cover-note"><strong>Score note:</strong> The SEO Health Score deducts heavily for critical issues (missing titles, H1s, meta descriptions, image alt text), a moderate amount for present-but-wrong titles/H1s (too short, too long, or duplicated), and a smaller amount for other improvement opportunities such as schema markup, canonical tags, and content depth.</div>
</div>

<div class="container">

  <!-- SNAPSHOT -->
  <div class="snapshot">
    <div class="snap-card">
      <div class="snap-num ${noTitlePgs.length === 0 ? 'good' : 'bad'}">${noTitlePgs.length}</div>
      <div class="snap-label">Pages Missing Title</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${noMetaPgs.length === 0 ? 'good' : 'bad'}">${noMetaPgs.length}</div>
      <div class="snap-label">Missing Meta Description</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${noH1Pgs.length === 0 ? 'good' : 'bad'}">${noH1Pgs.length}</div>
      <div class="snap-label">Pages Missing H1</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${multipleH1Pgs.length === 0 ? 'good' : 'warn'}">${multipleH1Pgs.length}</div>
      <div class="snap-label">Pages w/ Multiple H1s</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${badTitlePgs.length === 0 ? 'good' : 'warn'}">${badTitlePgs.length}</div>
      <div class="snap-label">Poorly Sized Title Tags</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${noSchemaPgs.length === 0 ? 'good' : 'warn'}">${noSchemaPgs.length}</div>
      <div class="snap-label">Pages Without Schema</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${missingAltPgs.length === 0 ? 'good' : 'warn'}">${missingAltPgs.length}</div>
      <div class="snap-label">Pages w/ Missing Image Alt</div>
    </div>
    <div class="snap-card">
      <div class="snap-num neutral">${pages.filter(p => !p.canonical).length}</div>
      <div class="snap-label">Missing Canonical Tag</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${duplicateTitlePgs.length === 0 ? 'good' : 'warn'}">${duplicateTitlePgs.length}</div>
      <div class="snap-label">Duplicate Title Tags</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${duplicateMetaPgs.length === 0 ? 'good' : 'warn'}">${duplicateMetaPgs.length}</div>
      <div class="snap-label">Duplicate Meta Descriptions</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${hasSitemap ? 'good' : 'warn'}">${hasSitemap ? 'Yes' : 'No'}</div>
      <div class="snap-label">Sitemap.xml Found</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${duplicateH1Pgs.length === 0 ? 'good' : 'warn'}">${duplicateH1Pgs.length}</div>
      <div class="snap-label">Duplicate H1 Tags</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${noindexedPgs.length === 0 ? 'good' : 'bad'}">${noindexedPgs.length}</div>
      <div class="snap-label">Pages Set to Noindex</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${robotsBlockedPgs.length === 0 ? 'good' : 'bad'}">${robotsBlockedPgs.length}</div>
      <div class="snap-label">Blocked by robots.txt</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${missingViewportPgs.length === 0 ? 'good' : 'warn'}">${missingViewportPgs.length}</div>
      <div class="snap-label">Missing Viewport Tag</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${missingLangPgs.length === 0 ? 'good' : 'warn'}">${missingLangPgs.length}</div>
      <div class="snap-label">Missing HTML Lang</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${missingCompressionPgs.length === 0 ? 'good' : 'warn'}">${missingCompressionPgs.length}</div>
      <div class="snap-label">No HTTP Compression</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${(multipleTitleTagsPgs.length + multipleMetaTagsPgs.length) === 0 ? 'good' : 'warn'}">${multipleTitleTagsPgs.length + multipleMetaTagsPgs.length}</div>
      <div class="snap-label">Multiple Title/Meta Tags</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${invalidSchemaPgs.length === 0 ? 'good' : 'bad'}">${invalidSchemaPgs.length}</div>
      <div class="snap-label">Invalid Schema Markup</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${mixedContentPgs.length === 0 ? 'good' : 'bad'}">${mixedContentPgs.length}</div>
      <div class="snap-label">Mixed-Content Resources</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${brokenLinkPgs.length === 0 ? 'good' : 'bad'}">${brokenLinkPgs.length}</div>
      <div class="snap-label">Pages w/ Broken Internal Links</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${missingImageDimensionPgs.length === 0 ? 'good' : 'warn'}">${missingImageDimensionPgs.length}</div>
      <div class="snap-label">Images Missing Dimensions</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${orphanPgs.length === 0 ? 'good' : 'warn'}">${orphanPgs.length}</div>
      <div class="snap-label">Orphan Pages</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${skippedHeadingPgs.length === 0 ? 'good' : 'warn'}">${skippedHeadingPgs.length}</div>
      <div class="snap-label">Skipped Heading Levels</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${difficultReadingPgs.length === 0 ? 'good' : 'warn'}">${difficultReadingPgs.length}</div>
      <div class="snap-label">Difficult-to-Read Pages</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${missingLocalBusinessFieldPgs.length === 0 ? 'good' : 'warn'}">${missingLocalBusinessFieldPgs.length}</div>
      <div class="snap-label">Incomplete LocalBusiness Schema</div>
    </div>
    <div class="snap-card">
      <div class="snap-num ${inconsistentPhonePgs.length === 0 ? 'good' : 'warn'}">${inconsistentPhonePgs.length}</div>
      <div class="snap-label">Inconsistent Phone Number</div>
    </div>
    <div class="snap-card">
      <div class="snap-num good">${healthyPages.length}</div>
      <div class="snap-label">Fully Healthy Pages</div>
    </div>
    <div class="snap-card">
      <div class="snap-num neutral">${pages.length}</div>
      <div class="snap-label">Total Pages Audited</div>
    </div>
  </div>

  <!-- PERFORMANCE METRICS -->
  ${metrics ? (() => {
    const periodLabel = liveGSC
      ? `Live data &bull; ${metrics.period} &bull; ${metrics.comparisonLabel}`
      : (() => { try { return `Snapshot: ${new Date(metrics.snapshot_date + 'T12:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} &bull; ${metrics.comparison_label}`; } catch { return ''; } })();

    const cards = metrics.metrics.map(m => `
      <div class="perf-card">
        <div class="perf-value">${m.value}</div>
        <div class="perf-label">${m.label}</div>
        ${m.change ? `<span class="perf-change ${m.direction === 'up' ? 'up' : 'down'}">
          <span class="perf-arrow">${m.direction === 'up' ? '▲' : '▼'}</span> ${m.change}
        </span>` : ''}
      </div>`).join('');

    const keywordsTable = liveGSC && metrics.topKeywords && metrics.topKeywords.length ? `
      <div style="margin-top:20px">
        <div class="section-title" style="font-size:12px;margin-bottom:10px">Top Organic Keywords (Last 28 Days)</div>
        <div class="page-table-wrap">
        <table>
          <thead><tr>
            <th>Keyword</th>
            <th class="center">Clicks</th>
            <th class="center">Impressions</th>
            <th class="center">Avg. Position</th>
            <th class="center">CTR</th>
          </tr></thead>
          <tbody>
            ${metrics.topKeywords.map(k => `<tr>
              <td>${k.keyword}</td>
              <td class="center">${k.clicks}</td>
              <td class="center">${k.impressions}</td>
              <td class="center">${k.position}</td>
              <td class="center">${k.ctr}</td>
            </tr>`).join('')}
          </tbody>
        </table>
        </div>
      </div>` : '';

    return `
  <div class="section">
    <div class="perf-header">
      <div class="section-title" style="margin-bottom:0;border:none;padding:0">Performance Highlights</div>
      <div class="perf-period">${periodLabel}</div>
    </div>
    <div class="perf-grid">${cards}</div>
    ${keywordsTable}
  </div>`;
  })() : ''}

  <!-- Core Web Vitals is deliberately not in this quick HTML report — see
       runAudit()'s comment above; it only appears in the full Word/strategy
       report, generated separately via webapp/server's /docx/prepare. -->

  <!-- KEYWORD RANKINGS -->
  ${(() => {
    if (!liveGSC || keywordHistory.length === 0) return '';
    const latest = keywordHistory[keywordHistory.length - 1];
    const prev   = keywordHistory.length >= 2 ? keywordHistory[keywordHistory.length - 2] : null;
    const prevMap = prev ? Object.fromEntries(prev.keywords.map(k => [k.keyword, k.positionRaw])) : {};
    const prevDate = prev ? new Date(prev.date + 'T12:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : null;

    const rows = latest.keywords.map(k => {
      const cur  = k.positionRaw;
      const prv  = prevMap[k.keyword];
      let changeHtml = '<span class="kw-change new">New</span>';
      if (prv !== undefined && cur !== null) {
        const diff = Math.round(prv - cur); // positive = improved (moved up)
        if (Math.abs(diff) < 1) {
          changeHtml = `<span class="kw-change same">—</span>`;
        } else if (diff > 0) {
          changeHtml = `<span class="kw-change improved">▲ ${diff}</span>`;
        } else {
          changeHtml = `<span class="kw-change declined">▼ ${Math.abs(diff)}</span>`;
        }
      }
      const posDisplay = cur !== null ? Math.round(cur) : '—';
      return `<tr>
        <td>${k.keyword}</td>
        <td class="center kw-pos">${posDisplay}</td>
        <td class="center">${changeHtml}</td>
        <td class="center">${k.clicks}</td>
        <td class="center">${k.impressions}</td>
        <td class="center">${k.ctr}</td>
      </tr>`;
    }).join('');

    return `
  <div class="section">
    <div class="perf-header">
      <div class="section-title" style="margin-bottom:0;border:none;padding:0">Keyword Rankings</div>
      <div class="perf-period">Last 28 days${prevDate ? ` &nbsp;&bull;&nbsp; vs. ${prevDate}` : ''}</div>
    </div>
    <div class="page-table-wrap" style="margin-top:14px">
    <table>
      <thead><tr>
        <th>Keyword</th>
        <th class="center">Position</th>
        <th class="center">${prevDate ? `vs. ${prevDate}` : 'Change'}</th>
        <th class="center">Clicks</th>
        <th class="center">Impressions</th>
        <th class="center">CTR</th>
      </tr></thead>
      <tbody>${rows}</tbody>
    </table>
    </div>
  </div>`;
  })()}

  <!-- QUICK WINS -->
  ${(() => {
    const wins = getQuickWins(pages, { hasSitemap });
    if (!wins.length) return '';
    return `
  <div class="section">
    <div class="section-title">Quick Wins &amp; Recommendations</div>
    <div class="qw-grid">
      ${wins.map(w => `
      <div class="qw-card">
        <div class="qw-top">
          <span class="qw-badge effort-${w.effort}">${w.effort} effort</span>
          <span class="qw-badge" style="background:#eff6ff;color:#1d4ed8">${w.impact} impact</span>
        </div>
        <div class="qw-action">${w.action}</div>
        <div class="qw-detail" style="margin-top:6px">${w.detail}</div>
      </div>`).join('')}
    </div>
  </div>`;
  })()}

  <!-- WHAT THIS MEANS -->
  <div class="section">
    <div class="section-title">Audit Summary</div>
    <div class="summary-box">
      <div class="summary-row">
        <span class="sr-label">Overall SEO Health Score</span>
        <span class="sr-val ${scoreData.score >= 80 ? 'good' : scoreData.score >= 60 ? 'warn' : 'bad'}">${scoreData.score} / 100 &mdash; ${scoreLabel}</span>
      </div>
      ${noTitlePgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing a title tag</span><span class="sr-val bad">${noTitlePgs.length} page${noTitlePgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${noMetaPgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing a meta description</span><span class="sr-val bad">${noMetaPgs.length} page${noMetaPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${noH1Pgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing an H1 heading</span><span class="sr-val bad">${noH1Pgs.length} page${noH1Pgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${noindexedPgs.length ? `<div class="summary-row"><span class="sr-label">Pages set to noindex (excluded from search)</span><span class="sr-val bad">${noindexedPgs.length} page${noindexedPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${robotsBlockedPgs.length ? `<div class="summary-row"><span class="sr-label">Pages blocked by robots.txt</span><span class="sr-val bad">${robotsBlockedPgs.length} page${robotsBlockedPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${multipleH1Pgs.length ? `<div class="summary-row"><span class="sr-label">Pages with more than one H1 heading</span><span class="sr-val warn">${multipleH1Pgs.length} page${multipleH1Pgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${badTitlePgs.length ? `<div class="summary-row"><span class="sr-label">Pages with a title tag outside the ideal 30&ndash;65 character range</span><span class="sr-val warn">${badTitlePgs.length} page${badTitlePgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${noSchemaPgs.length ? `<div class="summary-row"><span class="sr-label">Pages without structured data (schema)</span><span class="sr-val warn">${noSchemaPgs.length} page${noSchemaPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingAltPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with images missing alt text</span><span class="sr-val warn">${missingAltPgs.length} page${missingAltPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${duplicateTitlePgs.length ? `<div class="summary-row"><span class="sr-label">Pages with a duplicate title tag</span><span class="sr-val warn">${duplicateTitlePgs.length} page${duplicateTitlePgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${duplicateMetaPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with a duplicate meta description</span><span class="sr-val warn">${duplicateMetaPgs.length} page${duplicateMetaPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${duplicateH1Pgs.length ? `<div class="summary-row"><span class="sr-label">Pages with a duplicate H1 tag</span><span class="sr-val warn">${duplicateH1Pgs.length} page${duplicateH1Pgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingViewportPgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing a viewport meta tag</span><span class="sr-val warn">${missingViewportPgs.length} page${missingViewportPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingLangPgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing an html lang attribute</span><span class="sr-val warn">${missingLangPgs.length} page${missingLangPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingCompressionPgs.length ? `<div class="summary-row"><span class="sr-label">Pages served without HTTP compression</span><span class="sr-val warn">${missingCompressionPgs.length} page${missingCompressionPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${mixedContentPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with mixed-content (http://) resources</span><span class="sr-val bad">${mixedContentPgs.length} page${mixedContentPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${brokenLinkPgs.length ? `<div class="summary-row"><span class="sr-label">Pages linking to a broken internal page</span><span class="sr-val bad">${brokenLinkPgs.length} page${brokenLinkPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${invalidSchemaPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with invalid (malformed) schema markup</span><span class="sr-val warn">${invalidSchemaPgs.length} page${invalidSchemaPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${multipleTitleTagsPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with multiple title tags</span><span class="sr-val warn">${multipleTitleTagsPgs.length} page${multipleTitleTagsPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${multipleMetaTagsPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with multiple meta description tags</span><span class="sr-val warn">${multipleMetaTagsPgs.length} page${multipleMetaTagsPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingImageDimensionPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with images missing width/height attributes</span><span class="sr-val warn">${missingImageDimensionPgs.length} page${missingImageDimensionPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingOgTagsPgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing Open Graph tags</span><span class="sr-val warn">${missingOgTagsPgs.length} page${missingOgTagsPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingCharsetPgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing a character encoding declaration</span><span class="sr-val warn">${missingCharsetPgs.length} page${missingCharsetPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingDoctypePgs.length ? `<div class="summary-row"><span class="sr-label">Pages missing a doctype declaration</span><span class="sr-val warn">${missingDoctypePgs.length} page${missingDoctypePgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${orphanPgs.length ? `<div class="summary-row"><span class="sr-label">Orphan pages (no internal links point to them)</span><span class="sr-val warn">${orphanPgs.length} page${orphanPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${skippedHeadingPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with a skipped heading level</span><span class="sr-val warn">${skippedHeadingPgs.length} page${skippedHeadingPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${difficultReadingPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with difficult-to-read copy</span><span class="sr-val warn">${difficultReadingPgs.length} page${difficultReadingPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingLocalBusinessFieldPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with incomplete LocalBusiness schema</span><span class="sr-val warn">${missingLocalBusinessFieldPgs.length} page${missingLocalBusinessFieldPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${inconsistentPhonePgs.length ? `<div class="summary-row"><span class="sr-label">Pages with an inconsistent phone number</span><span class="sr-val warn">${inconsistentPhonePgs.length} page${inconsistentPhonePgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${!hasSitemap ? `<div class="summary-row"><span class="sr-label">Sitemap.xml</span><span class="sr-val warn">Not found &mdash; only nav-linked pages could be discovered</span></div>` : ''}
      <div class="summary-row">
        <span class="sr-label">Pages with improvement opportunities (schema, word count, canonical)</span>
        <span class="sr-val warn">${warningPages.length} of ${pages.length} pages</span>
      </div>
      <div class="summary-row">
        <span class="sr-label">Fully optimized pages (no issues, no warnings)</span>
        <span class="sr-val good">${healthyPages.length} of ${pages.length} pages</span>
      </div>
    </div>
    <p style="font-size:12px;color:#64748b;margin-top:12px;line-height:1.6">
      <strong>What this means:</strong> A score of ${scoreData.score}/100 means your site has no critical SEO problems. Pages listed under "improvement opportunities" have advisory items — adding schema markup, increasing content length, or setting canonical tags — that can help rankings but are not broken elements.
    </p>
  </div>

  <!-- FINDINGS BY PAGE -->
  ${allIssuePages.length ? `
  <div class="section">
    <div class="section-title">Findings by Page (${allIssuePages.length} pages need attention)</div>
    ${allIssuePages.map(findingBlock).join('')}
  </div>` : `
  <div class="section">
    <div class="section-title">Findings</div>
    <div class="summary-box" style="text-align:center;padding:32px;color:#16a34a;font-weight:700;font-size:15px">No critical issues found across all ${pages.length} pages.</div>
  </div>`}

  <!-- FULL PAGE TABLE -->
  <div class="section">
    <div class="section-title">All Pages at a Glance</div>
    <div class="page-table-wrap">
    <table>
      <thead>
        <tr>
          <th></th>
          <th>Page URL</th>
          <th>Title Tag</th>
          <th class="center">H1</th>
          <th class="center">Meta Desc</th>
          <th class="center">Schema</th>
          <th class="center">Image Alts</th>
        </tr>
      </thead>
      <tbody>
        ${pages.map(pageRow).join('')}
      </tbody>
    </table>
    </div>
  </div>

  ${errors.length ? `
  <div class="section">
    <div class="section-title">Pages That Could Not Be Crawled (${errors.length})</div>
    <table>
      <thead><tr><th>URL</th><th>Reason</th></tr></thead>
      <tbody>${errors.map(e => `<tr><td>${e.url}</td><td style="color:#dc2626">${e.error}</td></tr>`).join('')}</tbody>
    </table>
  </div>` : ''}

</div>

<div class="report-footer">
  Prepared by ${provider.name} &bull; ${client.name} SEO Audit &bull; ${date}<br>
  Questions? Contact ${provider.email}
</div>
</body>
</html>`;

    return html;
  }

  // ─── Public entry point: crawl + score + (optionally) live GSC + build report.
  // No prompts by default — provider is a required argument, and if the
  // client has no gsc_property (or the GSC fetch comes back empty), the
  // performance-metrics section falls back to whatever's already saved in
  // metrics.json (or is omitted, same as buildReport already handles
  // metrics === null). See design doc §3.4.
  //
  // onNeedMetrics(outDir), if given, is called at exactly the point the
  // original CLI's interactive promptMetrics() ran (only when there's no
  // live GSC data) and its return value is used instead of metrics.json —
  // this is how audit.js's CLI wrapper keeps its existing prompt-driven
  // metrics entry working unchanged, without forcing that prompt into the
  // web path (which never passes this option).
  async function runAudit({ provider, onProgress, onNeedMetrics, storage, enrichKeywords } = {}) {
    const resolvedProvider = resolveProvider(provider);
    const outDir = client.output_dir || process.cwd();
    const store = storage || fileStorage;

    const { results, hasSitemap, dominantPhone } = await crawl(onProgress);
    annotateDuplicates(results);
    const scoreData = calcScore(results, { hasSitemap });

    const crawledAt = new Date();
    const today = crawledAt.toISOString().split('T')[0];
    const history = await store.loadHistory(client);
    const existingToday = history.find(h => h.date === today);
    if (existingToday) existingToday.score = scoreData.score;
    else history.push({ date: today, score: scoreData.score });
    await store.saveHistory(client, history);

    let gscData = null;
    let keywordHistory = await store.loadKeywordHistory(client);
    if (client.gsc_property) {
      gscData = await getGSCMetrics(client.gsc_property);
      if (gscData && gscData.topKeywords && gscData.topKeywords.length) {
        // enrichKeywords (Phase 2, seo-tool/lib/keyword-planner.js's
        // enrichWithVolumes) adds real search volume to each keyword —
        // optional so this stays a no-op until Keyword Planner OAuth is
        // set up, same pattern as onNeedMetrics/storage above.
        if (enrichKeywords) gscData.topKeywords = await enrichKeywords(gscData.topKeywords);
        await store.saveKeywordHistory(client, today, gscData.topKeywords);
        keywordHistory = await store.loadKeywordHistory(client);
      }
    }
    // onNeedMetrics is a CLI-only concern (audit.js's interactive prompt) —
    // always file-based (metrics.json in outDir) regardless of which storage
    // adapter is in use, since it's the CLI wrapper's own fallback, not part
    // of the storage abstraction.
    const metrics = gscData || (onNeedMetrics ? await onNeedMetrics(outDir) : await store.loadMetrics(client));

    // Core Web Vitals (getCoreWebVitals, page-speed.js) is deliberately NOT
    // fetched here — a live PSI "mobile" run has been observed taking 90s+,
    // and this route needs to stay fast since it's the common-case audit
    // path (weekly cron across every client, plus every manual re-run).
    // It's generated later, same lifecycle as the narrative LLM section
    // (see webapp/server's /docx/prepare), and only shown in the full
    // Word/strategy report, not this quick HTML one.
    const html = buildReport(results, scoreData, history, metrics, !!gscData, keywordHistory, resolvedProvider, hasSitemap);

    // crawledAt is the full timestamp this specific run finished (not just
    // the date) — kept separate from `date`, which stays a plain
    // YYYY-MM-DD string since that's what history/keyword-history storage
    // key on and what the docx filename uses. Report display prefers the
    // exact time so it's clear which of possibly several same-day runs a
    // given report reflects.
    return { results, scoreData, html, metrics, liveGSC: !!gscData, keywordHistory, date: today, crawledAt: crawledAt.toISOString(), outDir, provider: resolvedProvider, dominantPhone };
  }

  return { runAudit };
}

module.exports = {
  PROVIDERS,
  runAudit: (client, opts) => createEngine(client).runAudit(opts),
  // Exported for reuse by build-docx-report.js — same findings data the
  // HTML report's Quick Wins and Findings by Page sections are built from.
  getQuickWins,
  friendlyIssue,
  // Exported so the Google Business Profile comparison (server-side, see
  // local-seo.js) normalizes/formats phone numbers the exact same way the
  // on-site NAP-consistency check above does.
  normalizePhone,
  formatPhone,
};
