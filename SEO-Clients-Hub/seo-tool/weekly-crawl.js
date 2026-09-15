// Entry point for the Render Cron Job (PHASE2-DESIGN.md §4) — crawls every
// client in the database sequentially (no urgency requiring parallelism,
// and this is politer to client sites than hammering them all at once),
// then emails a summary. Reuses runAudit()/dbStorage exactly as the web
// server does — no separate crawl logic to maintain.
const { runAudit, PROVIDERS } = require('./lib/audit-engine');
const dbStorage = require('./lib/db-storage');
const { getPool } = dbStorage;
const { sendSummaryEmail } = require('./lib/send-summary-email');

async function getProviderIdByName(name) {
  const { rows } = await getPool().query('SELECT id FROM providers WHERE name = $1', [name]);
  return rows[0] ? rows[0].id : null;
}

async function getLastScore(clientId) {
  const { rows } = await getPool().query(
    `SELECT seo_health_score FROM audit_runs WHERE client_id = $1 ORDER BY run_date DESC LIMIT 1`,
    [clientId]
  );
  return rows[0] ? rows[0].seo_health_score : null;
}

// client.default_provider_id is a providers.id (foreign key), not a
// PROVIDERS-map key like '1'-'4' — look up the actual row and hand
// runAudit a full provider object directly (resolveProvider in
// audit-engine.js accepts either a '1'-'4' key or an object with `.name`).
async function resolveClientProvider(client) {
  if (client.default_provider_id) {
    const { rows } = await getPool().query('SELECT * FROM providers WHERE id = $1', [client.default_provider_id]);
    if (rows[0]) {
      return { name: rows[0].name, email: rows[0].email, brand: rows[0].brand_hex, brand2: rows[0].brand2_hex };
    }
  }
  return PROVIDERS['1']; // no default pinned — set clients.default_provider_id manually to customize
}

async function main() {
  if (!process.env.DATABASE_URL) {
    console.error('DATABASE_URL is not set — cannot run the weekly crawl.');
    process.exit(1);
  }

  const pool = getPool();
  const { rows: clients } = await pool.query('SELECT * FROM clients ORDER BY name ASC');
  console.log(`Weekly crawl starting for ${clients.length} clients.`);

  const results = [];
  const errors = [];

  for (const client of clients) {
    // default_provider_id lets a client keep its established letterhead
    // across automated runs; falls back to provider 1 for clients that
    // have never had one pinned (all of them, as of the Phase 1 migration —
    // set clients.default_provider_id manually per client to customize).
    const provider = await resolveClientProvider(client);

    const oldScore = await getLastScore(client.id);
    console.log(`[${client.slug}] crawling ${client.url}...`);

    try {
      const result = await runAudit(client, { provider, storage: dbStorage });
      const providerId = await getProviderIdByName(provider.name);
      await pool.query(
        `UPDATE audit_runs SET provider_id = $1, pages_crawled = $2, deductions = $3, html_report = $4
         WHERE client_id = $5 AND run_date = $6`,
        [providerId, result.results.length, JSON.stringify(result.scoreData.deductions), result.html, client.id, result.date]
      );
      results.push({ client: client.name, oldScore, newScore: result.scoreData.score });
      console.log(`[${client.slug}] done — score ${result.scoreData.score}`);
    } catch (err) {
      console.error(`[${client.slug}] FAILED:`, err.message);
      errors.push(`${client.name}: ${err.message}`);
    }
  }

  const emailResult = await sendSummaryEmail(results, { errors });
  console.log('Summary email:', emailResult.sent ? `sent (${emailResult.id})` : `not sent (${emailResult.reason})`);
  console.log(`Weekly crawl finished. ${results.length} succeeded, ${errors.length} failed.`);

  await pool.end();
  process.exit(errors.length ? 1 : 0);
}

main().catch(err => {
  console.error('Weekly crawl script crashed:', err);
  process.exit(1);
});
