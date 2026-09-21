// Regression coverage for a client's ignore_paths config (exposed for
// editing in PR #43) — a path listed there must be skipped both as a
// crawled page AND as a link destination other pages are checked against,
// so a manually-verified false positive can be fully suppressed rather
// than just reworded.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a path in ignore_paths is never crawled and never checked as a link destination', async () => {
  let ignoredHits = 0;
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/privacy-policy">Privacy</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/privacy-policy': (req, res) => { ignoredHits++; res.writeHead(403); res.end('Forbidden'); },
  });
  try {
    const client = { name: 'Ignore Paths Test', url: site.url, max_pages: 5, ignore_paths: ['/privacy-policy'] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(ignoredHits, 0, 'an ignored path must never be fetched at all');
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !(home.warnings || []).some(w => w.includes('privacy-policy')) && !(home.issues || []).some(i => i.includes('privacy-policy')),
      'an ignored path must not appear as any kind of finding'
    );
  } finally {
    await site.close();
  }
});

test('a client with no ignore_paths still checks the same link normally', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/privacy-policy">Privacy</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/privacy-policy': (req, res) => { res.writeHead(403); res.end('Forbidden'); },
  });
  try {
    const client = { name: 'No Ignore Paths Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      (home.warnings || []).some(w => w.includes('blocked from reaching')),
      'without ignore_paths, the same destination should still be checked and flagged'
    );
  } finally {
    await site.close();
  }
});
