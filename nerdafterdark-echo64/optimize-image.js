/**
 * NerdAfterDark image optimizer
 * Usage:
 *   node optimize-image.js <input-file> [--width 1200] [--quality 82]
 *
 * Outputs:
 *   <name>.webp   — primary format (smallest, best quality)
 *   <name>.jpg    — fallback for older browsers / WordPress thumbnail generation
 *
 * Defaults:
 *   --width    1200  (maintains aspect ratio, never upscales)
 *   --quality  82    (sweet spot for web: visually lossless, small file)
 */

const sharp  = require('sharp');
const path   = require('path');
const fs     = require('fs');

// ── Parse args ───────────────────────────────────────────────────────────────
const args = process.argv.slice(2);
if (!args.length || args[0] === '--help') {
  console.log('Usage: node optimize-image.js <file> [--width 1200] [--quality 82]');
  process.exit(0);
}

const inputPath = path.resolve(args[0]);
if (!fs.existsSync(inputPath)) {
  console.error(`File not found: ${inputPath}`);
  process.exit(1);
}

const get = (flag, fallback) => {
  const i = args.indexOf(flag);
  return i !== -1 ? parseInt(args[i + 1], 10) : fallback;
};

const maxWidth  = get('--width',   1200);
const quality   = get('--quality', 82);

const dir      = path.dirname(inputPath);
const basename = path.basename(inputPath, path.extname(inputPath));
const outWebP  = path.join(dir, `${basename}.webp`);
const outJPG   = path.join(dir, `${basename}.jpg`);

// ── Process ──────────────────────────────────────────────────────────────────
async function run() {
  const meta = await sharp(inputPath).metadata();
  const originalKB = (fs.statSync(inputPath).size / 1024).toFixed(1);

  console.log(`\nInput:    ${path.basename(inputPath)}  (${meta.width}×${meta.height}  ${originalKB} KB)`);
  console.log(`Settings: max-width ${maxWidth}px · quality ${quality}\n`);

  const pipeline = sharp(inputPath).resize({
    width:   maxWidth,
    withoutEnlargement: true,
  });

  // WebP
  await pipeline.clone()
    .webp({ quality, effort: 6 })
    .toFile(outWebP);
  const webpKB = (fs.statSync(outWebP).size / 1024).toFixed(1);
  console.log(`✓ WebP:   ${path.basename(outWebP)}  (${webpKB} KB)  ${savings(originalKB, webpKB)}`);

  // JPG
  await pipeline.clone()
    .jpeg({ quality, mozjpeg: true, progressive: true })
    .toFile(outJPG);
  const jpgKB = (fs.statSync(outJPG).size / 1024).toFixed(1);
  console.log(`✓ JPG:    ${path.basename(outJPG)}  (${jpgKB} KB)  ${savings(originalKB, jpgKB)}`);

  console.log(`\nUpload the .webp to WordPress — use .jpg as fallback if needed.\n`);
}

function savings(original, output) {
  const pct = (100 - (output / original) * 100).toFixed(0);
  return pct > 0 ? `↓ ${pct}% smaller` : `(no reduction)`;
}

run().catch(err => { console.error(err.message); process.exit(1); });
