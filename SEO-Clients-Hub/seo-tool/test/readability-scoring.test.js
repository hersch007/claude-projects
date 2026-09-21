// Regression coverage for the readability scoring tiers (PR #43): a score
// of 25-29 is "Borderline readability" and doesn't cost score at all; only
// a genuinely low score (<25) is labeled "Difficult to read" and counted.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

// Deliberately synthetic, not real prose — built to hit precise Flesch
// score bands so this test doesn't depend on rewording every time the
// formula or its inputs shift slightly. `hardRatio` sentences (out of 10)
// use dense/technical filler words; the rest use short common ones.
function makeText(hardSentenceCount) {
  const easy = ['the', 'cat', 'sat', 'on', 'a', 'mat', 'and', 'ran', 'to', 'get'];
  const hard = ['certification', 'psychotherapeutic', 'reconsolidation', 'dissociation'];
  function sentence(hardCount) {
    const words = [];
    for (let i = 0; i < 10; i++) words.push(i < hardCount ? hard[i % hard.length] : easy[i % easy.length]);
    return words.join(' ') + '.';
  }
  const sentences = [];
  for (let i = 0; i < 10; i++) sentences.push(sentence(i < hardSentenceCount ? 3 : 2));
  return sentences.join(' ');
}

test('a borderline score (25-29) is labeled distinctly and does not cost score', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: `<h1>Home</h1><p>${makeText(2)}</p>` })), // ~score 25-27
  });
  try {
    const client = { name: 'Borderline Score Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const finding = home.warnings.find(w => w.includes('readability') || w.startsWith('Difficult to read'));
    assert.ok(finding, `expected a readability finding, got warnings: ${JSON.stringify(home.warnings)}`);
    assert.match(finding, /^Borderline readability/, `a 25-29 score must be labeled "Borderline readability", got: ${finding}`);
    assert.ok(
      !result.scoreData.deductions.some(d => d.label.includes('difficult-to-read')),
      'a borderline score must not contribute to the difficult-to-read-copy deduction'
    );
    assert.ok(
      !result.scoreData.deductions.some(d => d.label.includes('other improvement')),
      'a borderline score must not fall into the generic scoring bucket either'
    );
  } finally {
    await site.close();
  }
});

test('a genuinely low score (<25) is labeled "Difficult to read" and does cost score', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({ main: `<h1>Home</h1><p>${makeText(4)}</p>` })), // ~score 15-19
  });
  try {
    const client = { name: 'Difficult Score Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    const finding = home.warnings.find(w => w.startsWith('Difficult to read'));
    assert.ok(finding, `expected a "Difficult to read" finding, got warnings: ${JSON.stringify(home.warnings)}`);
    assert.ok(
      result.scoreData.deductions.some(d => d.label.includes('difficult-to-read')),
      'a genuinely low score must contribute to the difficult-to-read-copy deduction'
    );
  } finally {
    await site.close();
  }
});

test('image alt findings distinguish genuinely missing from present-but-empty', async () => {
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: `<h1>Home</h1><p>${'word '.repeat(60)}</p><img src="/logo.png" alt=""><img src="/hero.png">`,
    })),
  });
  try {
    const client = { name: 'Alt Text Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.equal(home.imagesNoAlt, 1, 'the image with no alt attribute at all should count as missing');
    assert.equal(home.imagesEmptyAlt, 1, 'the image with alt="" should count separately as empty, not missing');
    assert.ok(home.issues.some(i => i.includes('missing alt attribute')), 'missing alt must be a hard issue');
    assert.ok(home.warnings.some(w => w.includes('empty alt text')), 'empty alt must be a softer warning, not conflated with missing');
  } finally {
    await site.close();
  }
});
