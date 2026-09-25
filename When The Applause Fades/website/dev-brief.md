# Web Developer Brief — *When the Applause Fades*

*Project summary for a web developer building an author/book page. Fields in brackets need the author's input before build can be scoped/quoted.*

---

## The Project

An author website / book landing page for a debut (or [nth]) inspirational contemporary romance novel, **When the Applause Fades**, launching [publication date TBD]. Primary goals: sell the book (drive to retail buy links), build a reader email list (newsletter / ARC signup), and give press/bloggers/bookstagrammers a place to get assets and info.

Scope is intentionally modest — this is a book marketing site, not an app. Expect a small number of pages, mobile-first, fast-loading, content-driven.

---

## The Book (for tone/context)

| | |
|---|---|
| **Title** | When the Applause Fades |
| **Genre** | Inspirational / faith-based contemporary romance |
| **Heat level** | Sweet / closed-door |
| **Length** | ~67,300 words, 54 chapters + 2 epilogues |
| **Status** | Manuscript complete; going through editing before publication |

**Tagline:** *Some love stories don't chase the spotlight. They wait for you in the quiet.*

**Logline:** A widowed church elder in a small North Carolina beach town and a world-famous pop star fall for each other after a chance photo shoot — and have to decide whether their private, ordinary love can survive both of their public worlds finding out.

**Full blurb, comp titles, tropes, and content notes:** see `marketing/blurbs.md` and `README.md` in this project — all finalized copy the developer can drop straight into the site.

---

## Pages Needed

1. **Home** — hero section (tagline + cover image once available), short hook/about blurb, newsletter signup CTA
2. **Book page** — full back-cover blurb, buy links (Amazon/KU + other retailers once decided), maybe a short excerpt
3. **About the author** — short (50-word) and long (150-word) bio
4. **Press kit** — book details, blurb, author bio, downloadable assets (cover images, author photo, banner), for bloggers/podcasters/media

This mirrors the structure already scaffolded in `website/README.md` if useful reference.

---

## Visual / Brand Direction

The book's core tension is **fame vs. quiet, ordinary life** — a global pop star and a widowed small-town church elder. The site's tone should lean toward the *quiet* side of that tension rather than a glitzy celebrity aesthetic: warm, coastal, unhurried. Think beach town in golden-hour light rather than stadium lights — that contrast is the book's whole emotional pitch, and a loud/flashy site would undersell it.

**Not yet decided / needed from the author before final design:**
- Color palette (primary/secondary/accent/background hex codes)
- Fonts (heading/body/accent)
- Cover art — **not yet created.** `assets/` folder is scaffolded but empty; cover, author headshot, and banner graphics are all still pending. The developer will likely need to build with placeholder imagery first, or this needs to be sequenced after cover design is done.

---

## Functional Requirements

- **Newsletter / email capture** — platform not yet chosen (e.g. MailerLite, ConvertKit, Mailchimp); needs to plug into whatever the author selects
- **Buy links** — Amazon at minimum; other retailers TBD depending on distribution choice (KDP-exclusive vs. wide)
- **Mobile-responsive**, fast-loading — this is a marketing/conversion page, not a heavy app
- **Downloadable press assets** on the press kit page once available
- No login/account system, no e-commerce checkout needed (sales happen off-site via retailer links)

---

## What's Still Undecided (flag to author, not the developer, to resolve)

- Platform: [Squarespace / WordPress / Wix / custom / a BookFunnel landing page instead of a full site?]
- Domain name
- Author name as it will appear publicly (pen name or real name — not yet finalized in project files)
- Publication date, pricing, ISBNs
- Newsletter platform
- Social media handles to link
- Cover art, author photo, and full branding/color/font decisions

---

## What's Ready to Hand Off Now

- Finalized tagline, blurb (short/long), and one-liner (`marketing/blurbs.md`)
- Comp titles and tropes/heat-level/content notes (`README.md`, `marketing/blurbs.md`)
- Page structure and copy checklist (`website/README.md`)
- Press kit template with sections mapped out (`website/press-kit.md`) — needs author bio and asset links filled in once available
