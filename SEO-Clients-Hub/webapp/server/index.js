// Phase 0 pilot server. One client (GroupRB, per PHASE0-DESIGN.md §8), one
// "run audit" button, no database, no job queue — an in-memory Map is enough
// for a single pilot client triggered by one person. See design doc §4-6.
// Auth added per PHASE1-DESIGN.md §3 — the Phase 0 deploy was public with no
// login, which is urgent to fix now that it's live on a real URL.
const express = require('express');
const cookieSession = require('cookie-session');
const path = require('path');
const crypto = require('crypto');
const fs = require('fs');
const { runAudit } = require('../../seo-tool/lib/audit-engine');
const { getPool } = require('../../seo-tool/lib/db-storage');

const app = express();
const PORT = process.env.PORT || 4000;
const SITE_PASSWORD = process.env.SITE_PASSWORD;

if (!SITE_PASSWORD) {
  console.warn('WARNING: SITE_PASSWORD is not set — /api/login will reject all attempts until it is configured.');
}

// Applies webapp/db/schema.sql on every boot instead of requiring a manual
// `psql` step — schema.sql is written to be idempotent (CREATE TABLE IF NOT
// EXISTS, ON CONFLICT DO NOTHING on the seed data), so re-running it on
// every deploy is safe and just confirms the schema is current.
async function ensureSchema() {
  if (!process.env.DATABASE_URL) {
    console.warn('DATABASE_URL not set — skipping schema setup (DB-backed features unavailable until it is configured).');
    return;
  }
  const schemaSql = fs.readFileSync(path.join(__dirname, '../db/schema.sql'), 'utf8');
  try {
    await getPool().query(schemaSql);
    console.log('Database schema is up to date.');
  } catch (err) {
    console.error('Failed to apply database schema:', err.message);
  }
}

app.use(express.json());
app.use(cookieSession({
  name: 'session',
  secret: process.env.SESSION_SECRET || 'dev-only-secret-set-a-real-one-in-production',
  maxAge: 30 * 24 * 60 * 60 * 1000, // 30 days — internal tool, not worth re-logging-in constantly
}));

app.post('/api/login', (req, res) => {
  if (!SITE_PASSWORD) return res.status(500).json({ error: 'Server not configured with SITE_PASSWORD' });
  if (req.body && req.body.password === SITE_PASSWORD) {
    req.session.authed = true;
    return res.json({ ok: true });
  }
  res.status(401).json({ error: 'Wrong password' });
});

app.post('/api/logout', (req, res) => {
  req.session = null;
  res.json({ ok: true });
});

app.get('/login.html', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/login.html'));
});

app.use((req, res, next) => {
  if (req.session && req.session.authed) return next();
  if (req.path.startsWith('/api/')) return res.status(401).json({ error: 'Not authenticated' });
  return res.redirect('/login.html');
});

// Overridable via env for testing against a fixture config without touching
// the real pilot client's file.
const PILOT_CLIENT_PATH = process.env.PILOT_CLIENT_PATH || path.join(__dirname, '../../seo-tool/clients/grouprb.json');
// GroupRB auditing its own site runs under its own letterhead — no provider
// picker needed for a single hardcoded pilot client.
const PILOT_PROVIDER = '4';

// runId -> { status: 'crawling'|'done'|'error', pagesCrawled, totalQueued, score, html, error }
const runs = new Map();

// grouprb.json's output_dir is a hardcoded Windows path (used by the local
// CLI on Richard's machine) — that path doesn't exist on a Linux server, so
// score-history.json etc. would fail to write there. We don't touch the
// shared JSON config itself (that would break the local CLI), we just
// override output_dir here with a portable path to the same repo location.
function loadPilotClient() {
  if (!fs.existsSync(PILOT_CLIENT_PATH)) return null;
  const client = JSON.parse(fs.readFileSync(PILOT_CLIENT_PATH, 'utf8'));
  client.output_dir = path.join(__dirname, '../../clients/GroupRB');
  return client;
}

app.use(express.static(path.join(__dirname, '../public')));

app.get('/api/audit/client', (req, res) => {
  const client = loadPilotClient();
  if (!client) return res.status(500).json({ error: 'Pilot client config not found' });
  res.json({ name: client.name, url: client.url });
});

app.post('/api/audit/run', (req, res) => {
  const client = loadPilotClient();
  if (!client) return res.status(500).json({ error: 'Pilot client config not found' });
  const runId = crypto.randomUUID();
  runs.set(runId, { status: 'crawling', pagesCrawled: 0, totalQueued: 0 });

  // Fire and forget — the HTTP request returns immediately with a runId;
  // the browser polls /api/audit/status/:runId instead of holding this
  // request open for the full ~60-page crawl duration.
  runAudit(client, {
    provider: PILOT_PROVIDER,
    onProgress: (e) => {
      const run = runs.get(runId);
      if (run) Object.assign(run, { pagesCrawled: e.pagesCrawled, totalQueued: e.totalQueued, currentUrl: e.currentUrl });
    },
  }).then(result => {
    runs.set(runId, { status: 'done', pagesCrawled: result.results.length, score: result.scoreData.score, html: result.html });
  }).catch(err => {
    console.error(`Audit run ${runId} failed:`, err);
    runs.set(runId, { status: 'error', error: err.message });
  });

  res.json({ runId, client: client.name });
});

app.get('/api/audit/status/:runId', (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run) return res.status(404).json({ error: 'Unknown runId' });
  const { html, ...status } = run; // don't ship the full report on every status poll
  res.json(status);
});

app.get('/api/audit/report/:runId', (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run) return res.status(404).send('Unknown runId');
  if (run.status === 'error') return res.status(500).send(`Audit failed: ${run.error}`);
  if (run.status !== 'done') return res.status(425).send('Report not ready yet');
  res.set('Content-Type', 'text/html').send(run.html);
});

(async () => {
  await ensureSchema();
  app.listen(PORT, () => console.log(`Phase 0 pilot server listening on http://localhost:${PORT}`));
})();
