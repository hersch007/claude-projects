const { google } = require('googleapis');
const path = require('path');
const fs = require('fs');

const SCOPES = ['https://www.googleapis.com/auth/webmasters.readonly'];
const OAUTH_CLIENT_PATH = path.join(__dirname, 'gsc-oauth-client.json');
const TOKEN_PATH        = path.join(__dirname, 'gsc-token.json');
const SA_CREDS_PATH     = path.join(__dirname, 'gsc-credentials.json');

function getAuth() {
  // Prefer OAuth token (user account) — works with any GSC property you have access to
  if (fs.existsSync(OAUTH_CLIENT_PATH) && fs.existsSync(TOKEN_PATH)) {
    try {
      const { client_id, client_secret } = JSON.parse(fs.readFileSync(OAUTH_CLIENT_PATH, 'utf8')).installed;
      const tokens = JSON.parse(fs.readFileSync(TOKEN_PATH, 'utf8'));
      const client = new google.auth.OAuth2(client_id, client_secret, 'http://localhost:3456');
      client.setCredentials(tokens);
      return client;
    } catch { /* fall through */ }
  }

  // Fall back to service account if available
  if (fs.existsSync(SA_CREDS_PATH)) {
    try {
      const creds = JSON.parse(fs.readFileSync(SA_CREDS_PATH, 'utf8'));
      return new google.auth.GoogleAuth({ credentials: creds, scopes: SCOPES });
    } catch { /* fall through */ }
  }

  return null;
}

// Returns clicks/impressions/position for a date range
async function queryGSC(auth, siteUrl, startDate, endDate, dimensions = ['query']) {
  const sc = google.searchconsole({ version: 'v1', auth });
  const res = await sc.searchanalytics.query({
    siteUrl,
    requestBody: {
      startDate,
      endDate,
      dimensions,
      rowLimit: 1000
    }
  });
  return res.data.rows || [];
}

// Format a date as YYYY-MM-DD, offset by N days from today
function offsetDate(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().split('T')[0];
}

// Main function: pull 28-day performance vs prior 28-day period
async function getGSCMetrics(siteUrl) {
  const auth = getAuth();
  if (!auth) return null;

  try {
    // Current period: last 28 days (excluding today — GSC has ~2 day lag)
    const curEnd   = offsetDate(-3);
    const curStart = offsetDate(-30);
    // Prior period: 28 days before that
    const prevEnd   = offsetDate(-31);
    const prevStart = offsetDate(-58);

    const [curRows, prevRows] = await Promise.all([
      queryGSC(auth, siteUrl, curStart, curEnd, ['date']),
      queryGSC(auth, siteUrl, prevStart, prevEnd, ['date'])
    ]);

    const sum = rows => rows.reduce((acc, r) => ({
      clicks: acc.clicks + (r.clicks || 0),
      impressions: acc.impressions + (r.impressions || 0),
      position: acc.position + (r.position || 0),
      count: acc.count + 1
    }), { clicks: 0, impressions: 0, position: 0, count: 0 });

    const cur  = sum(curRows);
    const prev = sum(prevRows);

    const pct = (a, b) => b === 0 ? null : Math.round(((a - b) / b) * 1000) / 10;
    const fmt  = (n, decimals = 0) => n.toLocaleString('en-US', { maximumFractionDigits: decimals });
    const arrow = n => n >= 0 ? 'up' : 'down';
    const sign  = n => n >= 0 ? `+${n}%` : `${n}%`;

    const avgPosCur  = cur.count  ? cur.position  / cur.count  : 0;
    const avgPosPrev = prev.count ? prev.position / prev.count : 0;
    // For position: lower is better, so invert direction
    const posChange = pct(avgPosCur, avgPosPrev);

    // Also pull top keywords for this period
    const keywordRows = await queryGSC(auth, siteUrl, curStart, curEnd, ['query']);
    const topKeywords = keywordRows
      .sort((a, b) => (b.clicks || 0) - (a.clicks || 0))
      .slice(0, 25)
      .map(r => ({
        keyword: r.keys[0],
        clicks: r.clicks || 0,
        impressions: r.impressions || 0,
        position: r.position ? Math.round(r.position) : null,
        positionRaw: r.position || null,
        ctr: r.ctr ? Math.round(r.ctr * 1000) / 10 + '%' : '-'
      }));

    return {
      period: `${curStart} to ${curEnd}`,
      comparisonLabel: `vs. prior 28 days`,
      metrics: [
        {
          label: 'Organic Clicks',
          value: fmt(cur.clicks),
          change: posChange !== null ? sign(pct(cur.clicks, prev.clicks)) : 'N/A',
          direction: arrow(pct(cur.clicks, prev.clicks) || 0)
        },
        {
          label: 'Impressions',
          value: fmt(cur.impressions),
          change: sign(pct(cur.impressions, prev.impressions) || 0),
          direction: arrow(pct(cur.impressions, prev.impressions) || 0)
        },
        {
          label: 'Avg. Position',
          value: fmt(avgPosCur, 0),
          // Lower position number = better ranking, so invert
          change: posChange !== null ? (posChange <= 0 ? `+${Math.abs(posChange)}%` : `-${Math.abs(posChange)}%`) : 'N/A',
          direction: posChange !== null ? (posChange <= 0 ? 'up' : 'down') : 'up'
        },
        {
          label: 'Organic Keywords',
          value: fmt(keywordRows.length),
          change: '',
          direction: 'up'
        }
      ],
      topKeywords,
      raw: { cur, prev, curStart, curEnd }
    };
  } catch (e) {
    console.warn(`  GSC fetch failed: ${e.message}`);
    return null;
  }
}

module.exports = { getGSCMetrics };
