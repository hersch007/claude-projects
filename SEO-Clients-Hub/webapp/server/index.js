// Phase 1 server — multi-client dashboard, DB-backed history, no job queue
// (see PHASE1-DESIGN.md §6: the in-memory run-status Map already supports
// multiple concurrent runs, one per runId, so multi-client doesn't need real
// queue infra any more than Phase 0 did).
// Auth added per PHASE1-DESIGN.md §3 — the Phase 0 deploy was public with no
// login, which was urgent to fix once it went live on a real URL.
const express = require('express');
const cookieSession = require('cookie-session');
const path = require('path');
const crypto = require('crypto');
const fs = require('fs');
const { runAudit, PROVIDERS } = require('../../seo-tool/lib/audit-engine');
const dbStorage = require('../../seo-tool/lib/db-storage');
const { buildDocxReport } = require('../../seo-tool/lib/build-docx-report');
const { enrichWithVolumes } = require('../../seo-tool/lib/keyword-planner');
const { generateNarrative } = require('../../seo-tool/lib/narrative-report');
const { getPool } = dbStorage;

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

app.use(express.static(path.join(__dirname, '../public')));

// ─── Migration: sync seo-tool/clients/*.json into the clients table, and
// import each client's existing score-history.json/keyword-history.json
// (already-structured data — different from, and not blocked by, the
// earlier decision not to parse the old .md/.docx narrative reports; see
// PHASE1-DESIGN.md §9). Safe to re-run: upserts by slug/date throughout. ──

const SEO_TOOL_CLIENTS_DIR = path.join(__dirname, '../../seo-tool/clients');
const CLIENTS_REPO_DIR = path.join(__dirname, '../../clients');

app.post('/api/admin/migrate', async (req, res) => {
  if (!process.env.DATABASE_URL) return res.status(500).json({ error: 'DATABASE_URL not configured' });
  const pool = getPool();
  const summary = { clientsUpserted: 0, historyRowsImported: 0, keywordRowsImported: 0, errors: [] };

  const files = fs.readdirSync(SEO_TOOL_CLIENTS_DIR).filter(f => f.endsWith('.json'));
  for (const file of files) {
    const slug = file.replace(/\.json$/, '');
    try {
      const config = JSON.parse(fs.readFileSync(path.join(SEO_TOOL_CLIENTS_DIR, file), 'utf8'));

      const { rows } = await pool.query(
        `INSERT INTO clients (slug, name, url, brand_color, max_pages, ignore_paths, gsc_property)
         VALUES ($1, $2, $3, $4, $5, $6, $7)
         ON CONFLICT (slug) DO UPDATE SET
           name = EXCLUDED.name, url = EXCLUDED.url, brand_color = EXCLUDED.brand_color,
           max_pages = EXCLUDED.max_pages, ignore_paths = EXCLUDED.ignore_paths,
           gsc_property = EXCLUDED.gsc_property
         RETURNING id`,
        [slug, config.name, config.url, config.brand_color || null, config.max_pages || 60,
         JSON.stringify(config.ignore_paths || []), config.gsc_property || null]
      );
      const clientId = rows[0].id;
      summary.clientsUpserted++;

      // Import existing history JSON if present — output_dir's last path
      // segment is the folder name under SEO-Clients-Hub/clients/.
      const folderName = path.basename((config.output_dir || '').replace(/\\/g, '/'));
      if (!folderName) continue;
      const clientDir = path.join(CLIENTS_REPO_DIR, folderName);

      const scoreHistoryPath = path.join(clientDir, 'score-history.json');
      if (fs.existsSync(scoreHistoryPath)) {
        const history = JSON.parse(fs.readFileSync(scoreHistoryPath, 'utf8'));
        for (const entry of history) {
          await pool.query(
            `INSERT INTO audit_runs (client_id, run_date, seo_health_score, status)
             VALUES ($1, $2, $3, 'done')
             ON CONFLICT (client_id, run_date) DO UPDATE SET seo_health_score = EXCLUDED.seo_health_score`,
            [clientId, entry.date, entry.score]
          );
          summary.historyRowsImported++;
        }
      }

      const keywordHistoryPath = path.join(clientDir, 'keyword-history.json');
      if (fs.existsSync(keywordHistoryPath)) {
        const kwHistory = JSON.parse(fs.readFileSync(keywordHistoryPath, 'utf8'));
        for (const entry of kwHistory) {
          await pool.query(
            `INSERT INTO gsc_snapshots (client_id, period_end, top_keywords)
             VALUES ($1, $2, $3)
             ON CONFLICT (client_id, period_end) DO UPDATE SET top_keywords = EXCLUDED.top_keywords`,
            [clientId, entry.date, JSON.stringify(entry.keywords)]
          );
          summary.keywordRowsImported++;
        }
      }
    } catch (err) {
      summary.errors.push(`${slug}: ${err.message}`);
    }
  }

  res.json(summary);
});

// ─── Multi-client API ───────────────────────────────────────────────────────

async function findClientBySlug(slug) {
  const { rows } = await getPool().query('SELECT * FROM clients WHERE slug = $1', [slug]);
  return rows[0] || null;
}

async function getProviderId(providerKey) {
  const name = (PROVIDERS[providerKey] || PROVIDERS['1']).name;
  const { rows } = await getPool().query('SELECT id FROM providers WHERE name = $1', [name]);
  return rows[0] ? rows[0].id : null;
}

app.get('/api/clients', async (req, res) => {
  const archived = req.query.archived === '1';
  const { rows } = await getPool().query(`
    SELECT c.id, c.slug, c.name, c.url,
           latest.seo_health_score AS latest_score, latest.run_date AS latest_run_date
    FROM clients c
    LEFT JOIN LATERAL (
      SELECT seo_health_score, run_date FROM audit_runs
      WHERE client_id = c.id ORDER BY run_date DESC LIMIT 1
    ) latest ON true
    WHERE c.archived = $1
    ORDER BY c.name ASC
  `, [archived]);
  res.json(rows);
});

app.post('/api/clients', async (req, res) => {
  const { slug, name, url, gsc_property } = req.body || {};
  if (!slug || !name || !url) return res.status(400).json({ error: 'slug, name, and url are required' });
  if (!/^[a-z0-9-]+$/.test(slug)) return res.status(400).json({ error: 'slug must be lowercase letters, numbers, and hyphens only' });

  try {
    const { rows } = await getPool().query(
      `INSERT INTO clients (slug, name, url, gsc_property) VALUES ($1, $2, $3, $4) RETURNING *`,
      [slug, name, url, gsc_property || null]
    );
    res.json({ ok: true, client: rows[0] });
  } catch (err) {
    if (err.code === '23505') return res.status(409).json({ error: `A client with slug "${slug}" already exists` });
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/clients/:slug', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });

  const { rows: runHistory } = await getPool().query(
    `SELECT id, run_date AS date, seo_health_score AS score, 'run' AS source,
            (html_report IS NOT NULL) AS has_report
     FROM audit_runs WHERE client_id = $1`,
    [client.id]
  );
  const { rows: manualHistory } = await getPool().query(
    `SELECT id, entry_date AS date, score, 'manual' AS source, false AS has_report
     FROM manual_score_entries WHERE client_id = $1`,
    [client.id]
  );
  // Merge by date — a real run for a given date wins over a manual entry for
  // the same date, since it's ground truth.
  const merged = new Map();
  for (const m of manualHistory) merged.set(m.date.toISOString().split('T')[0], m);
  for (const r of runHistory) merged.set(r.date.toISOString().split('T')[0], r);
  const history = [...merged.entries()]
    .map(([date, v]) => ({ date, score: v.score, source: v.source, id: v.id, hasReport: v.has_report }))
    .sort((a, b) => a.date.localeCompare(b.date));

  res.json({ client, history, providers: PROVIDERS });
});

app.patch('/api/clients/:slug', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  if (typeof (req.body && req.body.business_notes) !== 'string') {
    return res.status(400).json({ error: 'business_notes (string) is required' });
  }
  await getPool().query('UPDATE clients SET business_notes = $1 WHERE id = $2', [req.body.business_notes, client.id]);
  res.json({ ok: true });
});

app.patch('/api/clients/:slug/archive', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  const archived = !!(req.body && req.body.archived);
  await getPool().query('UPDATE clients SET archived = $1 WHERE id = $2', [archived, client.id]);
  res.json({ ok: true });
});

app.delete('/api/clients/:slug', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  const pool = getPool();
  const dbClient = await pool.connect();
  try {
    await dbClient.query('BEGIN');
    await dbClient.query(
      `DELETE FROM page_results WHERE audit_run_id IN (SELECT id FROM audit_runs WHERE client_id = $1)`,
      [client.id]
    );
    await dbClient.query('DELETE FROM audit_runs WHERE client_id = $1', [client.id]);
    await dbClient.query('DELETE FROM manual_score_entries WHERE client_id = $1', [client.id]);
    await dbClient.query('DELETE FROM gsc_snapshots WHERE client_id = $1', [client.id]);
    await dbClient.query('DELETE FROM clients WHERE id = $1', [client.id]);
    await dbClient.query('COMMIT');
    res.json({ ok: true });
  } catch (err) {
    await dbClient.query('ROLLBACK');
    res.status(500).json({ error: err.message });
  } finally {
    dbClient.release();
  }
});

app.post('/api/clients/:slug/manual-score', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  const { date, score, note } = req.body || {};
  if (!date || typeof score !== 'number') return res.status(400).json({ error: 'date and numeric score are required' });

  await getPool().query(
    `INSERT INTO manual_score_entries (client_id, entry_date, score, note) VALUES ($1, $2, $3, $4)`,
    [client.id, date, score, note || null]
  );
  res.json({ ok: true });
});

app.delete('/api/clients/:slug/manual-score/:id', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  await getPool().query('DELETE FROM manual_score_entries WHERE id = $1 AND client_id = $2', [req.params.id, client.id]);
  res.json({ ok: true });
});

app.get('/api/clients/:slug/audit-run/:id/report', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).send('Client not found');
  const { rows } = await getPool().query(
    'SELECT html_report FROM audit_runs WHERE id = $1 AND client_id = $2',
    [req.params.id, client.id]
  );
  if (!rows[0]) return res.status(404).send('Run not found');
  if (!rows[0].html_report) return res.status(404).send('No saved report for this run (older runs imported before reports were stored don\'t have one).');
  res.set('Content-Type', 'text/html').send(rows[0].html_report);
});

app.delete('/api/clients/:slug/audit-run/:id', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });
  await getPool().query('DELETE FROM page_results WHERE audit_run_id = $1', [req.params.id]);
  await getPool().query('DELETE FROM audit_runs WHERE id = $1 AND client_id = $2', [req.params.id, client.id]);
  res.json({ ok: true });
});

// Diffs the just-completed run's deductions against the immediately prior
// run for the same client — audit_runs already stores both score and the
// full deductions list per run, so this needs no new schema or crawl work.
// Matches deductions by label since that's what identifies "the same issue"
// across runs. Returns null when there's no prior run to compare against,
// or the prior run predates deductions being stored (old migrated data).
async function computeChanges(clientId, currentRunDate, currentScore, currentDeductions) {
  const { rows } = await getPool().query(
    `SELECT run_date, seo_health_score, deductions FROM audit_runs
     WHERE client_id = $1 AND run_date < $2 AND deductions IS NOT NULL
     ORDER BY run_date DESC LIMIT 1`,
    [clientId, currentRunDate]
  );
  if (!rows[0]) return null;
  const prev = rows[0];
  const prevLabels = new Set((prev.deductions || []).map(d => d.label));
  const currLabels = new Set((currentDeductions || []).map(d => d.label));
  return {
    previousScore: prev.seo_health_score,
    previousDate: prev.run_date,
    scoreDelta: currentScore - prev.seo_health_score,
    newIssues: (currentDeductions || []).filter(d => !prevLabels.has(d.label)),
    resolvedIssues: (prev.deductions || []).filter(d => !currLabels.has(d.label)),
  };
}

// runId -> { status: 'crawling'|'done'|'error', pagesCrawled, totalQueued, score, html, error, slug }
const runs = new Map();

app.post('/api/clients/:slug/audit/run', async (req, res) => {
  const clientRow = await findClientBySlug(req.params.slug);
  if (!clientRow) return res.status(404).json({ error: 'Client not found' });

  const providerKey = (req.body && req.body.provider) || '1';
  const runId = crypto.randomUUID();
  runs.set(runId, { status: 'crawling', pagesCrawled: 0, totalQueued: 0, slug: clientRow.slug });

  // Client object passed to the engine: DB row's field names already match
  // what runAudit/dbStorage expect (id, name, url, max_pages, ignore_paths,
  // gsc_property) — ignore_paths comes back from pg as a parsed JS array
  // already (jsonb), no extra parsing needed.
  const clientForEngine = { ...clientRow };

  // Fire and forget — the HTTP request returns immediately with a runId;
  // the browser polls /api/clients/:slug/audit/status/:runId instead of
  // holding this request open for the full crawl duration.
  runAudit(clientForEngine, {
    provider: providerKey,
    storage: dbStorage,
    enrichKeywords: enrichWithVolumes,
    onProgress: (e) => {
      const run = runs.get(runId);
      if (run) Object.assign(run, { pagesCrawled: e.pagesCrawled, totalQueued: e.totalQueued, currentUrl: e.currentUrl });
    },
  }).then(async (result) => {
    // dbStorage.saveHistory already upserted a minimal audit_runs row
    // (client_id, run_date, seo_health_score) so the report's own trend
    // chart included today's score — fill in the rest of that same row now
    // that we have the full result (matched by the (client_id, run_date)
    // unique constraint, so this updates rather than duplicates).
    // Note: page_results is intentionally not populated in Phase 1 — the
    // html_report column already carries full page-level detail for the
    // UI's needs; adding a separate queryable page_results table is deferred
    // until something actually needs to query across pages/clients directly.
    const providerId = await getProviderId(providerKey);
    await getPool().query(
      `UPDATE audit_runs SET provider_id = $1, pages_crawled = $2, deductions = $3, html_report = $4
       WHERE client_id = $5 AND run_date = $6`,
      [providerId, result.results.length, JSON.stringify(result.scoreData.deductions), result.html, clientRow.id, result.date]
    );
    const changes = await computeChanges(clientRow.id, result.date, result.scoreData.score, result.scoreData.deductions);
    runs.set(runId, {
      status: 'done', pagesCrawled: result.results.length, score: result.scoreData.score,
      html: result.html, slug: clientRow.slug, changes,
      // Kept for the Word export route (§8) — generated on demand rather
      // than pre-built, since not every run's report gets downloaded as
      // .docx. Not persisted to the DB; only available for a run just
      // completed, same lifecycle as the HTML report.
      docxSource: { client: clientRow, results: result.results, scoreData: result.scoreData, provider: result.provider, date: result.date, changes },
    });
  }).catch(err => {
    console.error(`Audit run ${runId} for ${clientRow.slug} failed:`, err);
    runs.set(runId, { status: 'error', error: err.message, slug: clientRow.slug });
  });

  res.json({ runId, client: clientRow.name });
});

app.get('/api/clients/:slug/audit/status/:runId', (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run || run.slug !== req.params.slug) return res.status(404).json({ error: 'Unknown runId' });
  const { html, docxSource, ...status } = run; // don't ship the full report/crawl data on every status poll
  res.json(status);
});

app.get('/api/clients/:slug/audit/report/:runId', (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run || run.slug !== req.params.slug) return res.status(404).send('Unknown runId');
  if (run.status === 'error') return res.status(500).send(`Audit failed: ${run.error}`);
  if (run.status !== 'done') return res.status(425).send('Report not ready yet');
  res.set('Content-Type', 'text/html').send(run.html);
});

app.get('/api/clients/:slug/audit/report/:runId/docx', async (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run || run.slug !== req.params.slug) return res.status(404).send('Unknown runId');
  if (run.status !== 'done') return res.status(425).send('Report not ready yet');
  try {
    // Narrative is never generated inline here — it's a slow LLM call (the
    // network path to it has been observed stalling for 90s+ per attempt)
    // and this route needs to stay fast for the plain mechanical report,
    // which is the common case. Uses whatever narrative (if any) the
    // /docx/prepare route has already produced and cached on the run.
    const buffer = await buildDocxReport({ ...run.docxSource, narrative: run.narrative || null });
    const fileName = `${run.docxSource.client.name.replace(/\s+/g, '-')}-SEO-Audit-${run.docxSource.date}.docx`;
    res.set({
      'Content-Type': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'Content-Disposition': `attachment; filename="${fileName}"`,
    });
    res.send(buffer);
  } catch (err) {
    console.error(`Docx generation for run ${req.params.runId} failed:`, err);
    res.status(500).send('Failed to generate Word document');
  }
});

// Kicks off narrative generation in the background (fire-and-forget, same
// pattern as /audit/run) and returns immediately — the slow LLM call never
// blocks an HTTP response. The browser polls the status route below, then
// hits the plain /docx route above once ready, which will find the cached
// narrative and include it.
app.post('/api/clients/:slug/audit/report/:runId/docx/prepare', (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run || run.slug !== req.params.slug) return res.status(404).json({ error: 'Unknown runId' });
  if (run.status !== 'done') return res.status(425).json({ error: 'Report not ready yet' });

  run.narrativeStatus = 'generating';
  generateNarrative(run.docxSource).then((narrative) => {
    run.narrative = narrative;
    run.narrativeStatus = narrative ? 'done' : 'error';
  }).catch((err) => {
    console.error(`Narrative generation for run ${req.params.runId} failed:`, err);
    run.narrativeStatus = 'error';
  });

  res.json({ ok: true });
});

app.get('/api/clients/:slug/audit/report/:runId/docx/status', (req, res) => {
  const run = runs.get(req.params.runId);
  if (!run || run.slug !== req.params.slug) return res.status(404).json({ error: 'Unknown runId' });
  res.json({ status: run.narrativeStatus || 'not_started' });
});

(async () => {
  await ensureSchema();
  app.listen(PORT, () => console.log(`SEO Platform server listening on http://localhost:${PORT}`));
})();
