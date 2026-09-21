// Regression coverage for external-link handling — normalizeUrl() only
// treats a link as "internal" (crawlable, checkable) when its hostname
// matches the audited site's own; anything else must never be crawled and
// must never show up as a broken/blocked internal-link finding, even if it
// happens to be unreachable itself. Uses a second mock server on a
// different loopback address (127.0.0.2) to stand in for a real external
// site, so this is provable without depending on real DNS/network.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a link to an external domain is never crawled and never reported as broken', async () => {
  let externalHits = 0;
  const externalSite = await createMockSite({
    '/': (req, res) => { externalHits++; res.writeHead(404); res.end('Not found'); },
  }, '127.0.0.2');
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `<h1>Home</h1><a href="${externalSite.url}/">External</a><p>Homepage copy long enough to not warn about word count.</p>`,
    })),
  });
  try {
    const client = { name: 'External Link Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(externalHits, 0, 'an external link must never be fetched by the crawler at all');
    assert.equal(result.results.length, 1, 'only the internal homepage should be crawled, not the external site');
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !(home.issues || []).some(i => i.includes(externalSite.url)) && !(home.warnings || []).some(w => w.includes(externalSite.url)),
      'an unreachable external destination must never surface as a broken/blocked internal-link finding'
    );
  } finally {
    await site.close();
    await externalSite.close();
  }
});

test('a mailto: or tel: link is never queued for crawling', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Home</h1><a href="mailto:hello@example.com">Email</a><a href="tel:+15551234567">Call</a><p>Homepage copy long enough to not warn about word count.</p>',
    })),
  });
  try {
    const client = { name: 'Mailto Tel Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(result.results.length, 1, 'mailto:/tel: links must never be queued as pages to crawl');
  } finally {
    await site.close();
  }
});
