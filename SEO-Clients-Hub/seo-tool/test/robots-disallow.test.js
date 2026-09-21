// Regression coverage for the robots.txt Disallow check (fetchDisallowedPrefixes
// in lib/audit-engine.js) — a minimal parser that only honors "Disallow:"
// rules under a wildcard "User-agent: *" block, applied after the crawl to
// flag any already-crawled page whose path matches a disallowed prefix
// ("a page fully built but accidentally blocked from crawling").
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a page matching a wildcard Disallow prefix is flagged and costs score', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/private">Private</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/private': (req, res) => sendHtml(res, page({ main: '<h1>Private</h1><p>Private page copy long enough to not warn about word count on its own here.</p>' })),
    '/robots.txt': (req, res) => { res.writeHead(200, { 'content-type': 'text/plain' }); res.end('User-agent: *\nDisallow: /private'); },
  });
  try {
    const client = { name: 'Robots Disallow Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const privatePage = result.results.find(p => p.url.endsWith('/private'));
    const home = result.results.find(p => p.url === site.url);
    assert.ok(privatePage.issues.includes('Blocked by robots.txt'), `disallowed page should be flagged, got: ${JSON.stringify(privatePage.issues)}`);
    assert.ok(!home.issues.includes('Blocked by robots.txt'), 'the homepage itself is not disallowed and must not be flagged');
    assert.ok(
      result.scoreData.deductions.some(d => d.label.includes('blocked by robots.txt')),
      'a page blocked by robots.txt must cost score'
    );
  } finally {
    await site.close();
  }
});

test('a Disallow rule under a specific (non-wildcard) user-agent block does not apply', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/private">Private</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/private': (req, res) => sendHtml(res, page({ main: '<h1>Private</h1><p>Private page copy long enough to not warn about word count on its own here.</p>' })),
    '/robots.txt': (req, res) => { res.writeHead(200, { 'content-type': 'text/plain' }); res.end('User-agent: SomeOtherBot\nDisallow: /private'); },
  });
  try {
    const client = { name: 'Non-Wildcard Robots Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const privatePage = result.results.find(p => p.url.endsWith('/private'));
    assert.ok(
      !privatePage.issues.includes('Blocked by robots.txt'),
      `a Disallow rule scoped to a different user-agent must not block this page, got: ${JSON.stringify(privatePage.issues)}`
    );
  } finally {
    await site.close();
  }
});

test('a missing robots.txt does not falsely block any page', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/about">About</a><p>Homepage copy long enough to not warn about word count.</p>' })),
    '/about': (req, res) => sendHtml(res, page({ main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>' })),
    // '/robots.txt' intentionally absent -> falls through to the mock site's own 404 handler.
  });
  try {
    const client = { name: 'No Robots Txt Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.ok(
      result.results.every(p => !(p.issues || []).includes('Blocked by robots.txt')),
      `no page should be flagged when robots.txt is missing entirely, got: ${JSON.stringify(result.results.map(p => p.issues))}`
    );
  } finally {
    await site.close();
  }
});
