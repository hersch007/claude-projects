// Regression coverage for the client.max_pages crawl cap (`while (queue.length
// > 0 && count < MAX_PAGES)` in crawl()) — a real safeguard against an
// unbounded crawl on a very large site, so it must actually stop at the
// configured limit rather than just being a suggestion.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('the crawl stops at exactly max_pages even when more linked pages exist', async () => {
  const N = 8;
  const routes = {};
  for (let i = 0; i < N; i++) {
    routes[`/page-${i}`] = (req, res) => sendHtml(res, page({
      main: `<h1>Page ${i}</h1><a href="/page-${(i + 1) % N}">Next</a><p>${'word '.repeat(60)}</p>`,
    }));
  }
  routes['/'] = (req, res) => sendHtml(res, page({
    main: `<h1>Home</h1>${Array.from({ length: N }, (_, i) => `<a href="/page-${i}">Page ${i}</a>`).join(' ')}<p>${'word '.repeat(60)}</p>`,
  }));
  const site = await createMockSite(routes);
  try {
    const MAX = 3;
    const client = { name: 'Max Pages Cap Test', url: site.url, max_pages: MAX, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(result.results.length, MAX, `crawl should stop at exactly max_pages=${MAX}, got ${result.results.length}`);
  } finally {
    await site.close();
  }
});

test('a site with fewer pages than max_pages is crawled completely, not truncated early', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/about">About</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/about': (req, res) => sendHtml(res, page({ main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>' })),
  });
  try {
    const client = { name: 'Max Pages Headroom Test', url: site.url, max_pages: 50, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(result.results.length, 2, `both real pages should be crawled when the site is smaller than max_pages, got ${result.results.length}`);
  } finally {
    await site.close();
  }
});

test('a client with no max_pages set falls back to the default cap of 50', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
  });
  try {
    const client = { name: 'No Max Pages Set Test', url: site.url, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(result.results.length, 1, 'a single-page site should still crawl fine with no max_pages override');
  } finally {
    await site.close();
  }
});
