// Postgres-backed implementation of the storage interface audit-engine.js's
// runAudit() calls (see fileStorage in audit-engine.js and
// webapp/PHASE1-DESIGN.md §5). Passed in by the webapp server as
// `runAudit(client, { storage: dbStorage, ... })` — the CLI never touches
// this file, so audit.js's behavior is completely unaffected by it.
//
// Each method takes the `client` row object (must have `.id`, the
// clients.id primary key from webapp/db/schema.sql) instead of a file path.
//
// Note on scope: this only replicates what fileStorage replicated — the
// lightweight score/keyword history the engine itself needs while building
// a report. It does NOT insert the full audit_runs row (pages_crawled,
// deductions, html_report, provider_id) — the server does that itself after
// runAudit() returns, since only the caller has the full result object.
// saveHistory here does write a minimal audit_runs row (client_id, run_date,
// seo_health_score) so buildReport's trend chart includes today's score
// immediately; the server's later fuller INSERT/UPDATE on the same row
// (matched by the (client_id, run_date) unique constraint) fills in the
// rest without creating a duplicate.
const { Pool } = require('pg');

let pool;
function getPool() {
  if (!pool) {
    const connectionString = process.env.DATABASE_URL;
    if (!connectionString) throw new Error('DATABASE_URL is not set — dbStorage requires it.');
    pool = new Pool({
      connectionString,
      // Render's external Postgres connection strings include sslmode=require;
      // internal connections within the same Render project typically don't
      // need SSL. Respect whichever the connection string specifies.
      ssl: connectionString.includes('sslmode=require') ? { rejectUnauthorized: false } : undefined,
    });
  }
  return pool;
}

async function loadHistory(client) {
  const { rows } = await getPool().query(
    `SELECT run_date, seo_health_score FROM audit_runs WHERE client_id = $1 ORDER BY run_date ASC`,
    [client.id]
  );
  return rows.map(r => ({ date: r.run_date.toISOString().split('T')[0], score: r.seo_health_score }));
}

async function saveHistory(client, history) {
  // Mirrors fileStorage: the engine always calls this with today's entry as
  // the one that changed (either updated in place or freshly pushed), so we
  // only need to upsert that one row, not rewrite the whole table.
  const today = history[history.length - 1];
  await getPool().query(
    `INSERT INTO audit_runs (client_id, run_date, seo_health_score, status)
     VALUES ($1, $2, $3, 'done')
     ON CONFLICT (client_id, run_date)
     DO UPDATE SET seo_health_score = EXCLUDED.seo_health_score`,
    [client.id, today.date, today.score]
  );
}

async function loadKeywordHistory(client) {
  const { rows } = await getPool().query(
    `SELECT period_end, top_keywords FROM gsc_snapshots
     WHERE client_id = $1 ORDER BY period_end ASC LIMIT 12`,
    [client.id]
  );
  return rows.map(r => ({ date: r.period_end.toISOString().split('T')[0], keywords: r.top_keywords }));
}

async function saveKeywordHistory(client, date, keywords) {
  await getPool().query(
    `INSERT INTO gsc_snapshots (client_id, period_end, top_keywords)
     VALUES ($1, $2, $3)
     ON CONFLICT (client_id, period_end)
     DO UPDATE SET top_keywords = EXCLUDED.top_keywords`,
    [client.id, date, JSON.stringify(keywords)]
  );
}

async function loadMetrics(client) {
  // The file-based fallback (metrics.json, manually entered via the CLI
  // prompt) has no DB equivalent — the web path never prompts for manual
  // metrics (see PHASE0-DESIGN.md §3.4), so when there's no live GSC data,
  // the performance-metrics section of the report is simply omitted.
  return null;
}

module.exports = { loadHistory, saveHistory, loadKeywordHistory, saveKeywordHistory, loadMetrics, getPool };
