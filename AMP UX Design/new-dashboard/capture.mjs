import puppeteer from 'puppeteer';
import { writeFileSync } from 'fs';

const browser = await puppeteer.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
await page.goto('http://localhost:5173', { waitUntil: 'networkidle0', timeout: 15000 });
await new Promise(r => setTimeout(r, 1500));

// Screenshot 1 — top (shot clocks + actions)
await page.$eval('main', el => el.scrollTop = 0);
await new Promise(r => setTimeout(r, 300));
const s1 = await page.screenshot({ type: 'png', fullPage: false });
writeFileSync('screenshot-top.png', s1);
console.log('Saved screenshot-top.png');

// Screenshot 2 — bottom (project table)
await page.$eval('main', el => el.scrollTop = 600);
await new Promise(r => setTimeout(r, 300));
const s2 = await page.screenshot({ type: 'png', fullPage: false });
writeFileSync('screenshot-table.png', s2);
console.log('Saved screenshot-table.png');

await browser.close();
console.log('Done');
