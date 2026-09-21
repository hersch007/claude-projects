// Regression coverage for a real, confirmed production bug: BASE_URL was
// set directly from client.url (`client.url.replace(/\/$/, '')`) with no
// case normalization, while every discovered link is normalized through
// normalizeUrl()'s `new URL(...).href`, which the URL spec always
// lowercases the hostname of. A client onboarded with any uppercase letter
// in their domain (e.g. "https://Maxwellsplumbing.com", typed exactly as
// the business's own name is capitalized) made every single
// `abs.startsWith(BASE_URL)` link-filter comparison fail silently — every
// real internal link got treated as "external" and dropped, even on a
// site with a completely normal, real, 40+ page nav. The crawl still
// succeeded (homepage fetches fine, no errors), so the report looked like
// a clean pass on a 1-page site — max_pages was a red herring; raising it
// changed nothing, because the queue was empty after page 1 regardless of
// the cap.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('a client URL with uppercase letters in the hostname still discovers and crawls linked pages', async () => {
  // Bound to the hostname "localhost" (not a bare IP) specifically because
  // this bug is about hostname *case*, which a numeric IP can't exhibit.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: `<h1>Home</h1><a href="${site.url}/about">About</a><p>Homepage copy long enough to not warn about word count.</p>` })),
    '/about': (req, res) => sendHtml(res, page({ main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>' })),
  }, 'localhost');
  try {
    // The page's own nav emits the real, lowercase hostname (exactly like
    // WordPress/any real site does) — client.url is deliberately the same
    // origin with an uppercased hostname, exactly like a business owner
    // typing "https://Maxwellsplumbing.com" during onboarding.
    const configuredUrl = site.url.replace('localhost', 'LOCALHOST');
    const client = { name: 'Uppercase Hostname Test', url: configuredUrl, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.ok(
      result.results.some(p => p.url.endsWith('/about')),
      `a real internal link must still be discovered when client.url's hostname case differs from the page's own links, got: ${JSON.stringify(result.results.map(p => p.url))}`
    );
    assert.equal(result.results.length, 2, 'both the homepage and the linked page should be crawled');
  } finally {
    await site.close();
  }
});
