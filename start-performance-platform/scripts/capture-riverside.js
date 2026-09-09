// Requires: npm install puppeteer-core@23 (and jimp@0.22 for the crop script) in the folder you run from.
// Usage: node capture-riverside.js  — opens Chrome, you log in as Dana once, it captures all screens.
// Opens a real Chrome window, waits for Richard to log in as vendor, then saves
// full-resolution PNGs of the Clinton app screens for website / LinkedIn use.
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BASE = 'https://riverside.startperformance.com';
const OUT  = 'C:/Users/richa/Documents/Claude Projects/start-performance-platform/screenshots/riverside-2026-09-08';
fs.mkdirSync(OUT, { recursive: true });

const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const log = (m) => console.log(new Date().toISOString().slice(11, 19) + '  ' + m);

(async () => {
  const browser = await puppeteer.launch({
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    headless: false,
    defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 2 },
    args: ['--window-size=1460,1000', '--no-first-run', '--no-default-browser-check', '--host-resolver-rules=MAP riverside.startperformance.com 64.247.178.244'],
    ignoreHTTPSErrors: true,
  });
  const page = (await browser.pages())[0];

  // 1. Public service request form needs no login — grab it first.
  await page.goto(BASE + '/service-request/', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(1000);
  await page.screenshot({ path: path.join(OUT, '01-public-service-request-form.png'), fullPage: true });
  log('saved 01-public-service-request-form.png');

  // 2. Vendor login — Richard types the PIN in this window.
  await page.goto(BASE + '/sp-login/', { waitUntil: 'networkidle2', timeout: 60000 });
  log('WAITING for login in the Chrome window (up to 5 minutes)...');
  await page.waitForFunction(() => location.pathname.indexOf('/sp-app') === 0, { timeout: 300000, polling: 1000 });
  log('logged in');

  // 3. Dashboard — wait for the AI card to finish if it auto-refreshes.
  await page.goto(BASE + '/sp-app/', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(1500);
  try {
    await page.waitForFunction(() => {
      const s = document.getElementById('sp-ai-ca-status');
      const o = document.getElementById('sp-ai-ca-output');
      return o && o.style.display !== 'none' && o.textContent.trim().length > 50 && (!s || s.style.display === 'none');
    }, { timeout: 150000, polling: 1000 });
  } catch (e) { log('AI card did not finish in 150s; capturing anyway'); }
  await sleep(500);
  await page.screenshot({ path: path.join(OUT, '02-dashboard-top.png') });
  // The app scrolls inside an inner panel, so fullPage does nothing; grow the viewport instead.
  const needed = await page.evaluate(() => { let h = 0; document.querySelectorAll('*').forEach(el => { if (el.scrollHeight > h && getComputedStyle(el).overflowY !== 'visible') h = el.scrollHeight; }); return Math.min(Math.max(h + 80, 900), 4000); });
  await page.setViewport({ width: 1440, height: needed, deviceScaleFactor: 2 });
  await sleep(800);
  await page.screenshot({ path: path.join(OUT, '03-dashboard-full.png') });
  log('saved 02-dashboard-top.png, 03-dashboard-full.png');
  const aiCard = await page.$('.sp-ai-dark-card');
  if (aiCard) {
    await aiCard.screenshot({ path: path.join(OUT, '04-ai-queue-analysis-card.png') });
    log('saved 04-ai-queue-analysis-card.png');
  }
  await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });

  // 4. Service tickets list, on-duty board, on-call schedule.
  const views = [
    ['city-tickets', '05-service-tickets-list.png'],
    ['city-onduty',  '06-on-duty-board.png'],
    ['city-oncall',  '07-on-call-schedule.png'],
  ];
  for (const [view, file] of views) {
    await page.goto(BASE + '/sp-app/?view=' + view, { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(1200);
    await page.screenshot({ path: path.join(OUT, file), fullPage: true });
    log('saved ' + file);
  }

  // 5. First ticket detail (if any).
  await page.goto(BASE + '/sp-app/?view=city-tickets', { waitUntil: 'networkidle2', timeout: 60000 });
  const firstTicket = await page.$('a[href*="action=edit&id="]');
  if (firstTicket) {
    const href = await page.evaluate(a => a.href, firstTicket);
    await page.goto(href, { waitUntil: 'networkidle2', timeout: 60000 });
    await sleep(1200);
    await page.screenshot({ path: path.join(OUT, '08-ticket-detail.png'), fullPage: true });
    log('saved 08-ticket-detail.png');
  }

  // 6. Settings — weekly report card.
  await page.goto(BASE + '/sp-app/?view=settings', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(1200);
  const weekly = await page.$('#section-ai-weekly');
  if (weekly) {
    await weekly.scrollIntoView();
    await sleep(300);
    await weekly.screenshot({ path: path.join(OUT, '09-weekly-report-settings.png') });
    log('saved 09-weekly-report-settings.png');
  }

  // 7. Mobile view of the public form (LinkedIn-friendly).
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 3, isMobile: true, hasTouch: true });
  await page.goto(BASE + '/service-request/', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(1000);
  await page.screenshot({ path: path.join(OUT, '10-public-form-mobile.png') });
  log('saved 10-public-form-mobile.png');

  log('DONE');
  await browser.close();
})().catch(e => { console.error('ERROR ' + e.message); process.exit(1); });
