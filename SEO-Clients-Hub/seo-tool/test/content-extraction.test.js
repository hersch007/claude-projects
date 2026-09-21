// Regression coverage for the word-count/bodyText extraction fixes
// (PRs #39, #40, #41, #42) — everything that decides what text actually
// counts as a page's real content for word count, readability, and the
// narrative LLM pass's bodyText field.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

test('only the first <header> is stripped, not a WooCommerce products-header holding real copy', async () => {
  // PR #39: WooCommerce's default archive template wraps a category's own
  // description inside <header class="woocommerce-products-header">.
  // Blanket-removing every <header> deleted that real copy along with the
  // site's actual chrome header.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/category">Category</a><p>Some homepage copy that is long enough on its own to not warn.</p>' })),
    '/category': (req, res) => sendHtml(res, page({
      main: `
        <header class="woocommerce-products-header">
          <h1 class="woocommerce-products-header__title page-title">EMDR Resources</h1>
          <div class="term-description"><p>${'word '.repeat(150)}</p></div>
        </header>
        <ul class="products"><li class="product"><h2 class="woocommerce-loop-product__title">Book A</h2></li></ul>
      `,
    })),
  });
  try {
    const client = { name: 'Header Test', url: site.url, max_pages: 10, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const category = result.results.find(p => p.url.endsWith('/category'));
    assert.ok(category.wordCount > 100, `category description must survive header removal, got wordCount=${category.wordCount}`);
    assert.ok(!category.warnings.some(w => w.startsWith('Low word count')), 'should not be flagged as thin content');
  } finally {
    await site.close();
  }
});

test('product-card boilerplate is excluded from word count/readability', async () => {
  // PR #41: a homepage's embedded shop/product-teaser widget (price, "Add
  // to cart") was still counted as body content even after PR #39/#40,
  // distorting both word count and readability.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `
        <h1>Home</h1>
        <p>Real homepage copy that describes the practice in plain, ordinary sentences for visitors.</p>
        <ul class="products columns-3">
          <li class="product"><h2 class="woocommerce-loop-product__title">Queering EMDR</h2><span class="price">$24.99</span><a class="add_to_cart_button">Add to cart</a></li>
        </ul>
      `,
    })),
  });
  try {
    const client = { name: 'Product Card Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(!home.bodyText.includes('Add to cart'), 'product-card boilerplate must not appear in bodyText');
    assert.ok(!home.bodyText.includes('24.99'), 'product price must not appear in bodyText');
  } finally {
    await site.close();
  }
});

test('a single product page keeps its own real content (not gutted by the product-card exclusion)', async () => {
  // The product-card exclusion above is scoped to `.products .product`
  // specifically — WooCommerce also wraps a SINGLE product page's entire
  // real content in its own top-level <div class="product">, which must
  // not be excluded the same way (that would reproduce the PR #39 bug in
  // a new spot).
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: '<h1>Home</h1><a href="/product/queering-emdr">Book</a><p>Homepage copy long enough to not warn on its own here today.</p>' })),
    '/product/queering-emdr': (req, res) => sendHtml(res, page({
      main: `
        <div class="product" id="product-123">
          <div class="summary entry-summary">
            <h1 class="product_title">Queering EMDR</h1>
            <div class="woocommerce-product-details__short-description"><p>${'word '.repeat(120)}</p></div>
          </div>
        </div>
      `,
    })),
  });
  try {
    const client = { name: 'Single Product Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const productPage = result.results.find(p => p.url.endsWith('/product/queering-emdr'));
    assert.ok(productPage.wordCount > 100, `single product page's own description must survive, got wordCount=${productPage.wordCount}`);
  } finally {
    await site.close();
  }
});

test('a punctuation-free badge/specialty list gets its own sentence boundaries', async () => {
  // PR #40/#42: a badge row or specialty list with no periods merges into
  // one giant run-on "sentence" once the DOM flattens to plain text,
  // which can drag the Flesch score to an implausible negative number
  // even for perfectly reasonable prose. <li> items and leaf <div>s both
  // need this — <div> specifically because that's what a stats/badge row
  // is almost always built from.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `
        <h1>Home</h1>
        <p>Stephanie provides trauma-informed therapy for adults navigating anxiety and dissociation.</p>
        <ul class="specialties">
          <li>Anxiety and Overthinking</li>
          <li>Religious Deconstruction</li>
        </ul>
        <div class="stats-badges">
          <div class="badge">15+ Years Experience</div>
          <div class="badge">EMDRIA Certified</div>
        </div>
        <p>Sessions are offered in a warm, collaborative space designed to support real healing.</p>
      `,
    })),
  });
  try {
    const client = { name: 'Badge Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(home.bodyText.includes('Experience.'), 'a leaf div should get its own sentence boundary');
    assert.ok(home.bodyText.includes('Overthinking.'), 'a list item should get its own sentence boundary');
    // Deliberately not asserting on the overall readability score here —
    // with a sample this short, jargon density alone can still tip it
    // negative regardless of correct punctuation. The regression this
    // test guards is boundary insertion (asserted above), not the score;
    // see readability-scoring.test.js for score-band coverage.
  } finally {
    await site.close();
  }
});

test('an inline <span> used for styling mid-sentence is not corrupted by the leaf-div rule', async () => {
  // The leaf-div fix deliberately does NOT extend to <span>, since that's
  // routinely used for inline styling inside a real sentence — punctuating
  // every leaf span would break normal prose.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `<h1>Home</h1><p>Sessions use a <span class="highlight">trauma-informed</span> approach designed to support healing over time for every client we see.</p>`,
    })),
  });
  try {
    const client = { name: 'Span Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      home.bodyText.includes('a trauma-informed approach designed'),
      `inline span text must stay part of its surrounding sentence, got: ${home.bodyText}`
    );
    assert.ok(!home.bodyText.includes('trauma-informed.'), 'must not insert a stray period after inline span content');
  } finally {
    await site.close();
  }
});
