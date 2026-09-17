// Core Web Vitals via Google's PageSpeed Insights API — the one purely
// technical/performance gap our own crawl can't see at all, since
// analyzePage() (audit-engine.js) only ever reads static HTML, it never
// renders or times a page the way a browser would.
//
// Deliberately scoped to the homepage only, not every crawled page: a real
// PSI/Lighthouse run takes 5-15 seconds per URL, so running it against
// every page of a 60-page site on every weekly audit would make audits
// impractically slow for very little extra signal — Core Web Vitals are
// usually a site-wide template/hosting problem, not a page-by-page one,
// and Google's own real-world (CrUX) data is commonly reported at the
// origin level for smaller sites that don't get enough per-URL traffic to
// report individually anyway (exactly the case for our client base).
//
// Never throws — same graceful-degradation pattern as gsc.js and
// keyword-planner.js: no API key configured, a network failure, or a
// malformed response all just degrade to `null`, and the rest of the
// audit/report already handles a missing performance section fine.
const fetch = require('node-fetch');

const API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
const TIMEOUT_MS = 30000;

// Field-level thresholds Google itself uses to label a metric "good" vs
// "needs improvement" vs "poor" — https://web.dev/articles/defining-core-web-vitals-thresholds
const THRESHOLDS = {
  lcp: { good: 2500, needsImprovement: 4000 },  // ms
  cls: { good: 0.1, needsImprovement: 0.25 },   // unitless
  inp: { good: 200, needsImprovement: 500 },    // ms
};

function rate(value, thresholds) {
  if (value == null) return null;
  if (value <= thresholds.good) return 'good';
  if (value <= thresholds.needsImprovement) return 'needs-improvement';
  return 'poor';
}

// strategy: 'mobile' (default — Google is mobile-first for ranking) or 'desktop'.
async function getCoreWebVitals(url, strategy = 'mobile') {
  const apiKey = process.env.PAGESPEED_API_KEY;
  if (!apiKey) {
    console.warn('Core Web Vitals check skipped: PAGESPEED_API_KEY is not set.');
    return null;
  }

  try {
    const params = new URLSearchParams({ url, strategy, category: 'PERFORMANCE', key: apiKey });
    const res = await fetch(`${API_URL}?${params}`, { timeout: TIMEOUT_MS });
    if (!res.ok) {
      const body = await res.text().catch(() => '');
      throw new Error(`PageSpeed API returned ${res.status}: ${body.slice(0, 300)}`);
    }
    const data = await res.json();

    const perfScore = data.lighthouseResult?.categories?.performance?.score;
    const audits = data.lighthouseResult?.audits || {};
    const lcp = audits['largest-contentful-paint']?.numericValue ?? null;
    const cls = audits['cumulative-layout-shift']?.numericValue ?? null;
    const tbt = audits['total-blocking-time']?.numericValue ?? null;
    const fcp = audits['first-contentful-paint']?.numericValue ?? null;
    const speedIndex = audits['speed-index']?.numericValue ?? null;
    // Field data (real Chrome User Experience Report traffic) is the only
    // source for INP — lab runs like the audits above can't produce it
    // since that requires real user interactions. Only present once Google
    // has enough real-world traffic for this exact URL to report on.
    const inp = data.loadingExperience?.metrics?.INTERACTION_TO_NEXT_PAINT?.percentile ?? null;

    return {
      strategy,
      performanceScore: perfScore != null ? Math.round(perfScore * 100) : null,
      lcp: { value: lcp, rating: rate(lcp, THRESHOLDS.lcp) },
      cls: { value: cls, rating: rate(cls, THRESHOLDS.cls) },
      inp: inp != null ? { value: inp, rating: rate(inp, THRESHOLDS.inp) } : null,
      tbt: { value: tbt },
      fcp: { value: fcp },
      speedIndex: { value: speedIndex },
    };
  } catch (err) {
    console.warn(`Core Web Vitals check failed for ${url}: ${err.message}`);
    return null;
  }
}

module.exports = { getCoreWebVitals, THRESHOLDS, rate };
