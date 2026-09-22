// Regression coverage for rich-result eligibility (richResultGapsFor() /
// analyzePage()'s schema loop in lib/audit-engine.js) — a page can have
// present, valid JSON-LD schema and still not qualify for the rich result
// it's aiming for (star ratings, an FAQ dropdown, breadcrumb navigation)
// if it's missing the specific fields Google requires for that type.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

function jsonLd(obj) {
  return `<script type="application/ld+json">${JSON.stringify(obj)}</script>`;
}

test('an FAQPage schema with no valid Question/Answer pairs is flagged as rich-result incomplete', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>FAQ</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: jsonLd({ '@context': 'https://schema.org', '@type': 'FAQPage', mainEntity: [] }),
    })),
  });
  try {
    const client = { name: 'FAQ Gap Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(home, `homepage should crawl successfully, got: ${JSON.stringify(result.results)}`);
    const finding = home.warnings.find(w => w.startsWith('Rich result schema incomplete'));
    assert.ok(finding, `expected a rich-result warning, got: ${JSON.stringify(home.warnings)}`);
    assert.match(finding, /FAQPage missing valid Question\/Answer pairs/, `expected FAQPage gap text, got: ${finding}`);
    assert.ok(
      result.scoreData.deductions.some(d => d.label.includes('rich result')),
      'an incomplete rich-result schema must cost score'
    );
  } finally {
    await site.close();
  }
});

test('a complete FAQPage schema is not flagged', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>FAQ</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: jsonLd({
        '@context': 'https://schema.org', '@type': 'FAQPage',
        mainEntity: [{ '@type': 'Question', name: 'Do you offer emergency service?', acceptedAnswer: { '@type': 'Answer', text: 'Yes, 24/7.' } }],
      }),
    })),
  });
  try {
    const client = { name: 'FAQ Complete Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !home.warnings.some(w => w.startsWith('Rich result schema incomplete')),
      `a complete FAQPage schema must not be flagged, got: ${JSON.stringify(home.warnings)}`
    );
  } finally {
    await site.close();
  }
});

test('a BreadcrumbList schema with no itemListElement is flagged', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Category</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: jsonLd({ '@context': 'https://schema.org', '@type': 'BreadcrumbList', itemListElement: [] }),
    })),
  });
  try {
    const client = { name: 'Breadcrumb Gap Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const finding = home.warnings.find(w => w.startsWith('Rich result schema incomplete'));
    assert.ok(finding, `expected a rich-result warning, got: ${JSON.stringify(home.warnings)}`);
    assert.match(finding, /BreadcrumbList missing itemListElement/, `expected BreadcrumbList gap text, got: ${finding}`);
  } finally {
    await site.close();
  }
});

test('an aggregateRating missing a review count is flagged, nested inside a LocalBusiness block', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Reviews</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: jsonLd({
        '@context': 'https://schema.org', '@type': 'ProfessionalService',
        telephone: '555-123-4567', address: '123 Main St', priceRange: '$$',
        aggregateRating: { '@type': 'AggregateRating', ratingValue: '4.8' },
      }),
    })),
  });
  try {
    const client = { name: 'Rating Gap Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const finding = home.warnings.find(w => w.startsWith('Rich result schema incomplete'));
    assert.ok(finding, `expected a rich-result warning, got: ${JSON.stringify(home.warnings)}`);
    assert.match(finding, /AggregateRating missing a rating value or review count/, `expected AggregateRating gap text, got: ${finding}`);
  } finally {
    await site.close();
  }
});

test('a page with no schema at all is not flagged for rich-result gaps (only for missing schema entirely)', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
    })),
  });
  try {
    const client = { name: 'No Schema Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !home.warnings.some(w => w.startsWith('Rich result schema incomplete')),
      `a page with no schema must not be flagged for rich-result gaps, got: ${JSON.stringify(home.warnings)}`
    );
    assert.ok(home.issues.includes('No JSON-LD schema found'), 'should still be flagged for having no schema at all');
  } finally {
    await site.close();
  }
});
