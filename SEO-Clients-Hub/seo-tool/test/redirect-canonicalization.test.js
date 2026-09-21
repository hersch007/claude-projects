// Regression coverage for the redirect-canonicalization probe in crawl()
// (lib/audit-engine.js) — when a client's configured URL 301/302-redirects
// to a different origin (the classic www vs non-www, or http vs https,
// WordPress case), BASE_URL must be reassigned to the resolved origin
// *before* the crawl queue is built, or every one of the site's own
// internal links (generated using its real, canonical domain) gets
// wrongly rejected as "off-site" and the crawl silently stops after just
// the homepage.
//
// A redirect to a different port on the same 127.0.0.1 loopback address
// still counts as a different origin (origin includes the port), which
// lets this be tested without any real DNS/network dependency.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a configured URL that redirects to a different origin is crawled using the resolved domain, not just its homepage', async () => {
  const realSite = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/about">About</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/about': (req, res) => sendHtml(res, page({ main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>' })),
  });
  const redirectingSite = await createMockSite({
    '/': (req, res) => { res.writeHead(301, { Location: realSite.url + '/' }); res.end(); },
  });
  try {
    const client = { name: 'Redirect Canonicalization Test', url: redirectingSite.url, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === realSite.url);
    const about = result.results.find(p => p.url.endsWith('/about'));
    assert.ok(home, `the resolved domain's homepage should be crawled, got urls: ${JSON.stringify(result.results.map(p => p.url))}`);
    assert.ok(about, 'a page linked from the resolved domain must be discovered and crawled too, not rejected as off-site');
  } finally {
    await redirectingSite.close();
    await realSite.close();
  }
});

test('a configured URL that does not redirect is crawled using the originally configured origin, unchanged', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/about">About</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/about': (req, res) => sendHtml(res, page({ main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>' })),
  });
  try {
    const client = { name: 'No Redirect Test', url: site.url, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.ok(result.results.every(p => p.url.startsWith(site.url)), `every crawled page should stay on the originally configured origin, got: ${JSON.stringify(result.results.map(p => p.url))}`);
    assert.equal(result.results.length, 2, 'both pages on the non-redirecting site should be crawled normally');
  } finally {
    await site.close();
  }
});

test('a configured URL that is entirely unreachable falls back gracefully instead of crashing', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
  });
  const deadUrl = site.url;
  await site.close(); // nothing is listening on this URL anymore

  const client = { name: 'Unreachable Redirect Probe Test', url: deadUrl, max_pages: 5, ignore_paths: [] };
  const result = await runAudit(client, { storage: memoryStorage() });
  assert.equal(result.results.length, 1, 'a single error result for the homepage should be recorded, not a crash');
  assert.ok(result.results[0].error, 'the homepage attempt should be recorded as an error');
});
