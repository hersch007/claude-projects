// Phase 0 pilot server. One client (GroupRB, per PHASE0-DESIGN.md §8), one
// "run audit" button, no database, no job queue — an in-memory Map is enough
// for a single pilot client triggered by one person. See design doc §4-6.
const express = require('express');
const path = require('path');
const crypto = require('crypto');
const fs = require('fs');
const { runAudit } = require('../../seo-tool/lib/audit-engine');

const app = express();
const PORT = process.env.PORT || 4000;

// Overridable via env for testing against a fixture config without touching
// the real pilot client's file.
const PILOT_CLIENT_PATH = process.env.PILOT_CLIENT_PATH || path.join(__dirname, '../../seo-tool/clients/grouprb.json');
// GroupRB auditing its own site runs under its own letterhead — no provider
// picker needed for a single hardcoded pilot client.
const PILOT_PROVIDER = '4';

// runId -> { status: 'crawling'|'done'|'error', pagesCrawled, totalQueued, score, html, error }
const runs = new Map();

app.use(express.static(path.join(__dirname, '../public')));

app.get('/api/audit/client', (req, res) => {
  if (!fs.existsSync(PILOT_CLIENT_PATH)) {
    return res.status(500).json({ error: 'Pilot client config not found' });
  }
  const client = JSON.parse(fs.readFileSync(PILOT_CLIENT_PATH, 'utf8'));
  res.json({ name: client.name, url: client.url });
});

app.post('/api/audit/run', (req, res) => {
  if (!fs.existsSync(PILOT_CLIENT_PATH)) {
    return res.status(500).json({ error: 'Pilot client config not found' });
  }
  const client = JSON.parse(fs.readFileSync(PILOT_CLIENT_PATH, 'utf8'));
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

app.listen(PORT, () => console.log(`Phase 0 pilot server listening on http://localhost:${PORT}`));
