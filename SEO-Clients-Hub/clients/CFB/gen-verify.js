// CFB Live SEO Verifier
// Fetches each implemented page, checks live title + meta against recommended values,
// and reports pass/fail. With --update, rewrites status fields in pages-data.js and
// regenerates the running log docx.
//
// Usage:
//   node gen-verify.js              — report only, no file changes
//   node gen-verify.js --update     — update pages-data.js + regenerate docx

const data = require('./pages-data.js');
const fs   = require('fs');
const path = require('path');

const UPDATE = process.argv.includes('--update');
const TODAY  = new Date().toISOString().slice(0, 10);

// ---- helpers ----------------------------------------------------------------

function decodeEntities(str) {
  return str
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g,  '<')
    .replace(/&gt;/g,  '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&#39;/g,  "'")
    .replace(/&reg;/g,  '®')
    .replace(/&trade;/g,'™')
    .replace(/\s+/g, ' ')
    .trim();
}

async function fetchMeta(url) {
  try {
    const res = await fetch(url, {
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; CFB-SEO-Verifier/1.0)' },
      redirect: 'follow',
      signal: AbortSignal.timeout(15000)
    });
    const html = await res.text();

    // title tag
    const titleMatch = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
    const title = titleMatch ? decodeEntities(titleMatch[1]) : null;

    // meta description — handles both attribute orders
    const metaMatch =
      html.match(/<meta\s+name=["']description["']\s+content=["']([^"']*?)["']/i) ||
      html.match(/<meta\s+content=["']([^"']*?)["']\s+name=["']description["']/i);
    const meta = metaMatch ? decodeEntities(metaMatch[1]) : null;

    return { title, meta };
  } catch (e) {
    return { title: null, meta: null, error: e.message };
  }
}

function norm(s) { return s ? s.replace(/\s+/g, ' ').trim() : ''; }

function checkField(live, recommended) {
  if (!live) return { pass: false, reason: 'not found in page HTML' };
  const l = norm(live), r = norm(recommended);
  if (l === r) return { pass: true };
  // Missing brand suffix only
  const withoutCFB = r.replace(/\s*\|\s*CFB\s*$/, '');
  if (l === withoutCFB) return { pass: false, reason: 'missing | CFB suffix' };
  // Truncated (live is the start of recommended — HubSpot sometimes clips)
  if (r.startsWith(l)) return { pass: false, reason: `truncated — live: "${l}"` };
  return { pass: false, reason: `mismatch — live: "${l}"` };
}

// ---- main -------------------------------------------------------------------

async function main() {
  console.log(`\nCFB Live SEO Verification  •  ${TODAY}`);
  console.log(`Mode: ${UPDATE ? 'UPDATE pages-data.js + regenerate doc' : 'report only'}`);
  console.log('─'.repeat(72));

  const results = [];

  for (const page of data.pages) {
    const isImplemented = /^IMPLEMENTED|^VERIFIED LIVE/.test(page.status);
    if (!isImplemented) {
      console.log(`\n  ⏭  SKIP  ${page.name}  (not yet implemented)`);
      continue;
    }

    process.stdout.write(`\n  → ${page.name.padEnd(45)}`);
    const { title: liveTitle, meta: liveMeta, error } = await fetchMeta(page.liveUrl);

    if (error) {
      process.stdout.write(`ERROR: ${error}\n`);
      results.push({ page, outcome: 'error', error });
      continue;
    }

    const titleCheck = checkField(liveTitle, page.title.text);
    const metaCheck  = checkField(liveMeta,  page.meta.text);
    const pass = titleCheck.pass && metaCheck.pass;

    process.stdout.write(pass ? '✅ PASS\n' : '❌ FAIL\n');
    if (!titleCheck.pass) console.log(`       Title ❌  ${titleCheck.reason}`);
    if (!metaCheck.pass)  console.log(`       Meta  ❌  ${metaCheck.reason}`);

    results.push({ page, pass, titleCheck, metaCheck, liveTitle, liveMeta });
  }

  // ---- summary ----
  const checked = results.filter(r => r.outcome !== 'error' && r.pass !== undefined);
  const passed  = checked.filter(r => r.pass);
  const failed  = checked.filter(r => !r.pass);
  const errors  = results.filter(r => r.outcome === 'error');

  console.log('\n' + '─'.repeat(72));
  console.log(`RESULT  ${passed.length} pass  •  ${failed.length} fail  •  ${errors.length} error  •  ${results.length - checked.length - errors.length} skipped`);

  if (failed.length) {
    console.log('\nFAILS:');
    failed.forEach(r => {
      console.log(`  ❌ ${r.page.name}`);
      if (!r.titleCheck.pass) console.log(`       Title: ${r.titleCheck.reason}`);
      if (!r.metaCheck.pass)  console.log(`       Meta:  ${r.metaCheck.reason}`);
    });
  }

  if (!UPDATE) {
    console.log('\nRun with --update to write results back to pages-data.js and regenerate the doc.\n');
    return;
  }

  // ---- update pages-data.js ----
  let src = fs.readFileSync('./pages-data.js', 'utf8');
  let changes = 0;

  for (const r of checked) {
    const old = r.page.status;
    // Strip any previous verification suffix so we always write a clean new one
    const base = old
      .replace(/ — ⚠️ LIVE CHECK FAILED[^'"]*/g, '')
      .replace(/ — VERIFIED LIVE[^'"]*/g, '')
      .replace(/ — note:[^'"]*/g, '');

    let newStatus;
    if (r.pass) {
      newStatus = `VERIFIED LIVE ${TODAY} (title ✅, meta ✅)`;
    } else {
      const issues = [];
      if (!r.titleCheck.pass) issues.push(`title — ${r.titleCheck.reason}`);
      if (!r.metaCheck.pass)  issues.push(`meta — ${r.metaCheck.reason}`);
      newStatus = `${base} — ⚠️ LIVE CHECK FAILED ${TODAY}: ${issues.join(' · ')}`;
    }

    if (newStatus !== old) {
      // Escape for use in a string replacement — match the exact quoted status value
      const escaped = old.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      src = src.replace(new RegExp(escaped), newStatus);
      console.log(`\n  Updated: ${r.page.name}`);
      changes++;
    }
  }

  if (changes) {
    fs.writeFileSync('./pages-data.js', src);
    console.log(`\n${changes} status(es) updated in pages-data.js.`);

    // Regenerate doc
    console.log('Regenerating running log...');
    const { execSync } = require('child_process');
    execSync('node gen-running-log.js', { stdio: 'inherit' });
  } else {
    console.log('\nNo status changes needed.');
  }

  console.log('');
}

main().catch(err => { console.error(err); process.exit(1); });
