// Regression coverage for cross-page near-duplicate BODY content detection
// (findNearDuplicateContentGroups() / annotateDuplicates() in
// lib/audit-engine.js) — the classic "doorway page" pattern, where a page
// has a perfectly unique title/meta/H1 but its main copy is a near-verbatim
// copy of another page's with only a city or product name swapped. Only
// findable by comparing pages to each other, same as duplicate title/H1.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

// ~170 words, well over the 150-word minWords floor, with a {{CITY}}
// placeholder swapped in twice — enough shared 8-word shingles to clear the
// 0.75 similarity threshold even with those two words different.
function doorwayCopy(city) {
  return `<h1>Emergency Plumbing in ${city}</h1><p>Looking for fast, reliable emergency plumbing service in ${city}? Our licensed
  plumbers are available twenty four hours a day, seven days a week, to handle burst pipes, overflowing toilets,
  water heater failures, and every other plumbing emergency you can imagine. We arrive fully stocked with the parts
  and tools needed to fix most problems on the very first visit, so you are not left waiting around for a second
  appointment while your home floods or your family goes without hot water. Every technician on our team is
  background checked, drug tested, and trained to the highest industry standards, and we always provide a clear,
  upfront price before any work begins so there are never any surprises on your final bill. We proudly serve
  homeowners and business owners alike, and we stand behind every repair with a satisfaction guarantee. Call us
  any time, day or night, and a real person will answer the phone and get a truck on the way to you right away.
  Trust the team your neighbors already trust for dependable, honest plumbing work done right the first time.</p>`;
}

test('two pages with near-identical templated body copy (city swapped) are both flagged and cost score', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      title: 'Emergency Plumber Austin',
      main: `<a href="/plumber-houston">Houston</a>${doorwayCopy('Austin')}`,
    })),
    '/plumber-houston': (req, res) => sendHtml(res, page({
      title: 'Emergency Plumber Houston',
      main: doorwayCopy('Houston'),
    })),
  });
  try {
    const client = { name: 'Doorway Page Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const houston = result.results.find(p => p.url.endsWith('/plumber-houston'));
    assert.ok(home, `homepage should crawl successfully, got: ${JSON.stringify(result.results)}`);
    assert.ok(houston, `houston page should crawl successfully, got: ${JSON.stringify(result.results)}`);
    assert.ok(home.warnings.some(w => w.startsWith('Near-duplicate content')), `homepage should be flagged, got: ${JSON.stringify(home.warnings)}`);
    assert.ok(houston.warnings.some(w => w.startsWith('Near-duplicate content')), `houston page should be flagged, got: ${JSON.stringify(houston.warnings)}`);
    assert.ok(
      result.scoreData.deductions.some(d => d.label.includes('near-duplicate body content')),
      'near-duplicate body content shared across pages must cost score'
    );
  } finally {
    await site.close();
  }
});

test('two pages with genuinely different, substantial content are not flagged', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `<a href="/about">About</a><p>${'Our founder started this company in a small garage over twenty years ago, building custom furniture piece by piece for friends and family before word of mouth turned it into a full workshop. '.repeat(6)}</p>`,
    })),
    '/about': (req, res) => sendHtml(res, page({
      main: `<p>${'Return policy: items may be exchanged within thirty days of purchase provided the original receipt is presented and the item is unused and in its original packaging with all tags attached. '.repeat(6)}</p>`,
    })),
  });
  try {
    const client = { name: 'Distinct Content Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const about = result.results.find(p => p.url.endsWith('/about'));
    assert.ok(!home.warnings.some(w => w.startsWith('Near-duplicate content')), `homepage should not be falsely flagged, got: ${JSON.stringify(home.warnings)}`);
    assert.ok(!about.warnings.some(w => w.startsWith('Near-duplicate content')), `about page should not be falsely flagged, got: ${JSON.stringify(about.warnings)}`);
  } finally {
    await site.close();
  }
});

test('short pages under the word-count floor are not flagged even if identical', async () => {
  // findNearDuplicateContentGroups() deliberately skips pages under
  // minWords — near-empty pages produce meaningless similarity scores, and
  // this case is already covered by the separate "Low word count" check.
  const shortCopy = '<p>Thanks for visiting. Give us a call to learn more about what we offer.</p>';
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: `<h1>Home</h1><a href="/about">About</a>${shortCopy}` })),
    '/about': (req, res) => sendHtml(res, page({ main: `<h1>About</h1>${shortCopy}` })),
  });
  try {
    const client = { name: 'Short Page Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const about = result.results.find(p => p.url.endsWith('/about'));
    assert.ok(!home.warnings.some(w => w.startsWith('Near-duplicate content')), `homepage should not be flagged, got: ${JSON.stringify(home.warnings)}`);
    assert.ok(!about.warnings.some(w => w.startsWith('Near-duplicate content')), `about page should not be flagged, got: ${JSON.stringify(about.warnings)}`);
  } finally {
    await site.close();
  }
});
