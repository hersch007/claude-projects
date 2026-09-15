// CLI wrapper around lib/audit-engine.js. All crawl/scoring/report logic now
// lives in the engine (reentrant, callable from other code like the Phase 0
// web app); this file just handles argv, interactive prompts, and writing
// the final report to disk — the parts that only make sense for a terminal.
// See webapp/PHASE0-DESIGN.md §3.
const fs = require('fs');
const path = require('path');
const readline = require('readline');
const { runAudit, PROVIDERS } = require('./lib/audit-engine');

// ─── Config ───────────────────────────────────────────────────────────────────

const clientName = process.argv[2];
if (!clientName) {
  console.error('Usage: node audit.js <client>   e.g.  node audit.js fruth');
  process.exit(1);
}

const configPath = path.join(__dirname, 'clients', `${clientName}.json`);
if (!fs.existsSync(configPath)) {
  console.error(`No config found at ${configPath}`);
  process.exit(1);
}

const client = JSON.parse(fs.readFileSync(configPath, 'utf8'));

// ─── Shared prompt interface ────────────────────────────────────────────────
// Two modes:
//  - Real TTY (a human typing in a terminal): use readline normally. stdin
//    never ends mid-run here, so one interface can safely serve every prompt.
//  - Piped/non-interactive stdin (e.g. an agent driving this script): readline
//    auto-closes the moment the underlying pipe hits EOF, and since the crawl
//    takes time, the pipe is often already drained and closed by the time a
//    later prompt (e.g. performance metrics) fires — even with one shared
//    interface, that second question() throws ERR_USE_AFTER_CLOSE instead of
//    hanging. So for piped input, read all of stdin up front (synchronously,
//    before any prompt is shown) and serve answers from a line queue instead —
//    no readline, no dependency on the pipe staying open across the run.
let _sharedRl = null;
let _pipedLines = null;
let _pipedIndex = 0;

function getSharedPrompt() {
  if (!process.stdin.isTTY) {
    if (_pipedLines === null) {
      let raw = '';
      try { raw = fs.readFileSync(0, 'utf8'); } catch { /* no piped input at all */ }
      _pipedLines = raw.split('\n');
    }
    return async (q) => {
      process.stdout.write(q);
      const line = (_pipedLines[_pipedIndex++] ?? '').replace(/\r$/, '');
      console.log(line);
      return line;
    };
  }
  if (!_sharedRl) _sharedRl = readline.createInterface({ input: process.stdin, output: process.stdout });
  return q => new Promise(res => _sharedRl.question(q, res));
}
function closeSharedPrompt() {
  if (_sharedRl) { _sharedRl.close(); _sharedRl = null; }
}

// ─── Provider prompt ────────────────────────────────────────────────────────

async function promptProvider() {
  // A config can pin a provider (skips the prompt on repeat/automated runs) via
  // "provider": "1".."4" or a name matching PROVIDERS[key].name (case-insensitive).
  if (client.provider) {
    const pinned = PROVIDERS[client.provider] ||
      Object.values(PROVIDERS).find(p => p.name.toLowerCase() === String(client.provider).toLowerCase());
    if (pinned) {
      console.log(`Provider company: ${pinned.name} (pinned in ${clientName}.json)`);
      return pinned;
    }
  }

  const ask = getSharedPrompt();

  console.log('\n─── Provider Company ─────────────────────────────────────────────');
  console.log('  Which company is this audit being prepared under?');
  Object.entries(PROVIDERS).forEach(([key, p]) => console.log(`  ${key}) ${p.name}  (${p.email})`));

  let choice = (await ask('\n  Enter 1-4: ')).trim();
  while (!PROVIDERS[choice]) {
    choice = (await ask('  Please enter 1, 2, 3, or 4: ')).trim();
  }
  return PROVIDERS[choice];
}

// ─── Metrics prompt ───────────────────────────────────────────────────────────

function loadMetrics(outDir) {
  const p = path.join(outDir, 'metrics.json');
  try { return JSON.parse(fs.readFileSync(p, 'utf8')); } catch { return null; }
}

async function promptMetrics(outDir) {
  const existing = loadMetrics(outDir);

  const ask = getSharedPrompt();

  console.log('\n─── Performance Metrics ──────────────────────────────────────────');
  if (existing) {
    console.log(`  Last saved: ${existing.snapshot_date || 'unknown date'}`);
    existing.metrics.forEach(m => console.log(`  ${m.label}: ${m.value} (${m.change})`));
  }
  console.log('  Press Enter to keep existing data, or type new values.');

  const yn = (await ask('\n  Do you have updated metrics to enter? (y/N): ')).trim().toLowerCase();
  if (yn !== 'y') {
    console.log(existing ? '  Keeping existing metrics.' : '  No metrics — section will be skipped.');
    return existing;
  }

  console.log('  (Press Enter to skip any field)\n');

  const snapDate = (await ask('  Snapshot date (e.g. Aug 11, 2026): ')).trim();
  const compLabel = (await ask('  Comparison label (e.g. vs. May 27): ')).trim() || 'vs. prior period';

  async function askMetric(label) {
    const value  = (await ask(`  ${label} — value: `)).trim();
    if (!value) return null;
    const change = (await ask(`  ${label} — change (e.g. +12.5%): `)).trim();
    const dir    = change.startsWith('-') ? 'down' : 'up';
    return { label, value, change, direction: dir };
  }

  const fields = [
    'Organic Clicks',
    'Organic Keywords',
    'Avg. Position',
    'Impressions',
  ];

  const metricsOut = [];
  for (const f of fields) {
    const m = await askMetric(f);
    if (m) metricsOut.push(m);
  }

  if (!metricsOut.length) {
    console.log('  No values entered — keeping existing metrics.');
    return existing;
  }

  const saved = {
    snapshot_date: snapDate || new Date().toISOString().split('T')[0],
    comparison_label: compLabel,
    metrics: metricsOut
  };

  const metricsPath = path.join(outDir, 'metrics.json');
  fs.writeFileSync(metricsPath, JSON.stringify(saved, null, 2), 'utf8');
  console.log('  Metrics saved.\n');
  return saved;
}

// ─── Main ─────────────────────────────────────────────────────────────────────

(async () => {
  const provider = await promptProvider();

  const outDir = client.output_dir || __dirname;
  const { scoreData, html, date } = await runAudit(client, {
    provider,
    onNeedMetrics: promptMetrics,
  });
  closeSharedPrompt();

  console.log(`\nSEO Health Score: ${scoreData.score}/100`);
  if (scoreData.deductions.length) {
    scoreData.deductions.forEach(d => console.log(`  -${d.pts}  ${d.label}`));
  }

  const fileName = `${client.name.replace(/\s+/g, '-')}-SEO-Audit-${date}.html`;
  const outPath = path.join(outDir, fileName);

  fs.writeFileSync(outPath, html, 'utf8');
  console.log(`\nReport saved to:\n${outPath}\n`);
})();
