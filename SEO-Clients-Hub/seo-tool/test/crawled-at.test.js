// Regression coverage for the exact crawl timestamp (PR #43) — separate
// from the plain YYYY-MM-DD `date` field (which history/keyword storage
// and the docx filename key on and must stay untouched), used only to
// disambiguate which of possibly several same-day runs a report reflects.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { buildDocxReport } = require('../lib/build-docx-report');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('runAudit returns both a plain date and a full crawledAt timestamp', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><p>Some homepage copy long enough to not warn about word count.</p>' })),
  });
  try {
    const client = { name: 'Timestamp Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    assert.match(result.date, /^\d{4}-\d{2}-\d{2}$/, 'date must stay a plain YYYY-MM-DD string');
    assert.ok(!Number.isNaN(new Date(result.crawledAt).getTime()), 'crawledAt must be a valid parseable timestamp');
    assert.ok(result.crawledAt.startsWith(result.date), 'crawledAt should share the same calendar date as date');
  } finally {
    await site.close();
  }
});

test('crawledAt renders as a time on the docx cover, alongside the date', async () => {
  const client = { name: 'Docx Time Test', url: 'https://example.com' };
  const provider = { name: 'GroupRB', brand: '#003366', email: 'hello@example.com' };
  const results = [{ url: 'https://example.com', title: 'Home', h1Count: 1, metaDesc: 'desc', schemaTypes: [], imagesNoAlt: 0, issues: [], warnings: [], error: false }];
  const scoreData = { score: 90, deductions: [] };

  const buffer = await buildDocxReport({
    client, results, scoreData, provider,
    date: '2026-09-21', crawledAt: '2026-09-21T18:42:00.000Z',
    narrative: null, changes: null, pageSpeed: null, gbp: null, dominantPhone: null, gscKeywords: null,
  });
  assert.ok(buffer.length > 0, 'docx should generate successfully with crawledAt set');

  // Also confirm it still generates fine with crawledAt absent (older
  // in-memory runs from before this field existed, or any caller that
  // omits it) — must not throw.
  const bufferNoTime = await buildDocxReport({
    client, results, scoreData, provider,
    date: '2026-09-21', crawledAt: undefined,
    narrative: null, changes: null, pageSpeed: null, gbp: null, dominantPhone: null, gscKeywords: null,
  });
  assert.ok(bufferNoTime.length > 0, 'docx should still generate successfully without crawledAt');
});
