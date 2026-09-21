// Regression coverage for broken/blocked internal link detection
// (PRs #37, #39, #40) — destination + status visibility, single-fetch
// dedup, and the 403-retry logic that tells a genuine WAF/security block
// apart from an actually-missing page.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a broken link\'s finding names the exact destination and HTTP status', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/gone">Old link</a><p>Homepage copy long enough to not warn about word count on its own.</p>' })),
    // '/gone' is intentionally absent -> falls through to the mock site's
    // own 404 handler.
  });
  try {
    const client = { name: 'Broken Link Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const finding = home.issues.find(i => i.startsWith('Links to'));
    assert.ok(finding, 'a broken-link issue should be recorded');
    assert.match(finding, /\/gone/, 'the finding must name the actual destination path');
    assert.match(finding, /HTTP 404/, 'the finding must include the response status');
  } finally {
    await site.close();
  }
});

test('a destination linked from many pages is only fetched once', async () => {
  const N = 8;
  let hitCount = 0;
  const routes = {
    '/broken-everywhere': (req, res) => { hitCount++; res.writeHead(404); res.end(); },
  };
  for (let i = 0; i < N; i++) {
    routes[`/page-${i}`] = (req, res) => sendHtml(res, page({
      main: `<h1>Page ${i}</h1><p>${'word '.repeat(60)}</p><a href="/broken-everywhere">Broken</a>`,
    }));
  }
  routes['/'] = (req, res) => sendHtml(res, page({
    main: `<h1>Home</h1>${Array.from({ length: N }, (_, i) => `<a href="/page-${i}">Page ${i}</a>`).join(' ')}<p>${'word '.repeat(60)}</p>`,
  }));
  const site = await createMockSite(routes);
  try {
    const client = { name: 'Single Fetch Test', url: site.url, max_pages: 20, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.equal(hitCount, 1, `the shared destination should be fetched exactly once regardless of ${N} linking pages, got ${hitCount} hits`);
    const pagesFlagging = result.results.filter(p => (p.issues || []).some(i => i.includes('/broken-everywhere')));
    assert.equal(pagesFlagging.length, N, `all ${N} source pages should still be attributed as linking to it`);
  } finally {
    await site.close();
  }
});

test('a 403 that recovers on retry without the cache-buster is treated as reachable (no false finding)', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/privacy-policy">Privacy</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/privacy-policy': (req, res, query) => {
      // Blocks any request carrying the cache-busting param, allows the
      // plain retry through — simulates a WAF flagging the query string
      // itself, not the page.
      if (query.get('_seoaudit')) { res.writeHead(403); res.end('Forbidden'); return; }
      sendHtml(res, page({ main: '<h1>Privacy Policy</h1><p>Real policy text here.</p>' }));
    },
  });
  try {
    const client = { name: 'Recovers On Retry Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const privacyPage = result.results.find(p => p.url.endsWith('/privacy-policy'));
    assert.ok(privacyPage && !privacyPage.error, 'the page should be crawled successfully once the plain retry succeeds');
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !(home.warnings || []).some(w => w.includes('blocked from reaching')) && !(home.issues || []).some(i => i.includes('broken internal')),
      'no false broken/blocked finding should be recorded when the retry recovers'
    );
  } finally {
    await site.close();
  }
});

test('a 403 that persists after the retry is reported as "blocked", not "broken", and does not cost score', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/privacy-policy">Privacy</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/privacy-policy': (req, res) => { res.writeHead(403); res.end('Forbidden'); }, // always 403
  });
  try {
    const client = { name: 'Persistent Block Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const blockedFinding = (home.warnings || []).find(w => w.includes('blocked from reaching'));
    assert.ok(blockedFinding, 'a persistent 403 should be reported as a "blocked" warning');
    assert.match(blockedFinding, /HTTP 403/, 'the blocked finding should include the status');
    assert.ok(!(home.issues || []).some(i => i.includes('broken internal')), 'must not also be reported as a genuine broken link');
    assert.ok(
      !result.scoreData.deductions.some(d => d.label.includes('other improvement')),
      'an unconfirmed blocked link must not fall into the generic scoring bucket and cost points'
    );
  } finally {
    await site.close();
  }
});
