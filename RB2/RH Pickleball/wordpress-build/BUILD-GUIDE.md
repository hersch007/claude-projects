# Rock Hill Pickleball - WordPress Build Guide (fresh install)

This builds the exact site you approved into your fresh WordPress in `public_html`, keeping the dashboard for editing. You do the clicks; every piece here is copy-paste.

**Method:** load the CSS once, paste each page's ready-made HTML, and put the header/footer in GeneratePress "Elements." No block rebuilding, so it looks identical to the mockup.

**What's in this folder**
```
wordpress-build/
├── 00-HEAD-CODE.html      -> paste site-wide in the <head>
├── 00-FOOTER-CODE.html    -> paste site-wide before </body> (scroll animation)
├── header.html            -> paste into a GeneratePress Header element
├── footer.html            -> paste into a GeneratePress Footer element
├── content/               -> the body HTML for each page (paste into each WP page)
│   ├── home.html  courts.html  find-players.html
│   ├── getting-started.html  about.html  scheduler.html
└── rh-assets/             -> upload this whole folder to your web root
```

---

## STEP 1 - Install theme + plugins (5 min)
In WP admin:
- [ ] **Appearance -> Themes -> Add New** -> install & activate **GeneratePress**.
- [ ] **Plugins -> Add New** -> install & activate **GenerateBlocks** (free).
- [ ] **Plugins -> Add New** -> install & activate **WPCode - Insert Headers and Footers** (free). *(This is how we load the CSS/JS site-wide.)*

## STEP 2 - Upload the assets (5 min)
- [ ] In cPanel **File Manager**, go to **`public_html`**.
- [ ] Upload the **`rh-assets`** folder from this package so it lives at `public_html/rh-assets/`.
- [ ] Test: visit `yourdomain.com/rh-assets/site.css` - you should see CSS text (not a 404).

## STEP 3 - Load the CSS + JS site-wide (5 min)
- [ ] **WPCode -> + Add Snippet -> Add Your Custom Code (HTML)**.
- [ ] Paste **`00-HEAD-CODE.html`**, set **Location = Site Wide Header**, Save + Activate.
- [ ] Add another snippet, paste **`00-FOOTER-CODE.html`**, set **Location = Site Wide Footer**, Save + Activate.

## STEP 4 - Header & footer via GeneratePress Elements (10 min)
- [ ] **Appearance -> Elements -> Add New -> Block**. Title: "Site Header".
  - In the block editor, add a **Custom HTML** block (or use the "Bracket </>" / HTML block) and paste **`header.html`**.
  - Set **Settings -> Element Type = Hook**, **Hook = wp_body_open**, **Display Rules = Entire Site**. Publish.
- [ ] **Add New -> Block** again. Title: "Site Footer".
  - Paste **`footer.html`** into a Custom HTML block.
  - **Element Type = Hook**, **Hook = generate_after_footer**, **Display Rules = Entire Site**. Publish.
- [ ] **Appearance -> Customize -> Layout -> Header** -> set Header to **"No" / disable** the default (so only your custom header shows). Same for the theme footer widgets if any.

## STEP 5 - Create the pages (15 min)
For each file in **`content/`**, make a WP page:
- [ ] **Pages -> Add New**. Set the **Title** and **URL slug** exactly as below.
- [ ] Add a single **Custom HTML** block and paste the whole matching file.
- [ ] In the page sidebar, set **Template = "Full Width" / GeneratePress "Full Width Content"**, and **hide the page title** (GeneratePress: page sidebar -> "Disable Elements" -> Content Title). *(The content already includes its own hero/heading.)*
- [ ] Publish.

| File | Page title | Slug |
|---|---|---|
| `content/home.html` | Home | `home` |
| `content/courts.html` | Courts | `courts` |
| `content/find-players.html` | Find Players | `find-players` |
| `content/getting-started.html` | Getting Started | `getting-started` |
| `content/about.html` | About | `about` |
| `content/scheduler.html` | Scheduler | `scheduler` |

*(Internal links in the content already point to `/courts/`, `/find-players/`, etc. - so keep those slugs exactly.)*

## STEP 6 - Set the homepage (2 min)
- [ ] **Settings -> Reading -> "Your homepage displays" = A static page -> Homepage = Home**. Save.
- [ ] Visit your domain - the home page shows with the photo hero (the `body.home` rule handles that automatically).

## STEP 7 - Finishing touches
- [ ] **Settings -> Permalinks -> Post name -> Save** (clean URLs).
- [ ] **Appearance -> Customize -> Site Identity -> Site Icon** = upload the logo (browser-tab favicon).
- [ ] **Contact page:** the About page has a form *placeholder*. Install **Fluent Forms** (or WPForms), build a simple Name/Email/Message form emailing your club, and replace the placeholder block.
- [ ] Search all pages for **`[FILL IN`** and add your real details (court cost/hours, club email, gear picks, DUPR link).

---

## Notes / gotchas
- **Logo:** the header/footer load the logo from `/rh-assets/cropped-rock-hill-york-county-pickleball-club-logo.png`. If you'd rather use the Media Library, upload it there and swap the `src` in `header.html`/`footer.html`.
- **Nav menu:** the header links are hardcoded to the slugs above, so they work as soon as the pages exist. (No WP menu needed, though you can build one later.)
- **CourtReserve + Group.Me links** are already live in the content. Facebook/Instagram show "coming soon" until you have them.
- **Scheduler:** its script is included at the bottom of `content/scheduler.html`, so the tool and the "Load Example" demo buttons work on that page only.
- If a page looks unstyled, re-check Step 2/3 (the `/rh-assets/` path and the WPCode header snippet).
