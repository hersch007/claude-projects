# Rock Hill Pickleball — WordPress Build Checklist

*A click-by-click guide to assemble the resource site from the content you already have. Written for a non-technical volunteer. Work top to bottom — each phase builds on the last.*

**You'll build from these files:**
- `RockHill-Pickleball-Resource-Blueprint.md` — homepage, Start Here, Courts Guide, The Scene, About copy
- `RockHill-Pickleball-Court-and-FAQ-Pages.md` — Boyd Hill, Shield of Faith, Peachtree, Skill Levels, FAQ
- `pickleball-tournament-scheduler-2/` — the scheduler widget

**Realistic time:** a focused weekend for a first version. Don't aim for perfect — aim for live.

---

## PHASE 0 — Foundation (½ day)

- [ ] **Buy a domain.** Suggested: `rockhillpickleball.com` (or `.org`). Namecheap, Google Domains, or through your host.
- [ ] **Get WordPress hosting.** Pick a managed host that installs WordPress for you:
  - Recommended for easy + affordable: **SiteGround**, **Cloudways**, or **Bluehost**.
  - During signup, choose the "WordPress" / "install WordPress" option.
- [ ] **Confirm you can log in** at `yourdomain.com/wp-admin`.
- [ ] **Set the site title & tagline:** Settings → General →
  - Site Title: `Rock Hill Pickleball`
  - Tagline: `Your Complete Guide to Pickleball in Rock Hill & York County`
- [ ] **Set permalinks to "Post name":** Settings → Permalinks → select **Post name** → Save. *(Important for clean URLs and SEO.)*
- [ ] **Force HTTPS / SSL** — most hosts do this automatically. Confirm the site loads with the padlock (`https://`).

---

## PHASE 1 — Theme & Global Look (½ day)

- [ ] **Install a fast theme.** Appearance → Themes → Add New → search **Kadence** (or **Astra**) → Install → Activate.
- [ ] **Install the starter/onboarding** if prompted (Kadence Starter Templates) — pick a simple, clean template or start blank.
- [ ] **Set brand colors** (Appearance → Customize → Colors, or Kadence → Colors):
  - Primary / Green: `#2E7D32`
  - Accent / Yellow: `#D4E157`
  - Action / Orange: `#F57C00`
  - Text: `#1F2A24` · Background: `#F7F9F4`
- [ ] **Set fonts** (Customize → Typography):
  - Headings: **Poppins** or **Montserrat** (bold)
  - Body: **Inter**
- [ ] **Upload your logo** (Customize → Site Identity). No logo yet? Use the site title text for now.
- [ ] **Set a site favicon** (Customize → Site Identity → Site Icon) — a simple 🎾 or paddle image.
- [ ] **Confirm mobile view** — click the phone icon in the Customizer preview. Everything should look good on a narrow screen.

---

## PHASE 2 — Install the (few) plugins (30 min)

*Keep it lean — this is a resource site, not a web app.*

- [ ] **Rank Math SEO** — Plugins → Add New → search "Rank Math" → Install → Activate → run its setup wizard (choose "I'm setting up a blog/business site," connect a free account, accept default good settings).
- [ ] **A forms plugin** — install **Fluent Forms** (or **WPForms Lite**) for the contact + "Suggest an update" forms.
- [ ] *(Optional)* **Smash Balloon Social Photo Feed** — if you want to auto-show your Instagram photos.
- [ ] *(Optional, later)* **WP Rocket** (paid) + **ShortPixel** — for speed. Skip until the site is built.
- [ ] *(Recommended)* **UpdraftPlus** — set up a weekly automatic backup (Settings → UpdraftPlus → Settings → schedule "Weekly" → Save).

> ❌ Do **not** install: The Events Calendar, BuddyPress, Paid Memberships Pro, bbPress. The resource-first plan doesn't use them — fewer plugins = less to maintain and break.

---

## PHASE 3 — Build the Pages (½ to 1 day)

For each page: **Pages → Add New → paste the title → paste the copy from the content files → set the URL slug (in the sidebar "Permalink") → Publish.** Use Heading blocks for headings and Paragraph blocks for text. Use a **Table block** where the copy shows a table.

Create these pages (copy source in parentheses):

- [ ] **Home** *(Resource Blueprint → Homepage copy)* — build this last; see Phase 5.
- [ ] **Start Here** — slug `/start-here` *(Resource Blueprint → "New to Pickleball? Start Here")*
- [ ] **Skill Levels** — slug `/start/skill-levels` *(Court-and-FAQ file → Understanding Skill Levels)*
- [ ] **FAQ** — slug `/start/faq` *(Court-and-FAQ file → FAQ)*
- [ ] **Courts Guide** — slug `/courts` *(Resource Blueprint → Courts Guide overview + comparison table)*
- [ ] **Boyd Hill** — slug `/courts/boyd-hill` *(Court-and-FAQ file)*
- [ ] **Bleachery Fieldhouse** — slug `/courts/bleachery-fieldhouse` *(Resource Blueprint → the Bleachery template)*
- [ ] **Shield of Faith** — slug `/courts/shield-of-faith` *(Court-and-FAQ file)*
- [ ] **Peachtree – Fort Mill** — slug `/courts/peachtree-fort-mill` *(Court-and-FAQ file)*
- [ ] **Find Players & Groups** — slug `/scene/find-players` *(Resource Blueprint → The Scene)*
- [ ] **About** — slug `/about` *(Resource Blueprint → About / Our Story)*
- [ ] **Contact** — slug `/contact` — add a Fluent Forms contact form block.

### ⚠️ Fill in the blanks
- [ ] Go through every page and replace each **`[FILL IN: …]`** with real info (hours, prices, addresses, CourtReserve links). **Don't publish guesses** — leave a note like "Schedule confirming — check CourtReserve" if you're unsure.
- [ ] On each court page, **embed a Google Map**: get the address's "Share → Embed a map" iframe from Google Maps and paste it into a Custom HTML block.
- [ ] Replace every `[Suggest an update →]` and `[Contact us →]` link with a link to your Contact page.

---

## PHASE 4 — Embed the Tournament Scheduler (30 min)

- [ ] **Create a page:** Pages → Add New → Title: `Tournament Scheduler` → slug `/scheduler`.
- [ ] **Upload the widget files to your host:**
  - Use your host's File Manager (or an FTP tool) to upload the whole `pickleball-tournament-scheduler-2` folder into `wp-content/uploads/` (or any public folder).
  - Confirm it loads by visiting `yourdomain.com/wp-content/uploads/pickleball-tournament-scheduler-2/index.html`.
- [ ] **Embed it on the page** with a **Custom HTML block**:
  ```html
  <iframe src="/wp-content/uploads/pickleball-tournament-scheduler-2/index.html"
          style="width:100%;height:1500px;border:0"
          title="Tournament Scheduler"></iframe>
  ```
- [ ] Publish and test it on your phone — generate a schedule, enter a score, check standings.

*(Simpler alternative if file upload is a hassle: open `pickleball-tournament-scheduler.html` — the single-file version — and paste its full contents into a Custom HTML block. Works too.)*

---

## PHASE 5 — Homepage & Navigation (½ day)

- [ ] **Build the Home page** using the homepage copy (Resource Blueprint):
  - Hero headline + subheadline + the **two front-door buttons**: "🆕 New to the Game? Start Here" (→ /start-here) and "🏓 Show Me the Courts" (→ /courts).
  - "Courts at a Glance" comparison table (link to /courts).
  - "Find Your People" section (link to /scene/find-players).
  - Three quick-value cards → Skill Levels, FAQ, and (later) Gear.
  - *(Optional)* Instagram feed block (Smash Balloon).
- [ ] **Set Home as the front page:** Settings → Reading → "Your homepage displays: A static page" → Homepage: **Home** → Save.
- [ ] **Build the menu:** Appearance → Menus → create a menu with: **Start Here · Courts · The Scene · About** (add FAQ/Skill Levels as dropdown items under Start Here if your theme supports it) → set as **Primary Menu** → Save.
- [ ] **Build a simple footer menu:** Contact · FAQ · (Suggest an update → Contact).

---

## PHASE 6 — SEO & Discovery (1 hour)

- [ ] In **Rank Math**, for each important page set a **Focus Keyword** and confirm the SEO title/description (the meta descriptions are already written in the content files):
  - Home → `Rock Hill pickleball`
  - Courts Guide → `pickleball courts Rock Hill SC`
  - Each court page → its name (e.g., `Boyd Hill pickleball`)
  - Start Here → `how to start playing pickleball Rock Hill`
- [ ] **Create a free Google Business Profile** for the club at [google.com/business](https://www.google.com/business/) — this wins "pickleball near me" local searches. *(Do this even before the site is perfect.)*
- [ ] **Submit your sitemap to Google:** Rank Math shows your sitemap URL (`/sitemap_index.xml`). Add the site to [Google Search Console](https://search.google.com/search-console) and submit it.
- [ ] List the club on **USA Pickleball's Places2Play** and any local Facebook groups.

---

## PHASE 7 — Pre-Launch Checks (30 min)

- [ ] **Click every menu link and button** — do they all go somewhere real? No dead links.
- [ ] **Test on a real phone** — read every page on mobile. Text readable? Buttons tappable? Tables scroll instead of breaking the layout?
- [ ] **Check the forms** — submit a test through Contact and confirm you receive it (Fluent Forms → Settings → Email notifications go to your club email).
- [ ] **Run a speed check** at [PageSpeed Insights](https://pagespeed.web.dev/) — if mobile score is low, install WP Rocket + ShortPixel (Phase 2 optional items).
- [ ] **Confirm a backup ran** (UpdraftPlus).
- [ ] **Proofread** — especially that no `[FILL IN]` placeholders are still visible anywhere.

---

## 🚀 LAUNCH

- [ ] Announce it in your Group.Me and Facebook groups: *"Rock Hill Pickleball finally has one honest home base — courts, how to start, and where everyone plays. Bookmark it → [link]"*
- [ ] Ask 5 members to click around on their phones and report anything confusing.

---

## 🔁 Ongoing Maintenance (~2 hours per quarter)

- [ ] Walk the Courts Guide — any hours/prices/booking changes? Update.
- [ ] Check that CourtReserve / Group.Me / Facebook links still work.
- [ ] Approve any "suggest an update" messages from members.
- [ ] Add a new court page whenever a new venue opens (great for SEO — you'll rank first).
- [ ] *(Optional)* Post occasional local news to keep it fresh.

---

### Division of labor for 1–2 volunteers
- **Volunteer A (setup):** Phases 0–2 (hosting, theme, plugins) — the one-time technical lift.
- **Volunteer B (content):** Phases 3–5 (pasting copy, filling blanks, building pages) — no code required.
- **Either:** Phases 6–7 and quarterly upkeep.

**Remember:** launch imperfect. A live site with a few `[FILL IN]`s honestly noted beats a perfect site that never ships.
