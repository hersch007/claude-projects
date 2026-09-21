// Regression coverage for cross-page duplicate title/H1 detection
// (annotateDuplicates() in lib/audit-engine.js) — a check that can only be
// made by comparing pages to each other, not from any single page alone.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('two pages sharing an identical title tag are both flagged and cost score', async () => {
  const sameTitle = 'Same Title Across Pages For This Test Site Here';
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      title: sameTitle,
      main: '<h1>Home</h1><a href="/about">About</a><p>Homepage copy long enough to not warn about word count.</p>',
    })),
    '/about': (req, res) => sendHtml(res, page({
      title: sameTitle,
      main: '<h1>About</h1><p>About page copy long enough to not warn about word count on its own here.</p>',
    })),
  });
  try {
    const client = { name: 'Duplicate Title Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const about = result.results.find(p => p.url.endsWith('/about'));
    assert.ok(home.warnings.some(w => w.startsWith('Duplicate title tag')), `homepage should be flagged, got: ${JSON.stringify(home.warnings)}`);
    assert.ok(about.warnings.some(w => w.startsWith('Duplicate title tag')), `about page should be flagged, got: ${JSON.stringify(about.warnings)}`);
    assert.ok(
      result.scoreData.deductions.some(d => d.label.includes('duplicate title tag')),
      'a duplicate title tag shared across pages must cost score'
    );
  } finally {
    await site.close();
  }
});

test('two pages that both have no title are not falsely flagged as duplicates of each other', async () => {
  // findDuplicateGroups() deliberately skips empty values — otherwise every
  // page missing a title (already caught as its own, separate "missing
  // title" issue) would falsely group together as "duplicates".
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, `<!doctype html><html><head></head><body><h1>Home</h1><a href="/about">About</a><p>${'word '.repeat(60)}</p></body></html>`),
    '/about': (req, res) => sendHtml(res, `<!doctype html><html><head></head><body><h1>About</h1><p>${'word '.repeat(60)}</p></body></html>`),
  });
  try {
    const client = { name: 'No Title Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const about = result.results.find(p => p.url.endsWith('/about'));
    assert.ok(!home.warnings.some(w => w.startsWith('Duplicate title tag')), `homepage should not be falsely flagged, got: ${JSON.stringify(home.warnings)}`);
    assert.ok(!about.warnings.some(w => w.startsWith('Duplicate title tag')), `about page should not be falsely flagged, got: ${JSON.stringify(about.warnings)}`);
  } finally {
    await site.close();
  }
});
