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
  const { rows } = await getPool().query(`
    SELECT c.id, c.slug, c.name, c.url,
           latest.seo_health_score AS latest_score, latest.run_date AS latest_run_date
    FROM clients c
    LEFT JOIN LATERAL (
      SELECT seo_health_score, run_date FROM audit_runs
      WHERE client_id = c.id ORDER BY run_date DESC LIMIT 1
    ) latest ON true
    ORDER BY c.name ASC
  `);
  res.json(rows);
});

app.get('/api/clients/:slug', async (req, res) => {
  const client = await findClientBySlug(req.params.slug);
  if (!client) return res.status(404).json({ error: 'Client not found' });

  const { rows: runHistory } = await getPool().query(
    `SELECT run_date AS date, seo_health_score AS score, 'run' AS source
     FROM audit_runs WHERE client_id = $1`,
    [client.id]
  );
  const { rows: manualHistory } = await getPool().query(
    `SELECT entry_date AS date, score, 'manual' AS source
     FROM manual_score_entries WHERE client_id = $1`,
    [client.id]
  );
  // Merge by date — a real run for a given date wins over a manual entry for
  // the same date, since it's ground truth.
  const merged = new Map();
  for (const m of manualHistory) merged.set(m.date.toISOString().split('T')[0], m);
  for (const r of runHistory) merged.set(r.date.toISOString().split('T')[0], r);
  const history = [...merged.entries()]
    .map(([date, v]) => ({ date, score: v.score, source: v.source }))
    .sort((a, b) => a.date.localeCompare(b.date));

  res.json({ client, history, providers: PROVIDERS });
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
    runs.set(runId, {
      status: 'done', pagesCrawled: result.results.length, score: result.scoreData.score,
      html: result.html, slug: clientRow.slug,
      // Kept for the Word export route (§8) — generated on demand rather
      // than pre-built, since not every run's report gets downloaded as
      // .docx. Not persisted to the DB; only available for a run just
      // completed, same lifecycle as the HTML report.
      docxSource: { client: clientRow, results: result.results, scoreData: result.scoreData, provider: result.provider, date: result.date },
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
    const buffer = await buildDocxReport(run.docxSource);
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

(async () => {
  await ensureSchema();
  app.listen(PORT, () => console.log(`SEO Platform server listening on http://localhost:${PORT}`));
})();
