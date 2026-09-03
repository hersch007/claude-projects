# Optimize Image for NerdAfterDark

Optimizes an image for web use — converts to WebP + JPG, resizes to max 1200px wide, compresses to target under 150KB.

## Usage
```
/optimize-image <path-to-image>
```

## Examples
```
/optimize-image C:\Users\richa\Downloads\my-hero-image.png
/optimize-image C:\Users\richa\Downloads\firefly-poster.jpg --width 800
```

## What it does
Runs `optimize-image.js` on the given file and outputs:
- `<name>.webp` — primary format (smallest, best quality)
- `<name>.jpg` — fallback for WordPress thumbnail generation

Both files are saved alongside the original.

---

Run the optimizer on the file provided in the arguments:

```bash
node "C:\Users\richa\Documents\Claude Projects\nerdafterdark-echo64\optimize-image.js" $ARGUMENTS
```
