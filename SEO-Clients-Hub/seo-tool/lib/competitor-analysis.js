// Lightweight, single-page competitor summaries for the narrative report's
// competitive-analysis section — deliberately NOT a full audit (no scoring,
// no multi-page crawl, no issue/warning detection). The client's own site
// already gets that full treatment; a competitor only needs enough
// structural facts + a content sample for the LLM to compare against, not
// its own SEO health score. Same graceful-degradation pattern as
// page-speed.js and gsc.js — a failed fetch for one competitor never
// blocks the others or the rest of the report.
const fetch = require('node-fetch');
const cheerio = require('cheerio');

const BROWSER_HEADERS = { 'User-Agent': 'Mozilla/5.0 (compatible; SEO-Platform-Audit/1.0)' };
const TIMEOUT_MS = 12000;
const MAX_CONTENT_SAMPLE_CHARS = 1500;

async function fetchCompetitorSummary(url) {
  try {
    const res = await fetch(url, { headers: BROWSER_HEADERS, timeout: TIMEOUT_MS, redirect: 'follow' });
    if (!res.ok) {
      console.warn(`Competitor fetch failed for ${url}: HTTP ${res.status}`);
      return null;
    }
    const html = await res.text();
    const $ = cheerio.load(html);

    const title = $('title').first().text().trim();
    const metaDesc = ($('meta[name="description"]').attr('content') || '').trim();
    const h1 = $('h1').first().text().trim();
    const schemas = $('script[type="application/ld+json"]').toArray();
    const schemaTypes = schemas.flatMap(s => {
      try {
        const parsed = JSON.parse($(s).html());
        const items = Array.isArray(parsed['@graph']) ? parsed['@graph'] : [parsed];
        return items.map(it => it && it['@type']).filter(Boolean).flatMap(t => Array.isArray(t) ? t : [t]);
      } catch { return []; }
    });

    $('script, style, nav, footer, header').remove();
    const bodyText = $('body').text().replace(/\s+/g, ' ').trim();
    const wordCount = bodyText.split(' ').filter(w => w.length > 1).length;

    return {
      url,
      title: title || null,
      metaDesc: metaDesc || null,
      h1: h1 || null,
      wordCount,
      schemaTypes: [...new Set(schemaTypes)],
      contentSample: bodyText.slice(0, MAX_CONTENT_SAMPLE_CHARS),
    };
  } catch (err) {
    console.warn(`Competitor analysis failed for ${url}: ${err.message}`);
    return null;
  }
}

// Fetches all competitors concurrently — each is an independent, already-
// fault-tolerant single request, so there's no reason to serialize them.
async function fetchCompetitorSummaries(urls) {
  if (!urls || !urls.length) return [];
  const results = await Promise.all(urls.slice(0, 3).map(fetchCompetitorSummary));
  return results.filter(Boolean);
}

module.exports = { fetchCompetitorSummary, fetchCompetitorSummaries };
