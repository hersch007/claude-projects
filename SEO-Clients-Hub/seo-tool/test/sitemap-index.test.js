// Regression coverage for parseSitemap()'s sitemap-index support (lib/
// audit-engine.js) — a sitemap index (`<sitemapindex>`) doesn't list pages
// directly, it lists child sitemaps to fetch and combine, and can itself
// be nested (an index pointing to another index) up to a depth of 3.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

function urlset(base, paths) {
  return `<?xml version="1.0"?><urlset>${paths.map(p => `<url><loc>${base}${p}</loc></url>`).join('')}</urlset>`;
}
function sitemapIndex(locs) {
  return `<?xml version="1.0"?><sitemapindex>${locs.map(l => `<sitemap><loc>${l}</loc></sitemap>`).join('')}</sitemapindex>`;
}

test('a sitemap index pointing to multiple child sitemaps queues pages from all of them', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
    '/sitemap.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(sitemapIndex([`${site.url}/sitemap-a.xml`, `${site.url}/sitemap-b.xml`])); },
    '/sitemap-a.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(urlset(site.url, ['/page-a'])); },
    '/sitemap-b.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(urlset(site.url, ['/page-b'])); },
    '/page-a': (req, res) => sendHtml(res, page({ main: '<h1>Page A</h1><p>Page A copy long enough to not warn about word count on its own here.</p>' })),
    '/page-b': (req, res) => sendHtml(res, page({ main: '<h1>Page B</h1><p>Page B copy long enough to not warn about word count on its own here.</p>' })),
  });
  try {
    const client = { name: 'Sitemap Index Test', url: site.url, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.ok(result.results.some(p => p.url.endsWith('/page-a')), `page discovered only via sitemap-a.xml should be crawled, got: ${JSON.stringify(result.results.map(p => p.url))}`);
    assert.ok(result.results.some(p => p.url.endsWith('/page-b')), `page discovered only via sitemap-b.xml should be crawled, got: ${JSON.stringify(result.results.map(p => p.url))}`);
  } finally {
    await site.close();
  }
});

test('a nested sitemap index (index pointing to another index) is still resolved', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
    '/sitemap.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(sitemapIndex([`${site.url}/sitemap-mid.xml`])); },
    '/sitemap-mid.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(sitemapIndex([`${site.url}/sitemap-leaf.xml`])); },
    '/sitemap-leaf.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(urlset(site.url, ['/page-nested'])); },
    '/page-nested': (req, res) => sendHtml(res, page({ main: '<h1>Nested</h1><p>Nested page copy long enough to not warn about word count on its own here.</p>' })),
  });
  try {
    const client = { name: 'Nested Sitemap Index Test', url: site.url, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.ok(
      result.results.some(p => p.url.endsWith('/page-nested')),
      `a page two levels deep in nested sitemap indexes should still be discovered, got: ${JSON.stringify(result.results.map(p => p.url))}`
    );
  } finally {
    await site.close();
  }
});

test('one child sitemap failing to fetch does not prevent the other child sitemaps from being processed', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
    '/sitemap.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(sitemapIndex([`${site.url}/sitemap-missing.xml`, `${site.url}/sitemap-good.xml`])); },
    // '/sitemap-missing.xml' is intentionally absent -> 404
    '/sitemap-good.xml': (req, res) => { res.writeHead(200, { 'content-type': 'application/xml' }); res.end(urlset(site.url, ['/page-good'])); },
    '/page-good': (req, res) => sendHtml(res, page({ main: '<h1>Good</h1><p>Good page copy long enough to not warn about word count on its own here.</p>' })),
  });
  try {
    const client = { name: 'Partial Sitemap Failure Test', url: site.url, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.ok(
      result.results.some(p => p.url.endsWith('/page-good')),
      `the sitemap that did resolve should still seed its pages despite the other one 404ing, got: ${JSON.stringify(result.results.map(p => p.url))}`
    );
  } finally {
    await site.close();
  }
});
