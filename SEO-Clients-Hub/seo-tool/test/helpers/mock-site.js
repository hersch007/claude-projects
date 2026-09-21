// A tiny in-process HTTP server for feeding real markup through runAudit()
// without touching the network — every test that needs a "live site"
// starts one of these instead of hand-rolling http.createServer +
// process management (which is exactly what made ad-hoc debugging this
// session slower than it needed to be: fixed ports collide across runs).
// listen(0) lets the OS pick a free port, so tests never collide with each
// other or with anything else running locally.
const http = require('http');

// routes: { [path]: (req, res, query) => void }. A route not found in the
// map returns a plain 404 — good enough to also double as "this link is
// genuinely broken" in tests that need one.
function createMockSite(routes) {
  const server = http.createServer((req, res) => {
    const [urlPath, qs] = req.url.split('?');
    const handler = routes[urlPath];
    if (!handler) {
      res.writeHead(404, { 'content-type': 'text/html' });
      res.end('Not found');
      return;
    }
    handler(req, res, new URLSearchParams(qs || ''));
  });
  return new Promise((resolve, reject) => {
    server.on('error', reject);
    server.listen(0, '127.0.0.1', () => {
      const { port } = server.address();
      resolve({
        url: `http://127.0.0.1:${port}`,
        close: () => new Promise(r => server.close(r)),
      });
    });
  });
}

function sendHtml(res, html, status = 200) {
  res.writeHead(status, { 'content-type': 'text/html' });
  res.end(html);
}

// Wraps a body in the minimal chrome shape every real theme has: a site
// header (with nav) before the real content, and a footer after it. Tests
// that need something specific in the header/footer pass it in; otherwise
// this is what "a normal page" looks like for every test in this suite.
function page({ title = 'Test Page', head = '', header = '<nav>Nav</nav>', main, footer = 'Footer' } = {}) {
  return `<!doctype html><html><head><title>${title}</title><meta name="description" content="A real description.">${head}</head><body>
  <header class="site-header">${header}</header>
  <main>${main}</main>
  <footer>${footer}</footer>
  </body></html>`;
}

module.exports = { createMockSite, sendHtml, page };
