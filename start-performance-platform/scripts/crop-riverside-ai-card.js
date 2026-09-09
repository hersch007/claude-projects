const Jimp = require('jimp');
const dir = 'C:/Users/richa/Documents/Claude Projects/start-performance-platform/screenshots/riverside-2026-09-08/';
(async () => {
  const img = await Jimp.read(dir + '03-dashboard-full.png');
  const W = img.bitmap.width, H = img.bitmap.height;
  const isDark = (c) => { const r = (c >>> 24) & 255, g = (c >>> 16) & 255, b = (c >>> 8) & 255; return r < 40 && g < 50 && b < 80; };
  const x0 = 700;
  let top = -1;
  for (let y = 400; y < H; y++) { if (isDark(img.getPixelColor(x0, y))) { top = y; break; } }
  let left = x0, right = x0;
  while (left > 0 && isDark(img.getPixelColor(left - 1, top + 30))) left--;
  while (right < W - 1 && isDark(img.getPixelColor(right + 1, top + 30))) right++;
  const xe = left + 14; // inside the dark shell's left margin
  let bottom = top;
  for (let y = top + 40; y < H; y++) { if (!isDark(img.getPixelColor(xe, y))) { bottom = y; break; } }
  const pad = 24;
  const cx = Math.max(0, left - pad), cy = Math.max(0, top - pad);
  const card = img.clone().crop(cx, cy, Math.min(W, right + pad) - cx, Math.min(H, bottom + pad) - cy);
  await card.writeAsync(dir + '04-ai-queue-analysis-card.png');
  console.log('card', left, top, right, bottom, '->', card.bitmap.width + 'x' + card.bitmap.height);
})().catch(e => { console.error(e); process.exit(1); });
