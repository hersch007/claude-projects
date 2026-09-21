// Regression coverage for false positives in the "Heading hierarchy skips
// a level" check (PRs #39, #40) — structural/chrome headings that aren't
// part of a page's real content outline.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('WooCommerce product-card H3s do not trigger a false "skips H2" warning', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `
        <h1>Category</h1>
        <p>Real intro copy for this category page, long enough to not warn on word count here.</p>
        <ul class="products">
          <li class="product"><h3 class="woocommerce-loop-product__title">Book A</h3></li>
          <li class="product"><h3 class="woocommerce-loop-product__title">Book B</h3></li>
        </ul>
      `,
    })),
  });
  try {
    const client = { name: 'Product H3 Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(!home.warnings.some(w => w.startsWith('Heading hierarchy skips')), `should not warn on product-card H3s, got: ${JSON.stringify(home.warnings)}`);
  } finally {
    await site.close();
  }
});

test('footer widget H4s do not trigger a false "skips H3" warning', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `
        <h1>Queering EMDR</h1>
        <p>Real product description copy, long enough to not warn on word count on its own.</p>
        <div class="related products"><li class="product"><h3 class="woocommerce-loop-product__title">Related Book</h3></li></div>
      `,
      footer: '<h4 class="widget-title">Quick Links</h4><h4 class="widget-title">Newsletter</h4>',
    })),
  });
  try {
    const client = { name: 'Footer H4 Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(!home.warnings.some(w => w.startsWith('Heading hierarchy skips')), `should not warn on footer widget H4s, got: ${JSON.stringify(home.warnings)}`);
  } finally {
    await site.close();
  }
});

test('a genuine orphan H3 with no H2 on the page still gets flagged', async () => {
  // The exclusions above must be scoped narrowly — a real content H3 that
  // isn't inside a product card, .products grid, nav, or footer should
  // still trip the check.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `<h1>Home</h1><p>Real page copy, long enough to not warn on word count on its own here.</p><h3>An orphaned subsection heading</h3><p>More text.</p>`,
    })),
  });
  try {
    const client = { name: 'Genuine Orphan H3 Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(home.warnings.some(w => w.startsWith('Heading hierarchy skips H2')), `a real orphan H3 must still be flagged, got: ${JSON.stringify(home.warnings)}`);
  } finally {
    await site.close();
  }
});
