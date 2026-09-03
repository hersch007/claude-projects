# Rock Hill Pickleball Club — WordPress Website Blueprint

*The central community hub for Rock Hill / York County pickleball. Built to be the #1 bookmark players check before every session.*

---

## 0. Guiding Principles (read this first)

Everything below is engineered around one behavior loop:

> **Player has free time → opens phone → checks RH Pickleball → finds where/when to play + who's playing → shows up → posts a recap → comes back tomorrow.**

Three rules keep that loop alive:

1. **Freshness beats features.** A dead calendar kills the habit faster than a missing feature. Prioritize things that *change every week* (schedules, "playing today," newsletter).
2. **Mobile-first, always.** 80%+ of visits will be a player standing in a parking lot deciding where to drive. Every page must load fast and be thumb-usable.
3. **Maintainable by 1–2 volunteers.** Every plugin recommendation is weighed against "can a non-technical volunteer keep this current in 20 min/week?"

---

## 1. Site Structure / Sitemap

```
HOME
│
├── PLAY
│   ├── Courts & Schedules      ← flagship page (calendar + court directory)
│   ├── Find a Partner          ← player board + "Playing Today"
│   └── Events & Leagues        ← RSVPs, ladders, tournaments
│
├── COMMUNITY
│   ├── Member Forums           ← court feedback, ride-share, gear swap
│   ├── Player Spotlights       ← weekly featured member
│   ├── Photo Gallery & Recaps  ← Instagram feed + event albums
│   └── Newsletter Archive      ← "This Week in Rock Hill Pickleball"
│
├── ABOUT
│   ├── About Us / Our Story
│   ├── Local Courts Guide      ← honest pros/cons directory (SEO magnet)
│   └── Advocacy / Better Courts ← feedback forms + petitions
│
├── JOIN
│   ├── Membership (Free + Paid tiers)
│   └── Member Login / My Account
│
└── UTILITY (footer)
    ├── Contact / Volunteer
    ├── Sponsors & Partners
    └── FAQ
```

**Primary nav (keep to 5 items max for mobile):** `Play ▾` · `Community ▾` · `Courts Guide` · `Join` · `Login`

**Persistent sticky mobile bar (bottom):** `📅 Schedule` · `🎾 Play Today` · `👥 Partners` · `☰ Menu` — this is the single biggest driver of daily return visits.

---

## 2. Recommended Plugin Stack

| Need | Plugin | Tier | Why |
|---|---|---|---|
| Calendar & events | **The Events Calendar Pro** + **Event Tickets** (free) | Pro (~$99/yr) | Recurring events, filtered views, RSVP + attendee lists |
| Community profiles/activity | **BuddyPress** *or* **FluentCommunity** | Free / Freemium | Member directory, activity feed, "playing today" status |
| Forums | **bbPress** (with BuddyPress) *or* built into FluentCommunity | Free | Court feedback, ride-share, gear swap boards |
| Memberships / paywall | **Paid Memberships Pro** | Free core + add-ons | Free & paid tiers, member-only content, recurring billing |
| Forms | **WPForms Lite/Pro** *or* **Fluent Forms** | Freemium | Partner requests, advocacy petitions, contact |
| Email / newsletter | **FluentCRM** (self-hosted) *or* **Mailchimp** | FluentCRM ~$129/yr | Weekly newsletter, automations, segments by skill level |
| Page building | **Elementor Pro** *or* native Block editor | Pro (~$99/yr) | Fast layout by volunteers, template reuse |
| Social feeds | **Smash Balloon** (Instagram + Facebook) | Freemium | Auto-pulls fresh photos = free content |
| Sports/stats (optional) | **SportsPress** | Free | Ladder standings, league tables, player stats |
| SEO | **Rank Math** *or* **Yoast** | Free | Local SEO, schema, sitemaps |
| Performance | **WP Rocket** + **ShortPixel** | Paid (~$60/yr) | Speed = mobile retention |
| Security/backup | **Wordfence** + **UpdraftPlus** | Free | Protect member data, backups |

> **Two-ecosystem decision (choose ONE lane to keep maintenance sane):**
> - **Lane A — "The Events Calendar + BuddyPress + bbPress + PMPro + FluentCRM."** Most flexible, best calendar, more moving parts.
> - **Lane B — "FluentCommunity + Fluent Forms + FluentCRM + PMPro."** Fewer plugins, one dashboard, community + email + forms unified. **Recommended for a 1–2 volunteer team.**
>
> I recommend **Lane B for community/email/forms + The Events Calendar Pro just for the calendar** (its calendar is worth the exception). This is the sweet spot of "powerful but maintainable."

---

## 3. Detailed Feature Implementation Plan

### 3.1 Dynamic, Filterable Calendar (flagship)
- **Plugin:** The Events Calendar Pro.
- **Setup:**
  - Create **Event Categories**: `Club Play`, `Leagues/Ladders`, `Clinics`, `Tournaments`, `Social`, and one per venue: `Boyd Hill`, `Bleachery Fieldhouse`, `Shield of Faith`, `Peachtree (Fort Mill)`.
  - Create **Venues** once (address + map) so every event auto-shows directions.
  - Enable **filter bar** (Pro) → players filter by skill, venue, and category.
  - Use **recurring events** for standing sessions (e.g., "Tue/Thu 6pm Intermediate Round Robin").
  - **Overlay public court schedules:** manually mirror known Boyd Hill / Bleachery open-play blocks as recurring events tagged `Public Open Play` in a muted color. Add a clear note: *"Public schedule — confirm on CourtReserve."* with a deep link.
  - Embed a **"This Week" list view** on the homepage (not just month grid — mobile users want the next 3 days).
- **Engagement hooks:** RSVP button on every event, "X going" counter, add-to-calendar (Google/Apple).

### 3.2 "Find a Partner" / Player Matching Board
- **Plugin:** FluentCommunity (spaces/feed) **or** a custom board via WPForms + a filtered post list; simplest = a **BuddyPress "Playing Today" activity + member directory with skill filters**.
- **Setup:**
  - Add **profile fields**: DUPR rating, self-rated level (2.5–5.0), home courts, availability (mornings/evenings/weekends), preferred play style.
  - Create a **"Playing Today" toggle**: a member profile field or a daily activity post ("I'm at Bleachery 5–7, need 1 more 3.5+"). Pin a "Playing Today" feed on the Find a Partner page.
  - **Member directory filters:** skill level, availability, location.
  - Add a **"Looking for a Partner" form** (Fluent Forms) that posts to the board + notifies matching members by email.
- **DUPR integration:** DUPR has no official WP plugin; practical approach = a **DUPR profile URL field** on member profiles + a club **DUPR club link** in the footer. Show ratings as a badge. Auto-sync isn't realistic for volunteers — manual field is fine.

### 3.3 Member Portal — Free + Paid Tiers
- **Plugin:** Paid Memberships Pro.
- **Recommended tiers:**

  | Tier | Price | Gets |
  |---|---|---|
  | **Community (Free)** | $0 | Newsletter, forums (read), courts guide, calendar view, RSVP to free events |
  | **Member** | ~$25–40/yr | Full profile + partner board, post in forums, member-only events/ladders, priority RSVP, member discounts |
  | **Plus / Family** | ~$60–75/yr | Everything + family accounts, early tournament registration, club merch discount |
- **Setup:** Gate the partner board + forum posting + certain event categories to paid. Use PMPro's "membership required" per page. Enable recurring billing via Stripe. **Keep the free tier genuinely useful** — free users are your growth funnel and newsletter list.

### 3.4 Community Forums
- **Plugin:** bbPress (Lane A) or FluentCommunity Spaces (Lane B).
- **Boards to launch with (don't over-create):**
  - 🏓 **Court Conditions & Feedback** (report Bleachery dead spots, Boyd Hill cancellations)
  - 🚗 **Ride Share & Carpool** (to Peachtree, tournaments)
  - 🔁 **Gear Swap & Sell**
  - 📣 **General / Meet & Greet**
- **Engagement:** seed each board with 2–3 real posts before launch; assign a volunteer moderator; email digest of new replies.

### 3.5 Event RSVPs + Reminders
- **Plugin:** Event Tickets (free) with The Events Calendar.
- **Setup:** RSVP with capacity limits (great for limited-court sessions), public attendee list ("see who's going" drives sign-ups), automated email reminder 24h + 2h before. Waitlist for full sessions.

### 3.6 Weekly Newsletter — "This Week in Rock Hill Pickleball"
- **Plugin:** FluentCRM (or Mailchimp).
- **Setup:**
  - Signup forms: homepage hero, footer, exit-intent, post-RSVP.
  - **Template = repeatable 5-block layout** (see §7 content strategy) so a volunteer fills blanks in 20 min.
  - Segment lists by skill level + home court for targeted "your court this week" sends.
  - Automate a **welcome series** (3 emails) for new signups.

### 3.7 Local Courts Directory (SEO powerhouse)
- **Build with:** a custom post type or simple pages, one per venue, using a consistent template.
- **Each court page includes:** address + map, # courts (indoor/outdoor), lighting quality, surface notes, cost, reservation method + **CourtReserve deep link**, honest ⭐ pros/cons, best times, and a live "reports from members" feed pulling the forum tag.
- This is your **#1 organic search magnet** — see §11 keywords.

### 3.8 Advocacy Tools
- **Plugin:** WPForms/Fluent Forms + a petition (Fluent Forms with a running signature count, or a simple form + public tally).
- **Setup:** "Report a Court Problem" form → routes to the right venue thread + a private log for club leaders to take to the city/facility. A **"Better Courts Petition"** page with signature counter and a progress goal ("487 of 500 signatures for improved Bleachery lighting").

### 3.9 Spotlights, Gallery, Recaps
- **Plugins:** Smash Balloon (Instagram/Facebook auto-feed) + native posts for spotlights/recaps.
- **Setup:** weekly "Player Spotlight" post template; event recap template with photos; embed the Instagram feed on the homepage and gallery so social posts double as website content (zero extra work).

### 3.10 Skill Filters & DUPR
- Covered in 3.2. Standardize skill labels site-wide (2.5 / 3.0 / 3.5 / 4.0 / 4.5+) and use the same taxonomy on events, partner board, and profiles so filters are consistent.

---

## 4. Homepage Layout & Wireframe

**Design intent:** answer "where can I play, when, and with who — *today*" in the first 3 seconds.

```
┌───────────────────────────────────────────────┐
│ [Logo]   Play▾  Community▾  Courts  Join  Login│  ← sticky header
├───────────────────────────────────────────────┤
│  ABOVE THE FOLD (hero)                          │
│  H1 headline + subhead                          │
│  [ See This Week's Schedule ]  [ Find a Partner]│ ← 2 CTAs
│  ▸ live "PLAYING TODAY: 14 players ·             │
│    Next open play: Bleachery 6pm"  (dynamic)    │
├───────────────────────────────────────────────┤
│  3 KEY BENEFIT CARDS                            │
│  [Reliable Schedules] [Never Play Alone] [Local │
│   Courts, Honest Info]                          │
├───────────────────────────────────────────────┤
│  THIS WEEK'S CALENDAR (list view, next 5 days)  │
│  filter chips: skill · venue · type   [Full Cal]│
├───────────────────────────────────────────────┤
│  FIND A PARTNER — "Playing Today" feed preview  │
│  3 recent posts + [ Post that you're playing ]  │
├───────────────────────────────────────────────┤
│  LOCAL COURTS SNAPSHOT (4 venue cards w/ status)│
│  Boyd Hill · Bleachery · Shield of Faith · Peach│
├───────────────────────────────────────────────┤
│  NEWSLETTER SIGNUP band                         │
│  "This Week in Rock Hill Pickleball" [email][Go]│
├───────────────────────────────────────────────┤
│  COMMUNITY: Instagram feed + latest spotlight   │
├───────────────────────────────────────────────┤
│  MEMBERSHIP CTA band  [ Join Free ][ Go Member ]│
├───────────────────────────────────────────────┤
│  FOOTER: nav · sponsors · social · contact      │
└───────────────────────────────────────────────┘
     Mobile sticky bottom: 📅 · 🎾 · 👥 · ☰
```

**Above-the-fold must-haves:** the headline, the two CTAs, and **at least one live/dynamic element** (playing-today count or next session). Static heroes don't build habits — a number that changes daily does.

---

## 5. Ready-to-Use Copy

### 5.1 Homepage Hero

**Headline options (pick one):**
- **"Rock Hill's Pickleball Home Base."**
- "Never Wonder Where to Play Again."
- "Your Court. Your Crew. Every Day."

**Subheadline:**
> Reliable schedules, last-minute partners, and the real scoop on every court in York County — all in one place. Check in before you head out.

**Primary CTA:** `See This Week's Schedule`
**Secondary CTA:** `Find a Partner`

**Dynamic strip:** `🎾 14 players playing today · Next open play: Bleachery Fieldhouse, 6:00 PM`

**3 Key Benefit Cards:**

**📅 Always-Current Schedules**
No more guessing or scrolling three Group.Me chats. Every session, league, and open-play block across Rock Hill in one filterable calendar — updated weekly.

**👥 Never Play Alone**
Post that you're heading out and match instantly with players at your level. From 2.5 beginners to 4.5 bangers, your next game is one tap away.

**🏓 Honest Court Intel**
The real deal on Boyd Hill, Bleachery, Shield of Faith, and Peachtree — hours, lighting, surface, booking links, and live member reports. Drive to the right court the first time.

---

### 5.2 About Us

**Built by players, for players.**

Rock Hill Pickleball Club started the same way most great things around here do — a handful of folks who couldn't get enough of the game and were tired of the runaround. Cancelled sessions at Boyd Hill. Circling the Bleachery parking lot wondering if there was even room. Three different group chats and still no idea who was playing tonight. Half our crew driving 25 minutes to Fort Mill just to get a consistent game.

We figured York County deserved better. So we built the hub we always wished existed.

**What we're about:**
- **Making play easy.** One place for every schedule, every court, every game.
- **Growing the game.** New to pickleball? You'll find a welcoming crew and a beginner clinic within the week.
- **Fighting for better courts.** We're organized, we're loud (in a friendly way), and we're advocating for the lighting, flooring, and court time our community deserves.
- **Community over everything.** Ride shares, gear swaps, player spotlights, post-game hangs. The game is the excuse; the people are the point.

Whether you're a 2.5 picking up a paddle for the first time or a 4.5 chasing your next DUPR bump, there's a spot for you on this court. **Welcome home.**

`[ Join the Club — It's Free to Start ]`

---

### 5.3 Courts & Schedules

**Every court. Every session. One page.**

Stop juggling CourtReserve tabs and group chats. Below is the live, filterable schedule for club play and public open-play across Rock Hill and York County. Filter by skill level, venue, or session type — then RSVP so your partners know you're coming.

> **Filter:** `Skill: All ▾`  `Venue: All ▾`  `Type: All ▾`   `[ Add to my calendar ]`

**The Courts — Honest Snapshot**

**Boyd Hill** — *Rock Hill · Outdoor*
Our closest public option. When it's running, it's great. ⚠️ Scheduling and cancellations can be unpredictable and court count is limited — always check the calendar and CourtReserve before you drive over. `[Booking Link]` `[Report Conditions]`

**Bleachery Fieldhouse** — *Rock Hill · Indoor · 12 courts*
The big one — 12 indoor courts means rain-or-shine play. ⚠️ Lighting is uneven and there are known dead spots on the floor (we've mapped the worst ones — check member reports). Booking can be competitive, so plan ahead. `[Booking Link]` `[Court Map & Reports]`

**Shield of Faith Church** — *Rock Hill · Indoor*
A community gem with a welcoming vibe. Availability is limited to **weekday mornings and Sunday after service** — perfect for retirees, remote workers, and early birds. `[Schedule]`

**Peachtree (Fort Mill)** — *York County line · ~20–25 min*
Worth the drive for consistency and court quality — a lot of our crew makes the trip. We list their open-play here so you can carpool. `[Booking Link]` `[Find a Ride]`

*See a problem at any court? [Report it here] — we log every report and take them to facility managers and the city.*

---

### 5.4 Find a Partner

**Your next game is one tap away.**

Heading to the courts? Let the community know. Post where and when you're playing, your skill level, and how many players you need — and we'll match you up. No more showing up to an empty court or a full one.

`[ + I'm Playing Today ]`   `[ Set My Availability ]`

**Playing Today** *(live feed)*
> **Mike D. (3.5)** — Bleachery, 6–8 PM, need 2 more · *2 replies*
> **Sarah & Jen (3.0)** — Shield of Faith, 9 AM tomorrow, open to 1–2 · *join*
> **Open Ladder Night** — Boyd Hill, 5:30 PM, all levels welcome · *8 going*

**Find players like you:**
Filter the member directory by **skill (2.5–4.5+)**, **home court**, and **availability**. Follow players, DM to set up a game, and build your regular rotation.

`Skill ▾`  `Court ▾`  `Availability ▾`

*Members can post to the board and message players. [Become a member] to unlock full access — or [set up your free profile] to get started.*

---

### 5.5 Events & Leagues

**There's always something happening.**

From beginner clinics to competitive ladders to just-for-fun social nights, our calendar stays full. RSVP to save your spot, see who else is coming, and get a reminder before it starts.

**What's on:**
- **🏓 Round Robins** — weekly, sorted by skill. Show up, get matched, play everyone.
- **📈 Ladder Leagues** — climb the standings over a season. Track your rank and DUPR.
- **🎓 Beginner Clinics** — new to the game? Start here. Paddles provided.
- **🏆 Tournaments** — in-house brackets and trips to regional events.
- **🍻 Socials** — post-game hangs, cookouts, and season kickoffs.

`[ Browse the Full Calendar ]`   `[ RSVP to This Week's Events ]`

*Capacity is limited at some venues — RSVP early and hop on the waitlist if it fills. You'll get an automatic reminder 24 hours and 2 hours before.*

---

### 5.6 Join / Membership

**Join the club. Play more. Belong.**

Getting started is free. Becoming a member unlocks the partner board, member-only ladders, and the full Rock Hill Pickleball experience — plus you're directly fueling our push for better courts.

| | **Community** | **Member** ⭐ | **Plus / Family** |
|---|---|---|---|
| Price | **Free** | **$30/yr** | **$65/yr** |
| Weekly newsletter | ✅ | ✅ | ✅ |
| Browse calendar & courts guide | ✅ | ✅ | ✅ |
| RSVP to free events | ✅ | ✅ | ✅ |
| Read forums | ✅ | ✅ | ✅ |
| **Full player profile + partner board** | — | ✅ | ✅ |
| **Post in forums & partner board** | — | ✅ | ✅ |
| **Member-only ladders & events** | — | ✅ | ✅ |
| Priority & early event registration | — | ✅ | ✅✅ |
| Member discounts (gear, clinics) | — | ✅ | ✅ |
| Family accounts (up to 4) | — | — | ✅ |

`[ Start Free ]`            `[ Become a Member — $30/yr ]`

> **Where your dues go:** court time subsidies, beginner clinics, community events, and our advocacy fund for better lighting and flooring. Every membership makes Rock Hill pickleball better for everyone.

*Questions? [Contact us] or come to any open-play session and say hi.*

---

## 6. Color Palette, Typography & Imagery

### Color Palette (pickleball greens/yellows)

| Role | Color | Hex | Use |
|---|---|---|---|
| Primary (court green) | Deep Green | `#2E7D32` | Headers, primary buttons, nav |
| Secondary (energy) | Pickleball Yellow | `#E4E92C` / `#D4E157` | CTAs, highlights, accents |
| Accent (action) | Warm Orange | `#F57C00` | "Playing Today" badges, alerts |
| Court blue (optional) | Teal Blue | `#0097A7` | Secondary tags, links |
| Dark neutral | Charcoal | `#1F2A24` | Body text, footer |
| Light neutral | Off-white | `#F7F9F4` | Backgrounds |
| Success / live | Bright Green | `#43A047` | "Live now" indicators |

**Rule:** green = brand/structure, yellow = attention/CTAs, orange = live/urgent. Don't let yellow-on-white kill contrast — pair yellow with charcoal text for accessibility (WCAG AA).

### Typography
- **Headings:** a bold, sporty sans — **Poppins**, **Montserrat**, or **Archivo** (700/800 weight). Energetic, modern.
- **Body:** **Inter** or **Source Sans Pro** — highly legible on mobile.
- **Accent/numbers:** a condensed face (**Oswald**) for scores, counts, and stats.
- Keep to **two families max** for performance and consistency.

### Imagery
- **Real local photos > stock.** Nothing builds ownership like players seeing themselves. Prioritize authentic shots of your actual courts, sessions, and members.
- Action shots (mid-dink, high-fives), community moments (post-game groups, socials), and the specific venues.
- Use consistent editing (bright, warm, slightly punchy). A simple preset keeps volunteer-shot photos looking cohesive.
- **Iconography:** simple line icons for paddle, court, calendar, players.
- **Hero:** a wide action shot of a real Rock Hill session at golden hour with a green/yellow overlay for text legibility.
- Optimize every image (ShortPixel/WebP) — mobile speed is retention.

---

## 7. Engagement & Content Strategy

**The habit-forming cadence.** The goal is a *reason to return* on every timeframe.

### Weekly (non-negotiable — this is the engine)
- **Monday:** send **"This Week in Rock Hill Pickleball"** newsletter. Fixed 5-block template:
  1. This week's schedule highlights + any court changes/cancellations
  2. Featured event/RSVP (with "12 going")
  3. Player Spotlight
  4. Court report of the week (a real member tip)
  5. One advocacy update / community ask
- **Wednesday:** post the **Player Spotlight** on site + Instagram.
- **Friday:** **"Weekend Play Guide"** — where the courts are open and who's already committed.
- **Ongoing:** keep the calendar and "Playing Today" feed live daily (volunteer + members self-post).

### Monthly
- **Event recap** with photo album after each big session/tournament.
- **Ladder standings update** (SportsPress) — competition drives return visits.
- **Court advocacy update** — petition progress, meetings with facilities, wins.
- **New member welcome** shout-outs.
- **"Court of the Month"** honest deep-dive.

### Seasonal / Quarterly
- Launch a new **ladder league season** (built-in re-engagement moment).
- **Beginner clinic cohort** (grows the funnel + newsletter list).
- **Tournament or social** (photos = weeks of content).
- **Member survey** — feeds advocacy data and makes members feel heard.

### Retention mechanics baked in
- **Email/push reminders** for RSVP'd events.
- **Streaks/badges** (optional, via BuddyPress/gamification) — "played 10 sessions," "first spotlight."
- **User-generated content:** every member post, court report, and Instagram tag = free fresh content. Make posting one tap.

---

## 8. Launch Roadmap

### Phase 0 — Foundation (Week 1–2)
- Domain, fast hosting (managed WP: Kinsta/SiteGround/Cloudways), SSL.
- Install theme (Kadence or Astra — fast, block-friendly) + Elementor Pro (optional).
- Brand kit: colors, fonts, logo.
- Core plugins: The Events Calendar Pro, PMPro, FluentCRM, Rank Math, WP Rocket, Wordfence, UpdraftPlus.

### Phase 1 — MVP (Weeks 2–4) → **LAUNCH**
Focus on the habit loop only:
- ✅ Homepage with dynamic "this week" calendar + CTAs
- ✅ **Courts & Schedules page** (calendar + honest court directory) — the flagship
- ✅ Newsletter signup + first "This Week" send
- ✅ Free membership signup (PMPro free tier)
- ✅ Basic events + RSVP
- ✅ Contact/volunteer form
- **Launch here.** Don't wait for forums/DUPR. Get the schedule live and start the newsletter — everything else is additive.

### Phase 2 — Community (Weeks 5–8)
- Member profiles + skill fields (BuddyPress/FluentCommunity)
- **Find a Partner board + "Playing Today"**
- Forums (4 boards, seeded)
- Paid membership tiers go live
- Smash Balloon Instagram feed + first Player Spotlights

### Phase 3 — Advanced Engagement (Weeks 9–12+)
- Ladder leagues + standings (SportsPress)
- Advocacy petition + court-report routing
- DUPR profile fields + club DUPR link
- Automations (welcome series, reminders, segmented sends)
- Gamification/badges, waitlists, gear-swap marketplace

### Ongoing
- Weekly content cadence (§7)
- Monthly analytics review (which pages/return rate)
- Quarterly feature additions based on member survey

---

## 9. SEO Keyword Targets

### Primary (high intent, build pages/content around these)
- `Rock Hill pickleball` / `Rock Hill pickleball club`
- `Rock Hill pickleball schedule`
- `pickleball courts Rock Hill SC`
- `York County pickleball`
- `where to play pickleball Rock Hill`
- `Rock Hill pickleball open play`

### Court-specific (your courts directory = long-tail gold)
- `Boyd Hill pickleball` / `Boyd Hill pickleball schedule` / `Boyd Hill pickleball alternative`
- `Bleachery Fieldhouse pickleball` / `Bleachery indoor pickleball Rock Hill`
- `Shield of Faith pickleball`
- `Peachtree pickleball Fort Mill`
- `indoor pickleball Rock Hill` / `indoor pickleball near me York County`

### Community / intent
- `pickleball partner Rock Hill` / `find pickleball players Rock Hill`
- `pickleball lessons / clinics Rock Hill`
- `beginner pickleball Rock Hill`
- `pickleball leagues York County SC`
- `pickleball near me` (local pack — Google Business Profile!)

### SEO to-dos
- **Create a Google Business Profile** for the club → wins "pickleball near me" local pack.
- One **dedicated page per court** targeting its keyword cluster.
- Add **LocalBusiness + Event schema** (Rank Math handles this).
- Blog the newsletter content (recaps, court reports) — fresh, keyword-rich, local pages compound over time.
- Get listed on **Places2Play (USA Pickleball)**, local directories, and partner links from facilities.

---

## 10. Maintainability Cheatsheet (for your 1–2 volunteers)

**Weekly ~45 min total:**
1. Update calendar with any schedule changes (10 min)
2. Fill the newsletter template + send (20 min)
3. Post one spotlight + share to Instagram → auto-appears on site (10 min)
4. Skim forums / approve new members (5 min)

**Keep it simple:**
- Use **saved templates** in Elementor/blocks for spotlights, recaps, events — fill-in-the-blank.
- Let **members generate content** (partner posts, court reports, IG tags) — you curate, not create.
- **One dashboard where possible** (the Fluent suite helps here).
- Set up **automations once** (reminders, welcome series) so they run themselves.
- Monthly: check WP Rocket cache, run updates, confirm backups (UpdraftPlus auto).

---

### TL;DR — What makes this stick
The website lives or dies on **three fresh things**: the **weekly calendar**, the **"Playing Today" board**, and the **Monday newsletter**. Nail those three and the community habit forms — everything else (forums, ladders, advocacy, DUPR) makes it *richer*, but those three make it a *daily bookmark*. Launch the MVP fast, keep the schedule honest and current, and let your members become your content engine.
