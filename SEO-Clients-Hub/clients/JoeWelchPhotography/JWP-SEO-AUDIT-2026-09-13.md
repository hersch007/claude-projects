# SEO AUDIT REPORT

**Client:** Joe Welch Photography
**Website:** https://joewelchphoto.com
**Audit Date:** 2026-09-13
**Auditor:** Parts of Practice (richard@partsofpractice.com)
**Platform:** Not directly confirmed (see Technical SEO Analysis)

---

## 1. Overall SEO Health Score: 58 / 100

**Summary:** Joe Welch Photography has strong underlying content — clear service breadth, consistent geo-targeting, and real trust signals (15+ years, 1,000+ shoots, a 100% satisfaction guarantee) — but a critical, unresolved brand-name collision with a separate business and live legacy/duplicate URLs are actively splitting search visibility, and a full technical layer (schema, page speed, alt text) still needs to be confirmed with a live crawl.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Disambiguate the brand from the unrelated, near-identically named joewelchphotography.com via schema markup and specialty-first copy | High | Medium |
| 2 | 301-redirect the legacy `/real-estate-photography.html` into its canonical replacement, `/residential-real-estate-photography/` | High | Low |
| 3 | Rename and redirect `/hdr-photography.html` to a slug that matches its actual vacation-rental content | Medium | Low |
| 4 | Standardize the title-tag formula and geo string across every service page | Medium | Low |
| 5 | Reconcile the Yelp listing's Pompano Beach address with the site's stated Miami-Dade/Broward/Palm Beach service area | Medium | Low |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] Add `Person`/`LocalBusiness` schema naming the specific specialty ("real estate & aerial photography") and service area (Miami-Dade, Broward, Palm Beach, Caribbean) — see Appendix for starter code
- [ ] 301-redirect `/real-estate-photography.html` → `/residential-real-estate-photography/` and set a self-referencing canonical on the surviving page
- [ ] Rename `/hdr-photography.html` → `/vacation-rental-photography/` with a 301 from the old slug, and retitle it to match (see Appendix)
- [ ] Standardize every service-page title to `{Service} Photographer in Miami | Joe Welch Photography`, keep under ~60 characters, and move secondary geography (Ft. Lauderdale, Palm Beach, Caribbean) into the meta description and body copy
- [ ] Correct or confirm the Yelp listing's city — either update it to match the site's Miami-Dade-led messaging or update the site if Pompano Beach is in fact the business address

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] Run `seo-tool/run-audit-joewelchphoto.bat` locally to complete a live technical crawl (meta descriptions, H1s, image alt text, canonical tags, page speed) — this audit's Technical SEO section is built from search-index reconnaissance because direct crawling wasn't reachable from the environment this pass was written in
- [ ] Pitch inclusion in 3–5 Miami "best of real estate/aerial photographer" roundups (Wonderful Machine, LUXVT, Expertise.com) — none currently name Joe Welch Photography despite 15+ years and 1,000+ shoots
- [ ] Publish the first 1–2 long-tail guides (e.g., "How to Prepare a Listing for Real Estate Photos," "Florida Drone Photography Permits Explained") and link each to the relevant service page
- [ ] Consolidate NAP/service-area language across Google Business Profile, Yelp, LinkedIn, ZoomInfo, and Instagram so every listing agrees with the site

---

## 5. Long-Term / Strategic Recommendations

- [ ] Sustain a monthly content cadence (case studies, guides) tied to service pages to build topical authority for "real estate photography [city]" queries
- [ ] Track branded-search share vs. joewelchphotography.com over time once Search Console is verified
- [ ] Quarterly technical crawl and Core Web Vitals check-in via the seo-tool pipeline
- [ ] Expand outreach beyond the initial roundup list to real estate agent/brokerage sites that already use Joe Welch's photography, for reciprocal or testimonial-style links

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Present, inconsistent | Present on every page found; long, geo-stuffed, and abbreviated inconsistently page to page ("Bch" vs. "Beach," "Ft. Laud" vs. "Ft. Lauderdale") |
| URL Structure | Mixed / legacy debris | Clean, extension-less paths alongside live legacy `.html` URLs; two URLs target the identical keyword |
| Meta Descriptions | Not verified | Direct crawl blocked in this environment — confirm via Search Console or PageSpeed Insights |
| H1 Tags | Not verified | Same as above |
| Heading Hierarchy (H2–H4) | Not verified | Same as above |
| Schema / Structured Data | Not verified | Same as above — high-value fix given the entity-confusion risk below |
| Canonical Tags | Not verified | Should be set once the legacy `.html` duplicate is resolved |
| XML Sitemap | Present (HTML sitemap confirmed) | `/sitemap/` (HTML) found in the index; `sitemap.xml` not verified from this environment |
| Robots.txt | Not verified | Blocked from direct crawl |
| Page Speed | Not verified | Recommend a PageSpeed Insights run |
| Mobile Friendliness | Not verified | Recommend Google's Mobile-Friendly Test |
| HTTPS / SSL | Present | Site serves over HTTPS per every indexed URL |
| Broken Links | Not verified | — |
| Image Alt Text | Not verified | — |

**Key Technical Issues:**
- No entity-disambiguating schema despite a live, unrelated competing domain sharing almost the exact business name (see Local & E-E-A-T section) — this is the single highest-leverage fix on the site
- Two live URLs (`/residential-real-estate-photography/` and `/real-estate-photography.html`) carry the identical title and appear to target the identical keyword — classic duplicate-content/keyword-cannibalization risk
- `/hdr-photography.html`'s slug ("HDR," an editing technique) no longer matches its actual content ("Vacation Rentals Photography") — a leftover from a past restructuring
- A meaningful slice of the technical layer (meta descriptions, H1s, schema, page speed, alt text) could not be verified directly in this pass because outbound access to joewelchphoto.com was blocked by this environment's network egress; the `seo-tool` crawler in this repo will close that gap when run locally

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: Not confirmed — search results surfaced only the site name ("Joe Welch Photography"), not a literal `<title>` string; verify with a live crawl
- Notes: —

**Contact (`/contact/`):**
- Title: `Contact Joe Welch Photography, Serving Florida & the Caribbean`
- Notes: Clear and on-brand

**About (`/about/`):**
- Title: `About Joe Welch Photography | Real Estate & Aerial Photography | Miami, S. Florida`
- Notes: Good — leads with the specialty, which helps disambiguate from the unrelated joewelchphotography.com

**Pricing (`/pricing/`):**
- Title: `Photo Packages & Pricing or Call for Quote, Joe Welch Photography, S. Florida`
- Notes: Reads as a run-on; tighten to something like `Photo Packages & Pricing | Joe Welch Photography`

**Sitemap (`/sitemap/`):**
- Title: `Sitemap`
- Notes: Generic, unoptimized title on a page that's otherwise indexable — low priority, but worth a `noindex` or a more descriptive title

**Residential Real Estate Photography (`/residential-real-estate-photography/`) — and its duplicate, `/real-estate-photography.html`:**
- Title (both URLs): `Residential Real Estate Photographer | Miami-Dade, Broward, Palm Beach, Caribbean`
- Notes: Two live, indexed URLs sharing one title and (apparently) one target keyword — resolve per Quick Wins

**Commercial Photography (`/commercial-photography/`):**
- Title: `Commercial Photographer | Miami, Ft. Lauderdale, Palm Beach & Caribbean`

**Construction Photography (`/construction-photography/`):**
- Title: `Construction Photographer | Miami, Ft. Lauderdale, Palm Beach & Caribbean`

**Hotel & Resort Photography (`/resort-photography/`):**
- Title: `Hotel & Resort Photographer | Miami, Ft. Lauderdale, Palm Bch & Caribbean`
- Notes: "Palm Bch" abbreviation breaks the pattern used elsewhere

**Aerial/Drone Photography (`/aerial-photography/`):**
- Title: `Aerial/Drone Photographer | Miami-Dade, Ft. Lauderdale, Palm Beach & Caribbean`

**Dusk/Twilight Photography (`/dusk-twilight-photography/`):**
- Title: `Dusk Photographer for Real Estate | Miami, Ft. Laud, Palm Bch, FL & Caribbean`
- Notes: Both "Ft. Laud" and "Palm Bch" abbreviations appear only on this page — the most inconsistent title found

**Vacation Rental / HDR (`/hdr-photography.html`):**
- Title: `Vacation Rentals Photography, Miami & Palm Beach Homes for Rent`
- Notes: Content and title are about vacation rentals; the URL slug still says "hdr" — rename per Quick Wins

**Content Quality Summary:**
- Word count: Not verified (crawl blocked)
- Keyword targeting: Strong — consistent, natural use of the Miami-Dade/Broward/Palm Beach/Caribbean service triangle across nearly every page title
- Internal linking: Not verified
- CTAs: Strong signal from indexed copy — a "100% satisfaction guarantee" / free-reshoot policy is a genuine differentiator worth surfacing higher on service pages if it isn't already

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [ ] Author/team bios with credentials
- [ ] License numbers or certifications listed
- [ ] Professional affiliations
- [x] Media mentions or press coverage (Voyage MIA Magazine feature interview)
- [x] Client testimonials or reviews (quoted testimonials referencing responsiveness and professionalism found via search index)
- [ ] Awards or recognition
- [x] About page with founder/team story

**E-E-A-T Gaps:**
- No visible license/certification disclosure — for aerial/drone work specifically, FAA Part 107 remote pilot certification is a strong, verifiable trust signal that's currently unclaimed on-site
- No dedicated case studies or before/after project pages despite 1,000+ completed shoots — a significant, currently untapped trust and content asset

**Local SEO:**
- Google Business Profile: Not directly verified in this pass — recommend confirming primary category, service-area settings, and that all target cities are reflected
- NAP Consistency: **Issue** — Yelp lists the business under Pompano Beach, FL, while the site's own titles and copy lead with Miami-Dade as the primary market; reconcile before further citation building
- Geographic targeting in content: Strong — the Miami-Dade/Broward/Palm Beach/Caribbean service area is stated consistently across nearly every page title
- Local citations / directories: Yelp, LinkedIn (Joseph Welch), Instagram (@joewelchphotography), and ZoomInfo listings found; none yet cross-checked for full NAP consistency
- **Brand/entity risk (critical):** A separate, unrelated photographer operates at joewelchphotography.com with its own About/Contact pages and portfolio. Search queries for "Joe Welch Photography" return both businesses, with no schema or on-page signal currently disambiguating them. This is a structural risk to branded-search ownership, not a cosmetic issue.

---

## 9. Conversion & User Experience Issues

- **CTAs:** Not directly verified, but indexed copy suggests a strong angle already exists (100% satisfaction guarantee / free reshoot) — confirm it's prominent on every service page, not just About
- **Contact friction:** A dedicated `/contact/` page and a `/pricing/` page ("Photo Packages & Pricing or Call for Quote") suggest low friction to inquire — not directly verified
- **Mobile UX:** Not verified — recommend Google's Mobile-Friendly Test
- **Page load perception:** Not verified — recommend PageSpeed Insights
- **Trust signals above the fold:** Not verified, though testimonials and the 15-year/1,000-shoot track record exist somewhere on-site per search snippets — confirm placement
- **Navigation clarity:** Not verified directly, but the URL/title inventory suggests a full, if slightly untidy, service menu (7 distinct service pages)

**Key Fixes:**
- Once the live crawl runs, confirm the guarantee/trust messaging appears on every service page's above-the-fold area, not only the homepage or About
- Clean up the legacy `.html` URLs (Quick Wins) so navigation and internal links point at one canonical version of each service

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| real estate photographer miami | Commercial | Medium | `/residential-real-estate-photography/` (post-consolidation) |
| aerial drone photographer miami | Commercial | Medium | `/aerial-photography/` |
| dusk twilight real estate photography | Commercial | Low–Medium | `/dusk-twilight-photography/` |
| vacation rental photography miami | Commercial | Low | `/vacation-rental-photography/` (renamed from `/hdr-photography.html`) |
| how to prepare a home for real estate photos | Informational | Low | New guide |
| drone photography permits florida | Informational | Low | New guide |
| joe welch photography real estate | Branded | N/A | Homepage, About — reinforce with schema to win this query outright |

**Content Gap Analysis:**
- No case studies or before/after project pages despite 1,000+ completed shoots — the single biggest missed content asset on the site
- No visible FAA Part 107 / licensing disclosure for the aerial/drone service line
- No informational/how-to content capturing earlier-funnel, non-branded searches

**Recommended Content Pieces:**
1. "How to Prepare Your Listing for a Real Estate Photo Shoot" — targets informational intent, links to `/residential-real-estate-photography/`
2. "Do You Need a Permit to Fly a Drone Over Florida Real Estate?" — targets informational/trust intent, doubles as an E-E-A-T page for `/aerial-photography/`
3. "Recent Work: [Property Name] Twilight Shoot" — a repeatable case-study format tied to `/dusk-twilight-photography/`

---

## 11. Next Steps & Action Plan

1. **Week 1 — Richard/Parts of Practice:** Run `seo-tool/run-audit-joewelchphoto.bat` locally to complete the live technical crawl and confirm this report's Technical SEO section with real data
2. **Week 1 — Site owner:** 301-redirect the legacy `/real-estate-photography.html`, rename/redirect `/hdr-photography.html`, and add the disambiguating schema (Appendix)
3. **Week 2 — Site owner:** Standardize title tags site-wide using the formula above
4. **Weeks 3–4 — Parts of Practice:** Reconcile NAP/service-area info across GBP, Yelp, LinkedIn, ZoomInfo; begin outreach to 3–5 "best of Miami photographer" roundups
5. **Ongoing — Site owner:** Publish one case study or guide per month, starting with the three ideas above

---

## Appendix

### Suggested Title Tags
| Page | Recommended Title Tag |
|---|---|
| `/residential-real-estate-photography/` | `Real Estate Photographer in Miami \| Joe Welch Photography` |
| `/commercial-photography/` | `Commercial Photographer in Miami \| Joe Welch Photography` |
| `/construction-photography/` | `Construction Photographer in Miami \| Joe Welch Photography` |
| `/resort-photography/` | `Hotel & Resort Photographer in Miami \| Joe Welch Photography` |
| `/aerial-photography/` | `Aerial & Drone Photographer in Miami \| Joe Welch Photography` |
| `/dusk-twilight-photography/` | `Dusk & Twilight Real Estate Photographer \| Joe Welch Photography` |
| `/vacation-rental-photography/` (renamed) | `Vacation Rental Photographer in Miami \| Joe Welch Photography` |
| `/pricing/` | `Photo Packages & Pricing \| Joe Welch Photography` |

### Suggested Meta Descriptions
| Page | Recommended Meta Description |
|---|---|
| `/residential-real-estate-photography/` | `Professional real estate photography for Miami-Dade, Broward & Palm Beach listings. 15+ years, 1,000+ shoots, 100% satisfaction guarantee. Book Joe Welch Photography.` |
| `/aerial-photography/` | `FAA-compliant aerial & drone photography for South Florida real estate, resorts, and construction projects. Serving Miami-Dade, Broward, Palm Beach & the Caribbean.` |

### Schema Code
```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": "https://joewelchphoto.com/#/schema/LocalBusiness",
  "name": "Joe Welch Photography",
  "description": "Real estate, aerial/drone, commercial, construction, and hotel & resort photography serving Miami-Dade, Broward, and Palm Beach Counties, FL, and the Caribbean.",
  "url": "https://joewelchphoto.com/",
  "areaServed": [
    "Miami-Dade County, FL", "Broward County, FL", "Palm Beach County, FL", "Caribbean"
  ],
  "sameAs": [
    "https://www.instagram.com/joewelchphotography/",
    "https://www.linkedin.com/in/joewelchphoto"
  ]
}
```
*Note: Confirm the exact legal business name, address, and phone number directly with the client before publishing — none of these were independently verified in this pass — and replace `sameAs` with the client's actual, confirmed profile URLs.*

### Additional Notes
- **Data sources for this pass:** indexed search results, cached page titles/URLs, and public third-party listings (Yelp, LinkedIn, Instagram, competitor roundup articles). Direct crawling of joewelchphoto.com (page speed, meta descriptions, H1s, image alt text, schema, robots.txt/sitemap.xml) was blocked by network egress in the environment this audit was first drafted in.
- **To complete the technical layer:** run `seo-tool/run-audit-joewelchphoto.bat` from a machine with normal internet access — it crawls the live site with `audit.js` and regenerates `Joe-Welch-Photography-SEO-Audit-[date].html` with real, page-by-page data (title/meta/H1/schema/alt-text checks) in place of the "pending" markers used in this draft.
- **Recommended tools for ongoing monitoring:** Google Search Console (verify site), PageSpeed Insights / Lighthouse, Google's Mobile-Friendly Test, and the Rich Results structured-data test.
- **Standing flag:** the joewelchphotography.com brand collision should be re-checked each audit cycle until schema and citation work has had time to disambiguate the two entities in search results.
