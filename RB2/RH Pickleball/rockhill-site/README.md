# Rock Hill Pickleball — Site Layout

A full multi-page layout for the resource site. **Open `index.html` in a browser** to click through the whole thing. It's a working mockup *and* the source for the parts you'll use in GeneratePress.

## The pages
| File | Page | Notes |
|---|---|---|
| `index.html` | Home | Hero, two front doors, courts-at-a-glance, scheduler teaser |
| `courts.html` | Courts Guide | Comparison table + honest pro/con card per court |
| `find-players.html` | Find Players & Groups | The community directory |
| `getting-started.html` | Getting Started | 5 steps, skill levels, gear, FAQ accordion |
| `about.html` | About & Contact | Story + contact form placeholder |
| `scheduler.html` | Tournament Scheduler | The embedded tool + "Load Example" demo buttons |

## The files
```
rockhill-site/
├── index.html … scheduler.html      the 6 pages (mockup)
├── assets/
│   ├── header-footer.css   ← YOUR header/footer styles (for GeneratePress)
│   ├── site.css            ← content/section styles (visual spec for GenerateBlocks)
│   ├── site.js             ← injects header/footer into the mockup pages
│   ├── scheduler.css/js     ← the tournament tool (namespaced pbts-)
├── partials/
│   ├── header.html         ← static header markup for GeneratePress
│   └── footer.html         ← static footer markup for GeneratePress
```

---

## How this maps to GeneratePress

**You control the header & footer; the theme handles the page body.** Here's the split:

### 1. Header & footer (yours)
- Load **`assets/header-footer.css`** site-wide (Appearance → Customize → Additional CSS, or enqueue it).
- **Header:** Appearance → **Elements → Add New → Hook**, location `wp_body_open` (or a "Header" element). Paste **`partials/header.html`**. Disable the theme's default header if it shows one.
- **Footer:** Appearance → **Elements → Add New → Hook**, location `generate_after_footer` (or a "Footer" element). Paste **`partials/footer.html`**. Disable the theme's default footer.
- Update the `#` community links and `[Add your club email]` in the footer.

### 2. Page content (theme / GenerateBlocks)
- Build each page's body with **GenerateBlocks** modules, using the matching mockup page as your **visual spec** — colors, spacing, and the component styles are all in `site.css`.
- Fastest path: copy the section HTML from each mockup page into a **Custom HTML / GenerateBlocks "HTML" block**, and load `site.css` so it's styled. Then convert to native blocks over time if you want.
- Colors used: green `#2E7D32`, dark green `#1B5E20`, yellow `#D4E157`, orange `#F57C00`, charcoal `#1F2A24`, off-white `#F7F9F4`. Set these as GeneratePress global colors.

### 3. The scheduler
- Upload `assets/scheduler.css`, `assets/scheduler.js`, and the widget markup (the `<div id="pbts-root">…</div>` block from `scheduler.html`) — OR embed the standalone single-file version via an iframe. Either works.
- The **"Load Example" demo buttons** and their script are in `scheduler.html` — keep them if you want visitors to see a filled-in sample tournament.

---

## Before launch — fill these in
Search the pages for **`[FILL IN`** and replace with real details:
- Court cost / hours / booking (CourtReserve) links — `courts.html`
- CourtReserve / Group.Me / Facebook / Instagram links — `find-players.html`, footer
- Club email — `about.html`, footer
- Paddle recommendations, local shops — `getting-started.html`
- DUPR link (if you have one) — `getting-started.html`

## Notes
- **Mobile-first & responsive** — the header collapses to a hamburger, grids stack, tables scroll.
- The mockup injects the header/footer with `site.js` so the 6 pages stay in sync. In production you use the static `partials/` instead — they're identical.
