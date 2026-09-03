import puppeteer from 'puppeteer';
import { writeFileSync, readFileSync, readdirSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const srcDir = resolve(__dirname, 'src');

// Light → Dark token map
const TOKEN_MAP = [
  ['#F6F8FA', '#0D1117'],
  ['#FFFFFF',  '#161B22'],
  ['white',    '#161B22'],
  ['#D1D9E0', '#30363D'],
  ['#EEF1F4', '#21262D'],
  ['#1A1F2E', '#E6EDF3'],
  ['#656D76', '#7D8590'],
  ['#8C959F', '#484F58'],
  ['#0969DA', '#388BFD'],
  ['#0860C4', '#2F81F7'],
  ['#0969DA1A', '#388BFD1A'],
  ['#CF222E', '#F85149'],
  ['#9A6700', '#D29922'],
  ['#1A7F37', '#3FB950'],
  ['#8250DF', '#A371F7'],
  ['#D4A72C', '#D29922'],
  // badge backgrounds
  ['#FFEBE9', '#F851491A'],
  ['#FFF8C5', '#D299221A'],
  ['#DAFBE1', '#3FB9501A'],
  ['#DDF4FF', '#388BFD1A'],
  ['#FBEFFF', '#A371F71A'],
  ['#FFF5F5', '#F851490A'],
  ['#FFFDF0', '#D299220A'],
  // shadow
  ['shadow-sm', ''],
  ['hover:shadow-md', ''],
];

// Read and back up component files
const files = ['index.css',
  'components/TopBar.tsx',
  'components/Sidebar.tsx',
  'components/ShotClockRail.tsx',
  'components/ActionPanel.tsx',
  'components/PortfolioChart.tsx',
  'components/ProjectTable.tsx',
  'components/Dashboard.tsx',
].map(f => resolve(srcDir, f));

const backups = {};
for (const f of files) {
  backups[f] = readFileSync(f, 'utf8');
}

// Apply dark tokens
for (const f of files) {
  let content = backups[f];
  for (const [light, dark] of TOKEN_MAP) {
    content = content.replaceAll(light, dark);
  }
  writeFileSync(f, content);
}
console.log('Applied dark tokens, waiting for HMR...');
await new Promise(r => setTimeout(r, 4000));

// Capture
const browser = await puppeteer.launch({
  headless: true,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
await page.goto('http://localhost:5173', { waitUntil: 'networkidle0', timeout: 15000 });
await new Promise(r => setTimeout(r, 1500));

await page.$eval('main', el => el.scrollTop = 0);
await new Promise(r => setTimeout(r, 300));
writeFileSync('screenshot-dark-top.png', await page.screenshot({ type: 'png' }));
console.log('Saved screenshot-dark-top.png');

await page.$eval('main', el => el.scrollTop = 600);
await new Promise(r => setTimeout(r, 300));
writeFileSync('screenshot-dark-table.png', await page.screenshot({ type: 'png' }));
console.log('Saved screenshot-dark-table.png');

await browser.close();

// Restore light files
for (const f of files) {
  writeFileSync(f, backups[f]);
}
console.log('Restored light theme. Done.');
