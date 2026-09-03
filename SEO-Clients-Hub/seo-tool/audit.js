const fetch = require('node-fetch');
const cheerio = require('cheerio');
const fs = require('fs');
const path = require('path');
const readline = require('readline');
const { getGSCMetrics } = require('./gsc');

// ─── Config ───────────────────────────────────────────────────────────────────

const clientName = process.argv[2];
if (!clientName) {
  console.error('Usage: node audit.js <client>   e.g.  node audit.js fruth');
  process.exit(1);
}

const configPath = path.join(__dirname, 'clients', `${clientName}.json`);
if (!fs.existsSync(configPath)) {
  console.error(`No config found at ${configPath}`);
  process.exit(1);
}

const client = JSON.parse(fs.readFileSync(configPath, 'utf8'));
const BASE_URL = client.url.replace(/\/$/, '');
const MAX_PAGES = client.max_pages || 50;
const IGNORE = client.ignore_paths || [];

// ─── Crawler ──────────────────────────────────────────────────────────────────

const visited = new Set();
const queue = [BASE_URL + '/'];
const results = [];

const IGNORE_EXTENSIONS = /\.(jpg|jpeg|png|gif|webp|svg|pdf|zip|doc|docx|xls|xlsx|mp4|mp3|css|js|woff|woff2|ttf)(\?|$)/i;

function shouldIgnore(url) {
  if (IGNORE_EXTENSIONS.test(url)) return true;
  return IGNORE.some(p => url.includes(p));
}

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

const BROWSER_HEADERS = {
  'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
  'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
  'Accept-Language': 'en-US,en;q=0.9',
  'Accept-Encoding': 'gzip, deflate, br',
  'Cache-Control': 'no-cache',
};

async function fetchPage(url) {
  try {
    const res = await fetch(url, { headers: BROWSER_HEADERS, timeout: 12000, redirect: 'follow' });
    if (!res.ok) return { url, error: `HTTP ${res.status}` };
    const html = await res.text();
    return { url, html, status: res.status };
  } catch (e) {
    return { url, error: e.message };
  }
}

function analyzePage(url, html) {
  const $ = cheerio.load(html);
  const issues = [];
  const warnings = [];

  // Title
  const title = $('title').first().text().trim();
  const titleLen = title.length;
  if (!title) issues.push('Missing title tag');
  else if (titleLen < 30) warnings.push(`Title too short (${titleLen} chars)`);
  else if (titleLen > 65) warnings.push(`Title too long (${titleLen} chars, aim for 50-65)`);

  // Meta description
  const metaDesc = $('meta[name="description"]').attr('content') || '';
  const metaLen = metaDesc.trim().length;
  if (!metaDesc.trim()) issues.push('Missing meta description');
  else if (metaLen < 100) warnings.push(`Meta description short (${metaLen} chars)`);
  else if (metaLen > 165) warnings.push(`Meta description long (${metaLen} chars, aim for 140-160)`);

  // H1
  const h1s = $('h1').map((i, el) => $(el).text().trim()).get();
  if (h1s.length === 0) issues.push('No H1 tag found');
  else if (h1s.length > 1) warnings.push(`Multiple H1 tags (${h1s.length})`);

  // H2s
  const h2s = $('h2').map((i, el) => $(el).text().trim()).get();

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

  // Canonical
  const canonical = $('link[rel="canonical"]').attr('href') || '';
  if (!canonical) warnings.push('No canonical tag');

  // Schema / JSON-LD
  const schemas = $('script[type="application/ld+json"]').toArray();
  const schemaTypes = schemas.map(s => {
    try {
      const parsed = JSON.parse($(s).html());
      return parsed['@type'] || 'Unknown';
    } catch { return 'Invalid JSON-LD'; }
  });
  if (schemas.length === 0) issues.push('No JSON-LD schema found');

  // Word count (body text only)
  $('script, style, nav, footer, header').remove();
  const bodyText = $('body').text().replace(/\s+/g, ' ').trim();
  const wordCount = bodyText.split(' ').filter(w => w.length > 1).length;
  if (wordCount < 150) warnings.push(`Low word count (${wordCount} words)`);

  // Internal links
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
    wordCount,
    internalLinks,
    issues,
    warnings,
    links
  };
}

// ─── Sitemap seed ─────────────────────────────────────────────────────────────

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

async function seedFromSitemap() {
  const sitemapUrl = `${BASE_URL}/sitemap.xml`;
  try {
    const locs = await parseSitemap(sitemapUrl);
    if (!locs.length) { console.log(`  (sitemap not found, crawling by links only)`); return; }
    let added = 0;
    for (const loc of locs) {
      const norm = normalizeUrl(loc, BASE_URL);
      if (norm && norm.startsWith(BASE_URL) && !shouldIgnore(norm) && !visited.has(norm)) {
        queue.push(norm);
        added++;
      }
    }
    console.log(`  Sitemap: found ${locs.length} URLs, queued ${added} new pages`);
  } catch (e) {
    console.log(`  (sitemap fetch failed: ${e.message})`);
  }
}

// ─── Crawl ────────────────────────────────────────────────────────────────────

async function crawl() {
  console.log(`\nAuditing: ${client.name} (${BASE_URL})`);
  console.log(`Max pages: ${MAX_PAGES}\n`);

  await seedFromSitemap();

  let count = 0;
  while (queue.length > 0 && count < MAX_PAGES) {
    const url = queue.shift();
    const normalized = normalizeUrl(url, BASE_URL) || url;
    if (visited.has(normalized)) continue;
    visited.add(normalized);

    process.stdout.write(`[${count + 1}] ${normalized.replace(BASE_URL, '')} ... `);

    const { html, error, status } = await fetchPage(normalized);
    if (error) {
      console.log(`ERROR: ${error}`);
      results.push({ url: normalized, error });
      count++;
      continue;
    }

    const data = analyzePage(normalized, html);
    results.push(data);

    const issueCount = data.issues.length;
    const warnCount = data.warnings.length;
    const flag = issueCount > 0 ? '!' : (warnCount > 0 ? '~' : 'OK');
    console.log(`${flag}  (${issueCount} issues, ${warnCount} warnings, ${data.wordCount} words)`);

    // Queue new links
    for (const link of data.links) {
      if (!visited.has(link)) queue.push(link);
    }

    count++;
    await new Promise(r => setTimeout(r, 300)); // polite crawl
  }

  console.log(`\nCrawled ${results.length} pages.`);
}

// ─── Score ────────────────────────────────────────────────────────────────────

function calcScore(results) {
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

  // Advisory warning deduction: pages with warnings but no critical issues (-0.5 each, max -15)
  const warningOnlyPages = pages.filter(p => p.warnings.length > 0 && p.issues.length === 0).length;
  if (warningOnlyPages) {
    const warnPts = Math.min(15, Math.round(warningOnlyPages * 0.5));
    deduct(warnPts, `${warningOnlyPages} page(s) with improvement opportunities`);
  }

  return { score: Math.max(0, score), deductions: docked };
}

// ─── Friendly label maps ──────────────────────────────────────────────────────

const ISSUE_LABELS = {
  'Missing title tag': { label: 'Page is missing a title tag', why: 'Google uses the title tag as the clickable headline in search results. Without one, Google will generate its own — often poorly.', priority: 'critical' },
  'No H1 tag found': { label: 'Page has no main heading (H1)', why: 'The H1 tells search engines what the page is about. Every page should have exactly one.', priority: 'critical' },
  'Missing meta description': { label: 'Missing meta description', why: 'Meta descriptions appear as the summary text in search results. Missing ones lead to lower click-through rates.', priority: 'high' },
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
  return { label: text, why: '', priority: 'low' };
}

// ─── HTML Report ──────────────────────────────────────────────────────────────

function buildReport(results, scoreData, history = [], metrics = null, liveGSC = false, keywordHistory = []) {
  const pages = results.filter(r => !r.error);
  const errors = results.filter(r => r.error);
  const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const brand = client.brand_color || '#003366';

  const scoreColor = scoreData.score >= 80 ? '#22c55e' : scoreData.score >= 60 ? '#f59e0b' : '#ef4444';
  const scoreLabel = scoreData.score >= 85 ? 'Good' : scoreData.score >= 70 ? 'Needs Improvement' : 'Needs Attention';

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
    const allItems = [
      ...p.issues.map(i => ({ text: i, type: 'issue' })),
      ...p.warnings.map(w => ({ text: w, type: 'warn' }))
    ];
    return `
    <tr>
      <td class="status-cell ${status}">${statusIcon}</td>
      <td class="url-cell"><a href="${p.url}" target="_blank">${slug || '/'}</a></td>
      <td>${p.title || '<span class="missing">Missing</span>'}</td>
      <td class="center">${p.h1Count === 0 ? '<span class="missing">None</span>' : p.h1Count > 1 ? `<span class="warn-text">${p.h1Count}</span>` : '&#10003;'}</td>
      <td class="center">${p.metaDesc ? '&#10003;' : '<span class="missing">Missing</span>'}</td>
      <td class="center">${p.schemaTypes.length ? p.schemaTypes.join(', ') : '<span class="warn-text">None</span>'}</td>
      <td class="center">${p.imagesNoAlt > 0 ? `<span class="missing">${p.imagesNoAlt}</span>` : '&#10003;'}</td>
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
  .cover { background: ${brand}; color: white; min-height: 220px; padding: 48px 56px 44px; display: flex; flex-direction: column; page-break-after: always; }
  .cover-header { display: flex; justify-content: space-between; align-items: flex-start; }
  .cover-agency { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; opacity: .7; }
  .cover-score-box { background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.4); border-radius: 12px; padding: 14px 22px; text-align: center; flex-shrink: 0; }
  .cover-score-num { font-size: 40px; font-weight: 800; line-height: 1; }
  .cover-score-label { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; opacity: .8; margin-top: 2px; }
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
  .qw-card { background: white; border-radius: 10px; padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-top: 3px solid #003366; }
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
  .perf-card { background: white; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-bottom: 3px solid #003366; }
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
    td a { color: #003366 !important; font-size: 10px; }

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
    <div class="cover-agency">Start Advertising &bull; SEO Audit</div>
    <div class="cover-score-box">
      <div class="cover-score-num">${scoreData.score}</div>
      <div class="cover-score-label">SEO Health Score</div>
      <div style="font-size:11px;margin-top:4px;opacity:.9">${scoreLabel}</div>
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
  <div class="cover-note"><strong>Score note:</strong> The SEO Health Score deducts heavily for critical issues (missing titles, H1s, meta descriptions, image alt text) and applies a smaller deduction for pages with improvement opportunities such as schema markup, canonical tags, and content depth.</div>
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
    const wins = getQuickWins(pages);
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
      ${noSchemaPgs.length ? `<div class="summary-row"><span class="sr-label">Pages without structured data (schema)</span><span class="sr-val warn">${noSchemaPgs.length} page${noSchemaPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
      ${missingAltPgs.length ? `<div class="summary-row"><span class="sr-label">Pages with images missing alt text</span><span class="sr-val warn">${missingAltPgs.length} page${missingAltPgs.length > 1 ? 's' : ''} affected</span></div>` : ''}
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
  Prepared by Start Advertising &bull; ${client.name} SEO Audit &bull; ${date}<br>
  Questions? Contact richard@grouprb.com
</div>
</body>
</html>`;

  return html;
}

// ─── Score history ────────────────────────────────────────────────────────────

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

// ─── Quick wins ───────────────────────────────────────────────────────────────

function getQuickWins(pages) {
  const wins = [];

  // Count each warning type across all pages
  const warnCounts = {};
  for (const p of pages) {
    for (const w of p.warnings) {
      const key = w.replace(/\(\d+.*?\)/g, '').replace(/\d+ /g, 'N ').trim();
      warnCounts[key] = (warnCounts[key] || { count: 0, raw: w });
      warnCounts[key].count++;
    }
  }

  const noCanonical = pages.filter(p => !p.canonical).length;
  const noSchema = pages.filter(p => p.schemaTypes.length === 0).length;
  const shortTitle = pages.filter(p => p.title && p.titleLen < 30).length;
  const longTitle = pages.filter(p => p.title && p.titleLen > 65).length;
  const shortMeta = pages.filter(p => p.metaDesc && p.metaLen < 100).length;
  const longMeta = pages.filter(p => p.metaDesc && p.metaLen > 165).length;
  const emptyAlt = pages.filter(p => p.imagesEmptyAlt > 0).length;
  const lowWord = pages.filter(p => p.wordCount < 150).length;
  const multiH1 = pages.filter(p => p.h1Count > 1).length;

  if (noCanonical) wins.push({ effort: 'Low', impact: 'Medium', pages: noCanonical, action: `Add canonical tags to ${noCanonical} page${noCanonical > 1 ? 's' : ''}`, detail: 'Canonical tags are a single line of code. They tell Google which version of a page to index and prevent duplicate content penalties.' });
  if (emptyAlt) wins.push({ effort: 'Low', impact: 'Medium', pages: emptyAlt, action: `Fill in empty image alt text on ${emptyAlt} page${emptyAlt > 1 ? 's' : ''}`, detail: 'Alt text is already in the code but blank. Adding keyword-relevant descriptions takes minutes and improves both accessibility and image search rankings.' });
  if (shortTitle || longTitle) { const n = shortTitle + longTitle; wins.push({ effort: 'Low', impact: 'High', pages: n, action: `Optimize title tag length on ${n} page${n > 1 ? 's' : ''}`, detail: `${shortTitle ? shortTitle + ' titles are under 30 characters (missing keyword opportunities). ' : ''}${longTitle ? longTitle + ' titles exceed 65 characters and will be cut off in search results.' : ''}` }); }
  if (shortMeta || longMeta) { const n = shortMeta + longMeta; wins.push({ effort: 'Low', impact: 'Medium', pages: n, action: `Rewrite meta descriptions on ${n} page${n > 1 ? 's' : ''}`, detail: `${shortMeta ? shortMeta + ' meta descriptions are too short to persuade clicks. ' : ''}${longMeta ? longMeta + ' are too long and get truncated in search results.' : ''}` }); }
  if (multiH1) wins.push({ effort: 'Low', impact: 'Medium', pages: multiH1, action: `Fix multiple H1 tags on ${multiH1} page${multiH1 > 1 ? 's' : ''}`, detail: 'Each page should have exactly one H1. Multiple H1s dilute the main topic signal Google uses to understand the page.' });
  if (noSchema) wins.push({ effort: 'Medium', impact: 'High', pages: noSchema, action: `Add structured data (schema) to ${noSchema} page${noSchema > 1 ? 's' : ''}`, detail: 'Schema markup helps Google display rich results (star ratings, FAQs, product details). Product and service pages especially benefit from this.' });
  if (lowWord) wins.push({ effort: 'High', impact: 'High', pages: lowWord, action: `Expand thin content on ${lowWord} page${lowWord > 1 ? 's' : ''}`, detail: 'Pages under 150 words give Google very little to rank. Adding descriptive copy, FAQs, or specifications strengthens these pages significantly.' });

  // Sort: low effort first, then by pages affected
  const effortOrder = { Low: 0, Medium: 1, High: 2 };
  wins.sort((a, b) => effortOrder[a.effort] - effortOrder[b.effort] || b.pages - a.pages);

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

// ─── Metrics prompt ───────────────────────────────────────────────────────────

async function promptMetrics(outDir) {
  const existing = loadMetrics(outDir);

  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  const ask = q => new Promise(res => rl.question(q, res));

  console.log('\n─── Performance Metrics ──────────────────────────────────────────');
  if (existing) {
    console.log(`  Last saved: ${existing.snapshot_date || 'unknown date'}`);
    existing.metrics.forEach(m => console.log(`  ${m.label}: ${m.value} (${m.change})`));
  }
  console.log('  Press Enter to keep existing data, or type new values.');

  const yn = (await ask('\n  Do you have updated metrics to enter? (y/N): ')).trim().toLowerCase();
  if (yn !== 'y') {
    rl.close();
    console.log(existing ? '  Keeping existing metrics.' : '  No metrics — section will be skipped.');
    return existing;
  }

  console.log('  (Press Enter to skip any field)\n');

  const snapDate = (await ask('  Snapshot date (e.g. Aug 11, 2026): ')).trim();
  const compLabel = (await ask('  Comparison label (e.g. vs. May 27): ')).trim() || 'vs. prior period';

  async function askMetric(label) {
    const value  = (await ask(`  ${label} — value: `)).trim();
    if (!value) return null;
    const change = (await ask(`  ${label} — change (e.g. +12.5%): `)).trim();
    const dir    = change.startsWith('-') ? 'down' : 'up';
    return { label, value, change, direction: dir };
  }

  const fields = [
    'Organic Clicks',
    'Organic Keywords',
    'Avg. Position',
    'Impressions',
  ];

  const metricsOut = [];
  for (const f of fields) {
    const m = await askMetric(f);
    if (m) metricsOut.push(m);
  }

  rl.close();

  if (!metricsOut.length) {
    console.log('  No values entered — keeping existing metrics.');
    return existing;
  }

  const saved = {
    snapshot_date: snapDate || new Date().toISOString().split('T')[0],
    comparison_label: compLabel,
    metrics: metricsOut
  };

  const metricsPath = path.join(outDir, 'metrics.json');
  fs.writeFileSync(metricsPath, JSON.stringify(saved, null, 2), 'utf8');
  console.log('  Metrics saved.\n');
  return saved;
}

// ─── Main ─────────────────────────────────────────────────────────────────────

(async () => {
  await crawl();

  const scoreData = calcScore(results);
  const outDir = client.output_dir || __dirname;

  // Load history, append today if score changed or not yet recorded today
  const today = new Date().toISOString().split('T')[0];
  const history = loadHistory(outDir);
  const alreadyToday = history.some(h => h.date === today);
  if (!alreadyToday) {
    history.push({ date: today, score: scoreData.score });
    saveHistory(outDir, history);
  } else {
    // Update today's score if re-running same day
    const entry = history.find(h => h.date === today);
    if (entry) entry.score = scoreData.score;
    saveHistory(outDir, history);
  }

  console.log(`\nSEO Health Score: ${scoreData.score}/100`);
  if (scoreData.deductions.length) {
    scoreData.deductions.forEach(d => console.log(`  -${d.pts}  ${d.label}`));
  }

  // Try live GSC first, fall back to prompt
  let gscData = null;
  let keywordHistory = loadKeywordHistory(outDir);
  if (client.gsc_property) {
    process.stdout.write('\n  Fetching GSC data... ');
    gscData = await getGSCMetrics(client.gsc_property);
    console.log(gscData ? 'done' : 'no data returned');
    if (gscData && gscData.topKeywords && gscData.topKeywords.length) {
      saveKeywordHistory(outDir, today, gscData.topKeywords);
      keywordHistory = loadKeywordHistory(outDir);
    }
  }
  const metrics = gscData || await promptMetrics(outDir);
  const html = buildReport(results, scoreData, history, metrics, !!gscData, keywordHistory);

  const fileName = `${client.name.replace(/\s+/g, '-')}-SEO-Audit-${today}.html`;
  const outPath = path.join(outDir, fileName);

  fs.writeFileSync(outPath, html, 'utf8');
  console.log(`\nReport saved to:\n${outPath}\n`);
})();
