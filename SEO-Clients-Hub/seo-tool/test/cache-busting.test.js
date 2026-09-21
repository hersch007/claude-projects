// Regression coverage for the cache-busting fix (PR #38): re-running an
// audit against a WordPress site with a stale page cache kept returning
// old content even after the client purged their host's cache — because
// fetchPage() made an identical, cacheable request every time. Fixed by
// appending a per-run cache-busting query parameter to every crawled URL.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('cache-busting defeats an exact-URL-keyed page cache across two runs', async () => {
  // Simulates an aggressive full-page cache (like a WP caching plugin)
  // that caches the first response for an exact request URL verbatim and
  // replays it for any identical subsequent request.
  const cache = new Map();
  let liveWordCount = 40;
  function renderCategory() {
    return page({ main: `<h1>Category</h1><p>${'word '.repeat(liveWordCount)}</p>` });
  }
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/category">Category</a><p>Some homepage copy that is long enough on its own.</p>' })),
    '/__update': (req, res) => { liveWordCount = 150; res.writeHead(200); res.end('ok'); },
    '/category': (req, res) => {
      const key = req.url; // exact path+query, like a real page-cache plugin
      if (!cache.has(key)) cache.set(key, renderCategory());
      sendHtml(res, cache.get(key));
    },
  });

  try {
    const client = { name: 'Cache Test', url: site.url, max_pages: 10, ignore_paths: [] };

    const run1 = await runAudit(client, { storage: memoryStorage() });
    const category1 = run1.results.find(p => p.url.endsWith('/category'));
    assert.ok(category1.wordCount < 60, `first run should see close to the original ${liveWordCount}-word count, got ${category1.wordCount}`);

    await fetch(site.url + '/__update'); // simulate a real content edit

    const run2 = await runAudit(client, { storage: memoryStorage() });
    const category2 = run2.results.find(p => p.url.endsWith('/category'));
    assert.ok(
      category2.wordCount > category1.wordCount,
      `second run should reflect the update despite the exact-URL-keyed cache (got ${category2.wordCount}, expected > ${category1.wordCount})`
    );

    // The cache-busting parameter must never leak into the page's own
    // identity — it's used only for the outbound fetch.
    assert.ok(!category2.url.includes('_seoaudit'), 'stored page url must stay clean of the cache-busting param');
  } finally {
    await site.close();
  }
});
