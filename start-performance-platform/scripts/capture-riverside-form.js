const puppeteer = require('puppeteer-core');
const path = require('path');
const BASE = 'https://riverside.startperformance.com';
const OUT  = 'C:/Users/richa/Documents/Claude Projects/start-performance-platform/screenshots/riverside-2026-09-08';
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
(async () => {
  const browser = await puppeteer.launch({
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true,
    defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 2 }, ignoreHTTPSErrors: true,
    args: ['--host-resolver-rules=MAP riverside.startperformance.com 64.247.178.244', '--no-first-run'],
  });
  const page = await browser.newPage();
  await page.goto(BASE + '/service-request/', { waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(1200);
  await page.screenshot({ path: path.join(OUT, '01-public-service-request-form.png'), fullPage: true });
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 3, isMobile: true, hasTouch: true });
  await page.reload({ waitUntil: 'networkidle2', timeout: 60000 });
  await sleep(1200);
  await page.screenshot({ path: path.join(OUT, '10-public-form-mobile.png') });
  const title = await page.$eval('.sp-header-city', el => el.textContent.trim());
  console.log('header:', title);
  await browser.close();
})().catch(e => { console.error('ERROR ' + e.message); process.exit(1); });
