// Regression coverage for orphan-page detection — a page can be discovered
// via sitemap.xml (so it gets crawled and audited) while still being
// unreachable through the site's own navigation, which is a real, separate
// SEO problem worth flagging on its own.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a page found only via sitemap.xml, with no internal links pointing to it, is flagged as an orphan', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
    '/sitemap.xml': (req, res) => {
      res.writeHead(200, { 'content-type': 'application/xml' });
      res.end(`<?xml version="1.0"?><urlset><url><loc>${site.url}/</loc></url><url><loc>${site.url}/orphan</loc></url></urlset>`);
    },
    '/orphan': (req, res) => sendHtml(res, page({ main: '<h1>Orphan</h1><p>Orphan page copy long enough to not warn about word count on its own.</p>' })),
  });
  try {
    const client = { name: 'Orphan Page Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const orphan = result.results.find(p => p.url.endsWith('/orphan'));
    assert.ok(orphan, 'the sitemap-only page should still be crawled');
    assert.ok(
      orphan.warnings.includes('Orphan page (no internal links point to it)'),
      `a page with no inbound internal links should be flagged as an orphan, got: ${JSON.stringify(orphan.warnings)}`
    );
  } finally {
    await site.close();
  }
});

test('the homepage itself is never flagged as an orphan, even though nothing links back to it', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/about">About</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/about': (req, res) => sendHtml(res, page({ main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>' })),
  });
  try {
    const client = { name: 'Homepage Not Orphan Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !home.warnings.includes('Orphan page (no internal links point to it)'),
      `the homepage must never be flagged as its own orphan, got: ${JSON.stringify(home.warnings)}`
    );
  } finally {
    await site.close();
  }
});
