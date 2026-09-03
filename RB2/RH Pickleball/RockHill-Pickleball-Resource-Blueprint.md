# Rock Hill Pickleball — Resource Site Blueprint (v2, Resource-First)

*The definitive guide to pickleball in Rock Hill & York County. Built once, refreshed a few times a year. No schedules to babysit.*

---

## 0. The Concept (what changed and why)

This is **not** a "where to play right now" site. It's a **resource** — the page people find when they Google pickleball in Rock Hill, and the link locals send to anyone asking "how do I get into this?"

**The job it does:**
> Whether you just moved here, just picked up a paddle, or have been dinking for years — this is the one place that tells you the honest truth about every court, how to plug into the community, and everything you need to play more and play better in York County.

**Two audiences, one site:**
- **Newcomers** — "I'm new here / new to the game. Where do I start?" → getting-started guides, courts overview, how to find people.
- **Existing players** — "Just give me the honest court intel and the scene directory in one place." → deep courts guide, coaches/leagues/clinics directory, gear.

**Why this is the right model for you:**
- **Evergreen content** — write it once, well. Update quarterly, not weekly.
- **No live data to maintain** — you *link out* to CourtReserve, Group.Me, Facebook. You're the map, not the host.
- **SEO is the growth engine** — reference content ranks and gets shared. It compounds while you sleep.
- **Runnable by one person** in a couple hours a month.

**Success metric:** *"When someone searches or asks about Rock Hill pickleball, they land here and think 'this is exactly what I needed.'"* Not daily visits — **authority and shares.**

---

## 1. Site Structure / Sitemap

Lean and evergreen. Every page earns its place by being useful for months, not hours.

```
HOME  ← two clear front doors: "New to Pickleball" and "Find Courts"
│
├── START HERE (for newcomers)
│   ├── New to Pickleball? Start Here
│   ├── Understanding Skill Levels (2.5–5.0 explained)
│   └── FAQ
│
├── COURTS  ← the flagship
│   ├── Courts Guide (overview + comparison table)
│   ├── Boyd Hill            (individual court page)
│   ├── Bleachery Fieldhouse (individual court page)
│   ├── Shield of Faith      (individual court page)
│   ├── Peachtree – Fort Mill(individual court page)
│   └── [+ any others in York County]
│
├── THE SCENE (for everyone — the directory)
│   ├── Find Players & Groups   (CourtReserve, Group.Me, Facebook links)
│   ├── Leagues & Open Play      (who runs what, link out)
│   ├── Coaches & Clinics        (curated local list)
│   └── Gear Guide               (paddles, where to buy/demo)
│
├── ABOUT
│   ├── About / Our Story
│   └── Better Courts (Advocacy)   ← optional, feedback form
│
└── UTILITY (footer)
    ├── Contact / Suggest an Update
    ├── Newsletter (optional, low-frequency)
    └── Sponsors / Local Businesses
```

**Primary nav (5 items):** `Start Here` · `Courts ▾` · `The Scene ▾` · `Gear` · `About`

No sticky "play today" bar, no login, no member portal. A prominent **"Suggest an update / report a court change"** link in the footer lets the community keep you honest — crowdsourced freshness with zero data-entry burden on you.

---

## 2. Plugin Stack (dramatically simplified)

You went from ~15 plugins to a handful. This is the maintenance win.

| Need | Plugin | Cost | Notes |
|---|---|---|---|
| Theme (fast, block-based) | **Kadence** or **Astra** | Free (Pro optional ~$60/yr) | Fast, mobile-first, easy templates |
| SEO | **Rank Math** (or Yoast) | Free | Schema, sitemaps, local SEO — your #1 growth tool |
| Forms | **Fluent Forms** or **WPForms Lite** | Free | Contact, "suggest an update," advocacy feedback |
| Social feed (optional) | **Smash Balloon** | Freemium | Auto-pulls Instagram = free fresh visuals |
| Newsletter (optional) | **MailerLite** or **FluentCRM** | Free tier | Only if you want a low-frequency list |
| Performance | **WP Rocket** + **ShortPixel** | ~$60/yr | Speed = better SEO + happy mobile users |
| Security/backup | **Wordfence** + **UpdraftPlus** | Free | Basic protection + auto backups |

**That's it.** No Events Calendar, no BuddyPress, no memberships, no forums, no SportsPress. If you later want *one* interactive touch, add a simple embedded Google Calendar for club events — but it's optional and out of scope for the resource core.

> **Even lighter option:** a resource site like this can be built well on a page-builder theme with zero premium plugins. Start free; add WP Rocket only if speed tests say you need it.

---

## 3. Homepage Layout & Wireframe

**Design intent:** in 3 seconds, a visitor self-sorts into "I'm new" or "I know the game, show me courts." Two clear front doors.

```
┌───────────────────────────────────────────────┐
│ [Logo]   Start Here  Courts▾  Scene▾  Gear About│
├───────────────────────────────────────────────┤
│  HERO                                           │
│  H1: Your Complete Guide to Pickleball in       │
│      Rock Hill & York County                    │
│  subhead: honest court info, how to find        │
│           players, everything to get started.   │
│  ┌─────────────────┐   ┌─────────────────┐      │
│  │ 🆕 New to the   │   │ 🏓 Show Me the  │      │
│  │    Game? Start  │   │    Courts       │      │
│  │    Here  →      │   │    →            │      │
│  └─────────────────┘   └─────────────────┘      │
├───────────────────────────────────────────────┤
│  THE COURTS AT A GLANCE                          │
│  comparison table / 4 cards: indoor? #courts?   │
│  cost? honest one-liner.   [Full Courts Guide]  │
├───────────────────────────────────────────────┤
│  FIND YOUR PEOPLE                               │
│  "The games are already happening — here's how  │
│   to plug in." → CourtReserve · Group.Me · FB   │
├───────────────────────────────────────────────┤
│  3 QUICK-VALUE CARDS                            │
│  [Skill Levels Explained] [Coaches & Clinics]   │
│  [Beginner Gear Guide]                          │
├───────────────────────────────────────────────┤
│  ABOUT / TRUST band + Instagram feed            │
├───────────────────────────────────────────────┤
│  (Optional) Newsletter: occasional local updates│
├───────────────────────────────────────────────┤
│  FOOTER: nav · suggest an update · contact · IG │
└───────────────────────────────────────────────┘
```

**Above the fold = the two doors + the value promise.** No dynamic data needed. The homepage stays true for months.

---

## 4. Ready-to-Use Copy

### 4.1 Homepage Hero

**Headline options:**
- **"Your Complete Guide to Pickleball in Rock Hill & York County."**
- "Everything You Need to Play Pickleball in Rock Hill."
- "The Honest Guide to Rock Hill Pickleball."

**Subheadline:**
> Where to play, how to find people, and everything you need to get started — the straight truth on every court in York County, all in one place.

**Two front-door cards:**

**🆕 New to the Game? Start Here**
Never held a paddle — or new to the area? We'll get you from zero to your first game: the rules in plain English, what the skill numbers mean, what to bring, and the friendliest places to start. `[ Start Here → ]`

**🏓 Show Me the Courts**
You know the game — you just want the real scoop. Indoor vs. outdoor, how many courts, what it costs, how to book, and the honest pros and cons of every spot in York County. `[ See the Courts Guide → ]`

---

### 4.2 New to Pickleball? Start Here

**Welcome — you picked a great time to start.**

Pickleball is the easiest racquet sport to pick up and one of the hardest to put down. Here in Rock Hill and York County there's a friendly, fast-growing community, and you can genuinely be rallying within your first hour. Here's your no-stress path in.

**Step 1 — Learn the basics (15 minutes).**
It's played on a court smaller than tennis, with a paddle and a plastic ball. Games go to 11, win by 2. There's a "kitchen" (no-volley zone) at the net, and you serve underhand. That's enough to start — you'll pick up the rest by playing. `[Full rules FAQ →]`

**Step 2 — Know what the numbers mean.**
You'll hear people say "I'm a 3.0" or "that's a 4.0 game." Those are skill ratings from 2.5 (brand new) to 5.0+ (highly competitive). Don't worry about yours yet — most beginners are a 2.5–3.0. `[Skill levels explained →]`

**Step 3 — Grab a paddle (you don't need a fancy one).**
A $40–$80 beginner paddle is perfect to start. Many groups have loaner paddles too — you can play your first few times without buying anything. `[Beginner gear guide →]`

**Step 4 — Show up to a beginner-friendly session.**
The easiest way in is open play or a beginner clinic. `[See which courts are best for beginners →]` and `[find the local groups →]` — folks here are welcoming, and "I'm new" is the fastest way to get pulled into a game.

**Step 5 — Find your people.**
The community organizes through CourtReserve, Group.Me, and Facebook. We've rounded up all the links so you're not hunting. `[Find players & groups →]`

*That's it. Come out, say hi, and don't worry about being new — everyone here was, once.*

---

### 4.3 Courts Guide (overview page)

**Every court in York County — the honest version.**

We tell it straight: how many courts, indoor or outdoor, what it costs, how to book, and the real pros and cons. No hype. Use the table to compare at a glance, then tap any court for the full rundown.

| Court | Indoor/Outdoor | Courts | Booking | Best for |
|---|---|---|---|---|
| **Boyd Hill** | Outdoor | Limited | CourtReserve | Close-in casual play |
| **Bleachery Fieldhouse** | Indoor | 12 | CourtReserve | Rain-or-shine, group play |
| **Shield of Faith** | Indoor | — | Church schedule | Weekday mornings, beginners |
| **Peachtree (Fort Mill)** | Varies | — | CourtReserve | Consistency (worth the drive) |

*Notice a court is missing or something's changed? [Tell us →] — this guide stays accurate because the community keeps us honest.*

---

### 4.4 Individual Court Page — template (using Bleachery as the example)

**Bleachery Fieldhouse**
*Indoor · 12 courts · Rock Hill, SC*

**The quick take:** The biggest indoor option around — 12 courts means you can almost always get on, rain or shine. It's the workhorse of Rock Hill indoor play. Just go in knowing its quirks.

**👍 Pros**
- 12 indoor courts — great capacity, weather-proof
- Central Rock Hill location
- Good for groups and larger sessions

**👎 Honest cons**
- Lighting is uneven in spots — some courts play darker than others
- Known dead spots on the floor (locals learn which courts to favor)
- Can be competitive to book at peak times

**Details**
- **Surface:** [indoor gym floor]
- **Cost:** [fill in]
- **How to book:** CourtReserve → `[direct booking link]`
- **Best times:** [fill in — e.g., weekday mornings quieter]
- **Good for beginners?** [yes/with caveats]

**Getting there:** [address + embedded map]

*Been recently? [Suggest an update →]*

> Repeat this exact template for Boyd Hill, Shield of Faith, Peachtree, and any others. Consistency makes it easy to maintain and great for SEO.

---

### 4.5 The Scene — Find Players & Groups

**The games are already happening. Here's how to plug in.**

You don't need us to run a schedule — the Rock Hill pickleball community is active across a few platforms. Here's every place worth joining, in one list, so you're not hunting through screenshots and word-of-mouth.

- **📅 CourtReserve** — how most local courts handle open play and reservations. `[Set up an account →]` and here are the direct links for `[Boyd Hill]`, `[Bleachery]`, `[Peachtree]`.
- **💬 Group.Me chats** — where a lot of the day-to-day "who's playing" happens. `[Join the main group →]` (and skill-specific groups: `[3.0–3.5]` · `[4.0+]`).
- **📘 Facebook groups** — events, photos, and bigger announcements. `[Rock Hill Pickleball on Facebook →]`
- **🎓 Beginner clinics & lessons** — new players start here. `[See coaches & clinics →]`

*New to all of this? Start with CourtReserve and one Group.Me — that's enough to find a game this week.*

---

### 4.6 About / Our Story

**Built by local players who got tired of the runaround.**

Getting into pickleball around Rock Hill used to mean piecing things together — a Group.Me screenshot here, a "which court is even open?" text there, and no straight answer on what any of the courts were actually like. Newcomers had it worst: a fun, welcoming sport hidden behind a wall of insider knowledge.

So we built the guide we wish we'd had.

This site isn't a booking system or a schedule — it's a **resource.** Honest information about every court, plain-English help for anyone new, and a directory of where the community actually lives online. Our goal is simple: **make it easy for anyone in York County to find a game and fall in love with this sport.**

We keep it accurate with help from players like you. Spot something outdated? `[Tell us]` — and if you want to see better courts and facilities in Rock Hill, `[here's how we're pushing for that →]`.

**Welcome to the community. Now go play.**

---

## 5. Colors, Typography & Imagery

*(Carried over from v1 — the brand identity still fits.)*

**Palette:** court green `#2E7D32` (structure), pickleball yellow `#D4E157` (accents/CTAs), warm orange `#F57C00` (highlights), charcoal `#1F2A24` (text), off-white `#F7F9F4` (backgrounds). Pair yellow with charcoal text for contrast/accessibility.

**Type:** Poppins or Montserrat (bold headings) + Inter (body). Two families max.

**Imagery:** real local court and player photos over stock — especially shots of the *actual* venues you're reviewing (a photo of Bleachery's courts on the Bleachery page is worth a lot for trust and SEO). ~15 good photos covers the whole site. Optimize to WebP.

---

## 6. Content & Maintenance Strategy (the light version)

Because this is a resource, "content strategy" means **keeping it accurate and slowly expanding it** — not a weekly grind.

**Set-and-forget (build once):**
- All court pages, Start Here guides, skill-levels, FAQ, gear guide, scene directory.

**Quarterly (~2 hours):**
- Walk the courts guide — any hours, costs, or booking changes? Update.
- Check that all external links (CourtReserve, Group.Me, Facebook) still work.
- Refresh the gear guide if paddle recommendations changed.

**Occasional / opportunistic:**
- Add a new court page when a new venue opens (great for SEO — you'll rank first).
- Publish a short blog post when something notable happens (new courts, a tournament, a rule change) — each one is evergreen SEO fuel.
- Approve "suggest an update" submissions from the community.

**Optional newsletter:** if you want one, make it **quarterly or "when there's real news,"** not weekly. A resource site doesn't need a weekly cadence to succeed — that pressure is exactly what you're avoiding.

---

## 7. Launch Roadmap

**Phase 1 — Core Resource (this is your launch):**
- Homepage (two front doors)
- Courts Guide + individual court pages
- Start Here + Skill Levels + FAQ
- Find Players & Groups (the directory)
- About + Contact/"Suggest an update" form
- SEO basics (Rank Math, sitemap, Google Business Profile)
- **Launch.** This is a complete, valuable site.

**Phase 2 — Depth (add over the following weeks):**
- Coaches & Clinics directory
- Gear Guide
- Better Courts / advocacy page + feedback form
- Instagram feed embed

**Phase 3 — Nice-to-haves (only if you want them):**
- Optional low-frequency newsletter
- Blog for local pickleball news
- Embedded Google Calendar for club events (if you ever run any)

---

## 8. SEO Keyword Targets (your growth engine)

For a resource site, **SEO is everything** — it's how newcomers *and* players find you.

**Primary:**
- `Rock Hill pickleball`
- `pickleball courts Rock Hill SC`
- `York County pickleball`
- `where to play pickleball Rock Hill`
- `indoor pickleball Rock Hill`

**Newcomer intent (high value — little competition):**
- `how to start playing pickleball Rock Hill`
- `pickleball for beginners York County`
- `pickleball rules for beginners`
- `pickleball skill levels explained`
- `beginner pickleball paddle`

**Court-specific (long-tail gold — one page each):**
- `Boyd Hill pickleball`
- `Bleachery Fieldhouse pickleball`
- `Shield of Faith pickleball`
- `Peachtree pickleball Fort Mill`
- `indoor pickleball courts near me` (York County)

**Scene / directory intent:**
- `pickleball lessons Rock Hill`
- `pickleball clinics York County`
- `find pickleball players Rock Hill`
- `pickleball leagues Rock Hill SC`

**SEO to-dos:**
1. **Create a Google Business Profile** → wins "pickleball near me" local results.
2. **One page per court**, each targeting its keyword cluster with LocalBusiness schema (Rank Math).
3. Get listed on **USA Pickleball's Places2Play** and link from local Facebook groups.
4. Write the Start Here / skill-levels / gear guides thoroughly — these rank for newcomer searches statewide and pull people in.

---

### TL;DR — the new model
You're building **the authoritative guide to pickleball in York County**, not a schedule you have to feed. It wins on **honest court intel + a welcoming on-ramp for newcomers + a directory of where the community already lives.** Write it well once, refresh it quarterly, let SEO do the work. Both your audiences — the brand-new player and the seasoned local — get exactly what they came for, and you get a site that stays valuable without babysitting.
