// Regression coverage for mixed-content resource detection (analyzePage()
// in lib/audit-engine.js) — an http:// image/script/stylesheet loaded on
// an https:// page, which browsers actively block or warn about. The
// check is deliberately scoped to https:// pages only (`if
// (url.startsWith('https://'))`), so this needs a real TLS server to
// exercise at all — see test/helpers/mock-https-site.js.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { runAudit } = require('../lib/audit-engine');
const { createMockHttpsSite } = require('./helpers/mock-https-site');
const { createMockSite, sendHtml, page } = require('./helpers/mock-site');
const { memoryStorage } = require('./helpers/memory-storage');

// The mock HTTPS server uses a self-signed cert; node-fetch defers to
// Node's default TLS verification, which honors this env var. Scoped with
// try/finally in every test below so it never leaks into other files.
const TLS_ENV_VAR = 'NODE_TLS_REJECT_UNAUTHORIZED';

test('an http:// resource loaded on an https:// page is flagged as mixed content and costs score', async () => {
  const site = await createMockHttpsSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: '<img src="http://insecure.example.com/logo.png"><script src="http://insecure.example.com/widget.js"></script><link rel="stylesheet" href="http://insecure.example.com/style.css">',
    })),
  });
  const prevTls = process.env[TLS_ENV_VAR];
  process.env[TLS_ENV_VAR] = '0';
  try {
    const client = { name: 'Mixed Content Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(home, `the https page should be crawled successfully, got results: ${JSON.stringify(result.results)}`);
    const finding = home.issues.find(i => i.includes('mixed-content resource'));
    assert.ok(finding, `expected a mixed-content issue, got: ${JSON.stringify(home.issues)}`);
    assert.match(finding, /^3 mixed-content resource/, `all 3 insecure resources (img/script/stylesheet) should be counted, got: ${finding}`);
    assert.ok(
      result.scoreData.deductions.some(d => d.label.includes('mixed-content')),
      'mixed-content resources on an https page must cost score'
    );
  } finally {
    if (prevTls === undefined) delete process.env[TLS_ENV_VAR]; else process.env[TLS_ENV_VAR] = prevTls;
    await site.close();
  }
});

test('an http:// resource on a plain http:// page is not flagged as mixed content', async () => {
  // The check only makes sense on an https:// page — an all-http:// page
  // has no secure/insecure mismatch to warn about.
  const site = await createMockSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: '<img src="http://insecure.example.com/logo.png">',
    })),
  });
  try {
    const client = { name: 'Http Page No Mixed Content Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(
      !home.issues.some(i => i.includes('mixed-content resource')),
      `an http:// page must never be flagged for mixed content, got: ${JSON.stringify(home.issues)}`
    );
  } finally {
    await site.close();
  }
});

test('an https:// page loading only https:// resources is not falsely flagged', async () => {
  const site = await createMockHttpsSite({
    '/': (req, res) => sendHtml(res, page({
      main: '<h1>Home</h1><p>Homepage copy long enough to not warn about word count on its own.</p>',
      head: '<img src="https://secure.example.com/logo.png"><link rel="stylesheet" href="/local-style.css">',
    })),
  });
  const prevTls = process.env[TLS_ENV_VAR];
  process.env[TLS_ENV_VAR] = '0';
  try {
    const client = { name: 'All Secure Resources Test', url: site.url, max_pages: 5, ignore_paths: [] };
    const result = await runAudit(client, { storage: memoryStorage() });
    const home = result.results.find(p => p.url === site.url);
    assert.ok(home, 'the https page should be crawled successfully');
    assert.ok(
      !home.issues.some(i => i.includes('mixed-content resource')),
      `an https page with no http:// resources must not be falsely flagged, got: ${JSON.stringify(home.issues)}`
    );
  } finally {
    if (prevTls === undefined) delete process.env[TLS_ENV_VAR]; else process.env[TLS_ENV_VAR] = prevTls;
    await site.close();
  }
});
