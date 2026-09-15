// Adds real search volume to the top-organic-keywords data GSC already
// surfaces — deliberately NOT a standalone keyword research tool (that
// scope was explicitly deferred, see PHASE2-DESIGN.md §3.1/§7).
//
// API shape below was read directly out of the installed `google-ads-api`
// package's own generated type definitions (node_modules/google-ads-node/
// build/protos/protos.d.ts — IGenerateKeywordHistoricalMetricsRequest/
// Response), not guessed — but this has NOT been exercised against the
// real Google Ads API yet, pending running ads-auth.js locally (§3.2) to
// produce ads-token.json. Treat as code-reviewed, not live-tested, until
// that's done and this has actually been run once.
const fs = require('fs');
const path = require('path');

const OAUTH_CLIENT_PATH = path.join(__dirname, '..', 'gsc-oauth-client.json'); // same Cloud project/OAuth client as GSC, extended to the adwords scope
const TOKEN_PATH = path.join(__dirname, '..', 'ads-token.json'); // separate token file — see ads-auth.js

// Sensible defaults for a US-based agency's clients — override via env if
// a client's market differs. geoTargetConstants/2840 = United States,
// languageConstants/1000 = English.
const LANGUAGE = process.env.GOOGLE_ADS_LANGUAGE || 'languageConstants/1000';
const GEO_TARGETS = (process.env.GOOGLE_ADS_GEO_TARGETS || 'geoTargetConstants/2840').split(',');

function getCustomerId() {
  return (process.env.GOOGLE_ADS_CUSTOMER_ID || '1697201017').replace(/-/g, '');
}

function getCustomer() {
  if (!fs.existsSync(OAUTH_CLIENT_PATH)) {
    throw new Error(`Missing ${OAUTH_CLIENT_PATH} — same OAuth client file gsc-auth.js uses.`);
  }
  if (!fs.existsSync(TOKEN_PATH)) {
    throw new Error(`Missing ${TOKEN_PATH} — run ads-auth.js first to authorize the adwords scope.`);
  }

  const { GoogleAdsApi } = require('google-ads-api');
  const { client_id, client_secret } = JSON.parse(fs.readFileSync(OAUTH_CLIENT_PATH, 'utf8')).installed;
  const { refresh_token } = JSON.parse(fs.readFileSync(TOKEN_PATH, 'utf8'));

  // Google sunset developer tokens on 2026-09-09 — API access is now tied to
  // the Cloud project that owns these OAuth credentials (Explorer access was
  // approved for "GroupRB SEO Software Project" this same session), not a
  // token value. The google-ads-api client still requires a non-empty string
  // for this field (it's sent as a request header), but the API server
  // ignores it now, so any placeholder satisfies both.
  const developerToken = process.env.GOOGLE_ADS_DEVELOPER_TOKEN || 'unused-post-sunset-placeholder';

  const client = new GoogleAdsApi({ client_id, client_secret, developer_token: developerToken });
  const customerId = getCustomerId();
  return client.Customer({ customer_id: customerId, login_customer_id: customerId, refresh_token });
}

// Returns { [keyword]: avgMonthlySearches | null }. Never throws — a
// lookup failure (credentials not set up yet, quota, API error) degrades
// to an empty map, same as GSC data already degrades to `metrics: null`
// when unavailable, rather than failing the whole crawl.
async function getSearchVolumes(keywords) {
  if (!keywords || !keywords.length) return {};
  try {
    const customer = getCustomer();
    const response = await customer.keywordPlanIdeas.generateKeywordHistoricalMetrics({
      customer_id: getCustomerId(),
      keywords: keywords.slice(0, 20), // keep requests modest — well within Explorer tier's daily quota either way
      language: LANGUAGE,
      geo_target_constants: GEO_TARGETS,
    });
    const volumes = {};
    for (const result of response.results || []) {
      volumes[result.text] = result.keyword_metrics ? (result.keyword_metrics.avg_monthly_searches ?? null) : null;
    }
    return volumes;
  } catch (err) {
    // google-ads-api errors often carry the real detail in `.errors`
    // (per-failure error_code/message) rather than a top-level `.message`,
    // so log whichever is actually populated.
    const detail = (err && err.errors) ? JSON.stringify(err.errors) : (err && (err.stack || err.message)) || err;
    console.warn('Keyword Planner lookup failed (continuing without volume data):', detail);
    return {};
  }
}

// Convenience wrapper matching the shape audit-engine.js's runAudit()
// expects for its optional `enrichKeywords` hook — takes GSC's
// topKeywords array, returns the same array with a `volume` field added.
async function enrichWithVolumes(topKeywords) {
  const volumes = await getSearchVolumes(topKeywords.map(k => k.keyword));
  return topKeywords.map(k => ({ ...k, volume: volumes[k.keyword] ?? null }));
}

module.exports = { getSearchVolumes, enrichWithVolumes };
