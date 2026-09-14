# SEO AUDIT REPORT

**Client:** Joe Welch Photography
**Website:** https://joewelchphoto.com
**Audit Date:** 2026-09-14
**Auditor:** Parts of Practice (richard@partsofpractice.com)
**Platform:** Not directly confirmed (see Technical SEO Analysis)

**Live crawl update:** The 2026-09-13 pass was built entirely from search-index reconnaissance because direct crawling was blocked by network egress. On 2026-09-14, `seo-tool/run-audit-joewelchphoto.bat` was run from a machine with normal internet access and completed a full live crawl (39 pages, sitemap-seeded). This report replaces every "Not verified" / "Pending" item in the prior draft's Technical SEO and On-Page sections with real, page-by-page data. The regenerated HTML report (`Joe-Welch-Photography-SEO-Audit-2026-09-14.html`) is the client-facing deliverable; this file is the working analysis behind it. The **brand-collision risk with joewelchphotography.com remains the top finding** — the live crawl doesn't change that, since it's a search-index/entity issue, not a technical one.

**Client-stated goals (2026-09-14, direct from Joe Welch):** Joe reached out directly — he's the brother of existing client Karen Welch Buttars — asking for help improving organic visibility. He was explicit that the goal is **qualified inquiries, not traffic volume**, and named three priority service lines: **aerial/drone photography, commercial photography, and hotel/resort photography**. Critically, he named **Fort Lauderdale and Broward County as the primary target market, with Miami-Dade and Palm Beach as secondary** — this reverses the market-priority assumption in the 2026-09-13 pass, which (based only on search-index titles) had treated Miami-Dade as primary. Section 2, 10, and the Appendix below have been reordered to reflect his actual stated priorities.

**Scoring engine fix (2026-09-14):** `seo-tool/audit.js` previously only deducted score for *missing* titles/H1s, not for present-but-wrong ones (too short, too long, or duplicated) — those fell into a small, capped, catch-all bucket regardless of severity. Fixed so poorly-sized titles and duplicate H1s are now scored as their own line items, and fixed schema-type detection to unwrap `@graph`-structured JSON-LD (previously showed "Unknown" for every page — a common false negative on Yoast/WordPress sites). This is a tool-wide fix affecting all clients going forward, not something specific to this site.

---

## 1. Overall SEO Health Score: 70 / 100

**Summary:** The live crawl confirms the underlying content is stronger than a search-index-only pass could show: no missing titles, no missing meta H1s, no missing canonical tags, and confirmed JSON-LD schema present site-wide (`Organization`, `WebSite`, `WebPage`, `BreadcrumbList`). The score is held back by three fixable, mechanical issues — 15 of 39 pages missing a meta description, 32 pages with title tags outside the ideal 30–65 character range, and 1 page (the homepage) with two H1 tags — plus the still-unresolved brand-collision and duplicate-URL items carried over from the prior pass. Note: this score is **not directly comparable to the 2026-09-13 draft's 58/100 or the first live-crawl run's 80/100** — both the source data and the scoring formula changed (see notes above).

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Disambiguate the brand from the unrelated, near-identically named joewelchphotography.com via `LocalBusiness`/`Person` schema and specialty-first copy — confirmed via live crawl that current schema (`Organization`, `WebSite`, `WebPage`, `BreadcrumbList`) does **not** already cover this | High | Medium |
| 2 | Re-lead every title/meta description with **Fort Lauderdale, Broward County** — per Joe's direct guidance, the current geo-stuffed titles (Miami-first, then a string of other cities) don't match his actual priority market, and reordering them is effectively free alongside the title-length fix below | High | Low |
| 3 | Write meta descriptions for the 15 pages currently missing one (homepage, `/portfolio`, `/sitemap`, `/blog`, and 11 FAQ sub-pages), and fix the 32 pages with title tags outside 30–65 characters — do these together since both touch the same `<head>` tags | High | Low |
| 4 | Reprioritize on-page emphasis toward aerial/drone, commercial, and hotel/resort photography (Joe's three named priority lines) over residential real estate, which the site currently leads with | Medium | Medium |
| 5 | Fix the homepage's duplicate H1 tag, expand the 14 thin-content pages (<150 words — mostly FAQ answers and `/blog`), and confirm whether `/real-estate-photography.html` / `/hdr-photography.html` still exist | Medium | Medium |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] Add meta descriptions to the 15 pages missing one — see list in Section 6
- [ ] Add `Person`/`LocalBusiness` schema naming the specific specialty ("aerial, commercial & real estate photography") and service area, led by Fort Lauderdale/Broward per Joe's stated priority (Miami-Dade, Palm Beach, Caribbean secondary) — see Appendix for starter code
- [ ] Fix the homepage's duplicate H1 tag (currently has 2 — should have exactly one)
- [ ] Fill in blank image alt text on 23 pages (the attribute exists in the code, it's just empty — ranges from 3 images on `/pricing` up to 35 on the homepage)
- [ ] Standardize every service-page title to `{Service} Photographer in Fort Lauderdale | Joe Welch Photography`, keep to 30–65 characters (see Appendix for the full list)
- [ ] Correct or confirm the Yelp listing's city — either update it to match the site's Miami-Dade-led messaging or update the site if Pompano Beach is in fact the business address

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] Expand the 14 thin-content pages (<150 words): 11 individual FAQ answer pages (21–94 words each), `/contact` (116 words), `/sitemap` (137 words), and `/blog` (19 words)
- [ ] Confirm whether `/real-estate-photography.html` and `/hdr-photography.html` still resolve; if they do, 301-redirect them into their canonical replacements (`/residential-real-estate-photography` and `/vacation-rentals-photography` respectively) — they weren't discovered by this crawl (sitemap + internal links), so they may already be gone, or simply orphaned
- [ ] Pitch inclusion in 3–5 Miami "best of real estate/aerial photographer" roundups (Wonderful Machine, LUXVT, Expertise.com) — none currently name Joe Welch Photography despite 15+ years and 1,000+ shoots
- [ ] Publish the first 1–2 long-tail guides (e.g., "How to Prepare a Listing for Real Estate Photos," "Florida Drone Photography Permits Explained") and link each to the relevant service page
- [ ] Consolidate NAP/service-area language across Google Business Profile, Yelp, LinkedIn, ZoomInfo, and Instagram so every listing agrees with the site

---

## 5. Long-Term / Strategic Recommendations

- [ ] Sustain a monthly content cadence (case studies, guides) tied to service pages to build topical authority for "real estate photography [city]" queries
- [ ] Track branded-search share vs. joewelchphotography.com over time once Search Console is verified and connected to the `seo-tool` pipeline for live performance metrics
- [ ] Quarterly technical crawl and Core Web Vitals check-in via the `seo-tool` pipeline (now confirmed working end-to-end from a live-crawl-capable machine)
- [ ] Expand outreach beyond the initial roundup list to real estate agent/brokerage sites that already use Joe Welch's photography, for reciprocal or testimonial-style links

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Present on all 39 pages | 22 pages exceed 65 characters (will truncate in results); 10 pages are under 30 characters (under-using keyword space) |
| URL Structure | Clean in this crawl | No legacy `.html` duplicates surfaced — `/real-estate-photography.html` and `/hdr-photography.html` were not found via sitemap or internal links; confirm directly whether they still exist |
| Meta Descriptions | **15 of 39 pages missing** | Homepage, `/portfolio`, `/sitemap`, `/blog`, and all 11 FAQ sub-pages — see full list in Section 7 |
| H1 Tags | Present on all pages | 1 page (homepage) has 2 H1 tags — should be exactly one |
| Heading Hierarchy (H2–H4) | Not flagged | No issues surfaced by the crawler |
| Schema / Structured Data | **Confirmed present, but not the disambiguating type** | Every page carries `Organization`, `WebSite`, `WebPage`, and `BreadcrumbList` JSON-LD (likely via an SEO plugin's `@graph` output). No page carries `LocalBusiness` or `Person` — the specific type that would help disambiguate from joewelchphotography.com (see Priority #1) |
| Canonical Tags | None flagged missing | — |
| XML Sitemap | Present and used | Sitemap seeded 29 of the 39 crawled URLs; the remaining 10 (mostly `/portfolio/*` variants) were reached via internal links |
| Robots.txt | Not directly reported by this crawl pass | — |
| Page Speed | Not measured by this crawler | Recommend a PageSpeed Insights run |
| Mobile Friendliness | Not measured by this crawler | Recommend Google's Mobile-Friendly Test |
| HTTPS / SSL | Present | Site serves over HTTPS |
| Broken Links | Not measured by this crawler | — |
| Image Alt Text | **23 of 39 pages have blank alt attributes** | Alt text exists in the code but is empty — ranges from 3 images (`/pricing`) to 35 (homepage); see Section 7 for the full per-page breakdown |

**Key Technical Issues:**
- No entity-disambiguating schema despite a live, unrelated competing domain sharing almost the exact business name (see Section 8) — still the single highest-leverage fix on the site
- 15 pages missing meta descriptions is the single largest mechanical score deduction (-8 of the 20 points lost)
- The two legacy duplicate URLs (`/real-estate-photography.html`, `/hdr-photography.html`) flagged in the 2026-09-13 pass did not appear anywhere in this live crawl — good news if they've been removed/redirected, but worth a direct check rather than assuming so, since the crawler only follows the sitemap and internal links and wouldn't find an orphaned page either way

---

## 7. On-Page SEO & Content Analysis

**Pages missing a meta description (15):**
`/` (homepage), `/portfolio`, `/sitemap`, `/blog`, `/faqs/what-is-the-turnaround-time`, `/faqs/how-many-photos-will-i-get`, `/faqs/what-areas-do-you-cover`, `/faqs/are-there-any-legal-restrictions-when-using-a-drone-to-photograph-properties`, `/faqs/what-is-the-resolution-or-file-size-of-the-photos`, `/faqs/do-i-need-to-be-there`, `/faqs/where-are-you-located`, `/faqs/how-long-will-it-take-to-photograph-my-listing-property`, `/faqs/do-you-fly-above-water`, `/faqs/what-forms-of-payment-do-you-take`, `/faqs/for-aerial-photography-when-is-the-best-time-of-day-to-photograph-my-listing-property`

**Pages with blank image alt text (23), image count per page:**
`/` (35), `/residential-real-estate-photography` & `/portfolio/residential` & `/resort-photography` & `/portfolio/resort` & `/vacation-rentals-photography` & `/portfolio/vacation-rentals-photography` (15 each), `/aerial-photography` & `/portfolio/aerial` (14 each), `/architectural-photography` & `/commercial-photography` & `/construction-photography` & `/dusk-twilight-photography` & `/portfolio/commercial` & `/portfolio/construction` & `/portfolio/architectural` & `/portfolio/dusk` (13 each), `/portfolio` (9), `/insurance-photography` & `/portfolio/insurance` (10 each), `/clients` (11), `/about` (4), `/pricing` (3)

**Thin-content pages (<150 words, 14):**
`/blog` (19), `/faqs/where-are-you-located` (23), `/faqs/do-you-fly-above-water` (21), `/faqs/what-forms-of-payment-do-you-take` (29), `/faqs/what-areas-do-you-cover` (37), `/faqs/how-long-will-it-take-to-photograph-my-listing-property` (39), `/faqs/for-aerial-photography-when-is-the-best-time-of-day-to-photograph-my-listing-property` (35), `/faqs/are-there-any-legal-restrictions-when-using-a-drone-to-photograph-properties` (32), `/faqs/what-is-the-turnaround-time` (50), `/faqs/do-i-need-to-be-there` (52), `/faqs/what-is-the-resolution-or-file-size-of-the-photos` (62), `/faqs/how-many-photos-will-i-get` (94), `/contact` (116), `/sitemap` (137)

**Titles confirmed live (all 39 pages have one):**
- Homepage title is only 4 characters — effectively just the site name, missing keyword opportunity (see Appendix for a suggested replacement)
- `/portfolio`, `/sitemap`, and `/blog` are similarly short, generic titles (9, 7, and 4 characters)
- 22 service/portfolio pages exceed 65 characters, largely from stacking multiple cities/regions into the title (e.g. `/dusk-twilight-photography` at 77 chars includes "Miami, Ft. Laud, Palm Bch, FL & Caribbean")
- `/about` (82 chars) and `/residential-real-estate-photography` (81 chars) are the longest

**Content Quality Summary:**
- Word count: Now confirmed live — ranges from 19 words (`/blog`) to 461 words (`/vacation-rentals-photography`); most core service pages sit in a healthy 240–460 word range
- Keyword targeting: Strong — consistent, natural use of the Miami-Dade/Broward/Palm Beach/Caribbean service triangle across nearly every page title
- Internal linking: Sitemap + internal links surfaced all 10 `/portfolio/*` sub-pages plus every FAQ sub-page, suggesting reasonably complete internal linking
- CTAs: Not scored by this crawler — confirm the "100% satisfaction guarantee" / free-reshoot policy is prominent on every service page, not just About

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [ ] Author/team bios with credentials
- [ ] License numbers or certifications listed
- [ ] Professional affiliations
- [x] Media mentions or press coverage (Voyage MIA Magazine feature interview)
- [x] Client testimonials or reviews (`/reviews` page confirmed live, 401 words)
- [ ] Awards or recognition
- [x] About page with founder/team story

**E-E-A-T Gaps:**
- No visible license/certification disclosure — for aerial/drone work specifically, FAA Part 107 remote pilot certification is a strong, verifiable trust signal that's currently unclaimed on-site
- No dedicated case studies or before/after project pages despite 1,000+ completed shoots — a significant, currently untapped trust and content asset

**Local SEO:**
- Google Business Profile: Not directly verified in this pass — recommend confirming primary category, service-area settings, and that all target cities are reflected
- NAP Consistency: **Issue, and now easier to resolve** — Yelp lists the business under Pompano Beach, FL, which is actually a better geographic match for Joe's stated primary market (Fort Lauderdale/Broward) than the site's current Miami-Dade-led titles are. Update the site's geo emphasis to match Yelp and Joe's real priority, rather than the other way around.
- Geographic targeting in content: Present but misordered — the Miami-Dade/Broward/Palm Beach/Caribbean service area is stated consistently across nearly every page title, but leads with Miami-Dade when Fort Lauderdale/Broward is the client's actual priority market (see Priority #2)
- Local citations / directories: Yelp, LinkedIn (Joseph Welch), Instagram (@joewelchphotography), and ZoomInfo listings found; none yet cross-checked for full NAP consistency
- **Brand/entity risk (critical, confirmed by this crawl):** A separate, unrelated photographer operates at joewelchphotography.com with its own About/Contact pages and portfolio. Search queries for "Joe Welch Photography" return both businesses, with no schema or on-page signal currently disambiguating them. The live crawl confirms the site's existing JSON-LD (`Organization`, `WebSite`, `WebPage`, `BreadcrumbList`) does not include a `LocalBusiness` or `Person` type, so this gap is real and not just unverified — it's the single highest-leverage fix on the site.

---

## 9. Conversion & User Experience Issues

- **CTAs:** Not directly measured by this crawler — indexed copy suggests a strong angle already exists (100% satisfaction guarantee / free reshoot); confirm it's prominent on every service page, not just About
- **Contact friction:** `/contact` (confirmed live, 116 words) and `/pricing` (confirmed live, 276 words) suggest low friction to inquire
- **Mobile UX:** Not measured by this crawler — recommend Google's Mobile-Friendly Test
- **Page load perception:** Not measured by this crawler — recommend PageSpeed Insights
- **Trust signals above the fold:** Not measured by this crawler, though testimonials and the 15-year/1,000-shoot track record are confirmed present on `/reviews` and `/about`
- **Navigation clarity:** Confirmed via crawl — 7 core service pages, each mirrored under `/portfolio/*`, plus 11 FAQ sub-pages and a dedicated `/clients` page; a full, if slightly duplicated, site structure (service pages and portfolio pages share nearly identical titles)

**Key Fixes:**
- Fix the duplicate title pattern between each service page and its `/portfolio/*` counterpart (e.g. `/aerial-photography` and `/portfolio/aerial` currently share the exact same title) — differentiate them or canonicalize one to the other
- Confirm whether `/real-estate-photography.html` and `/hdr-photography.html` still exist; if so, clean them up per Section 4

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities (reordered 2026-09-14 to match Joe's stated priorities — Fort Lauderdale/Broward primary, aerial/commercial/resort the priority service lines):**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| aerial drone photographer fort lauderdale | Commercial | Medium | `/aerial-photography` |
| commercial photographer fort lauderdale | Commercial | Medium | `/commercial-photography` |
| hotel resort photographer fort lauderdale | Commercial | Low–Medium | `/resort-photography` |
| aerial drone photographer broward county | Commercial | Medium | `/aerial-photography` |
| real estate photographer fort lauderdale | Commercial | Medium | `/residential-real-estate-photography` |
| dusk twilight real estate photography | Commercial | Low–Medium | `/dusk-twilight-photography` |
| vacation rental photography fort lauderdale | Commercial | Low | `/vacation-rentals-photography` |
| how to prepare a home for real estate photos | Informational | Low | New guide |
| drone photography permits florida | Informational | Low | New guide |
| joe welch photography real estate | Branded | N/A | Homepage, About — reinforce with `LocalBusiness` schema to win this query outright |

**Content Gap Analysis:**
- No case studies or before/after project pages despite 1,000+ completed shoots — the single biggest missed content asset on the site
- No visible FAA Part 107 / licensing disclosure for the aerial/drone service line
- 11 FAQ pages average under 50 words each — thin enough that expanding them with fuller, keyword-rich answers is likely the fastest content win on the site

**Recommended Content Pieces:**
1. "How to Prepare Your Listing for a Real Estate Photo Shoot" — targets informational intent, links to `/residential-real-estate-photography`
2. "Do You Need a Permit to Fly a Drone Over Florida Real Estate?" — targets informational/trust intent, doubles as an E-E-A-T page for `/aerial-photography`
3. "Recent Work: [Property Name] Twilight Shoot" — a repeatable case-study format tied to `/dusk-twilight-photography`

---

## 11. Next Steps & Action Plan

1. **Week 1 — Site owner:** Write meta descriptions for the 15 pages missing one, fix the homepage's duplicate H1, and add the disambiguating `LocalBusiness` schema (Appendix)
2. **Week 1 — Site owner:** Confirm whether `/real-estate-photography.html` and `/hdr-photography.html` still exist; redirect if so
3. **Week 2 — Site owner:** Standardize title tags site-wide using the formula in the Appendix; fill in blank image alt text on the 23 affected pages
4. **Weeks 2–3 — Site owner:** Expand the 14 thin-content pages, prioritizing the FAQ answers
5. **Weeks 3–4 — Parts of Practice:** Reconcile NAP/service-area info across GBP, Yelp, LinkedIn, ZoomInfo; begin outreach to 3–5 "best of Miami photographer" roundups
6. **Ongoing — Site owner:** Publish one case study or guide per month, starting with the three ideas above
7. **Quarterly — Parts of Practice:** Re-run `seo-tool/run-audit-joewelchphoto.bat` to track score trend now that the live pipeline is confirmed working

---

## Appendix

### Suggested Title Tags
*Reordered 2026-09-14 to lead with Fort Lauderdale/Broward (Joe's stated primary market) and prioritize his three named service lines — aerial/drone, commercial, and hotel/resort photography.*

| Page | Recommended Title Tag |
|---|---|
| `/` (homepage) | `Aerial, Commercial & Real Estate Photographer in Fort Lauderdale \| Joe Welch Photography` |
| `/aerial-photography` | `Aerial & Drone Photographer in Fort Lauderdale, Broward County \| Joe Welch Photography` |
| `/commercial-photography` | `Commercial Photographer in Fort Lauderdale, Broward County \| Joe Welch Photography` |
| `/resort-photography` | `Hotel & Resort Photographer in Fort Lauderdale \| Joe Welch Photography` |
| `/residential-real-estate-photography` | `Real Estate Photographer in Fort Lauderdale \| Joe Welch Photography` |
| `/construction-photography` | `Construction Photographer in Fort Lauderdale, Broward County \| Joe Welch Photography` |
| `/dusk-twilight-photography` | `Dusk & Twilight Real Estate Photographer \| Joe Welch Photography` |
| `/vacation-rentals-photography` | `Vacation Rental Photographer in Fort Lauderdale \| Joe Welch Photography` |
| `/pricing` | `Photo Packages & Pricing \| Joe Welch Photography` |
| `/portfolio` | `Portfolio \| Joe Welch Photography Aerial, Commercial & Real Estate Photos` |
| `/sitemap` | `Sitemap \| Joe Welch Photography` |
| `/blog` | `Blog \| Joe Welch Photography` |

### Suggested Meta Descriptions
| Page | Recommended Meta Description |
|---|---|
| `/` (homepage) | `Aerial/drone, commercial, and hotel & resort photography serving Fort Lauderdale & Broward County, with Miami-Dade & Palm Beach. 15+ years, 1,000+ shoots, 100% satisfaction guarantee.` |
| `/portfolio` | `Browse aerial, commercial, resort, residential, and construction photography by Joe Welch Photography, serving Fort Lauderdale, Broward County, and South Florida.` |
| `/aerial-photography` | `FAA-compliant aerial & drone photography for Fort Lauderdale & Broward County real estate, resorts, and construction projects. Serving Miami-Dade, Palm Beach & the Caribbean.` |
| `/commercial-photography` | `Commercial photography for Fort Lauderdale & Broward County businesses, hotels, and properties. 15+ years, 1,000+ shoots, 100% satisfaction guarantee.` |
| `/resort-photography` | `Hotel & resort photography for Fort Lauderdale, Broward County, and South Florida hospitality properties. Book Joe Welch Photography.` |
| `/residential-real-estate-photography` | `Professional real estate photography for Fort Lauderdale, Broward, Miami-Dade & Palm Beach listings. 15+ years, 1,000+ shoots, 100% satisfaction guarantee.` |

### Schema Code
```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": "https://joewelchphoto.com/#/schema/LocalBusiness",
  "name": "Joe Welch Photography",
  "description": "Aerial/drone, commercial, hotel & resort, real estate, and construction photography serving Fort Lauderdale and Broward County, FL, with Miami-Dade, Palm Beach, and the Caribbean as secondary markets.",
  "url": "https://joewelchphoto.com/",
  "areaServed": [
    "Broward County, FL", "Fort Lauderdale, FL", "Miami-Dade County, FL", "Palm Beach County, FL", "Caribbean"
  ],
  "sameAs": [
    "https://www.instagram.com/joewelchphotography/",
    "https://www.linkedin.com/in/joewelchphoto"
  ]
}
```
*Note: Confirm the exact legal business name, address, and phone number directly with the client before publishing — none of these were independently verified — and replace `sameAs` with the client's actual, confirmed profile URLs.*

### Additional Notes
- **Data sources for this pass:** Live crawl via `seo-tool/audit.js` (39 pages, sitemap-seeded) for all Technical SEO and On-Page findings; the brand-collision and NAP/Local sections still rely on indexed search results and public listings (Yelp, LinkedIn, Instagram), since those live outside what a site crawl can see.
- **Performance metrics:** No Google Search Console data was available to merge into this pass — the crawler's performance-metrics step was skipped. Connect GSC (`gsc-auth.js` / `gsc.js` in `seo-tool/`) before the next run to pull live impressions/clicks/position data automatically.
- **Recommended tools for ongoing monitoring:** Google Search Console (verify site), PageSpeed Insights / Lighthouse, Google's Mobile-Friendly Test, and the Rich Results structured-data test.
- **Standing flag:** the joewelchphotography.com brand collision should be re-checked each audit cycle until schema and citation work has had time to disambiguate the two entities in search results.
