# SEO AUDIT REPORT

**Client:** Ari + Ava
**Website:** https://www.shopariandava.com
**Audit Date:** 2026-08-17
**Auditor:** SEO AI Assistant
**Platform:** Shopify

---

## 1. Overall SEO Health Score: 53 / 100

**Summary:** The on-page foundation is far stronger than the raw Google Search Console numbers suggest — titles, H1s, collection copy and a site-wide NAP footer are all genuinely well-executed — but the site is held back by missing trust content (no founder/brand story, no contact details, no product reviews) and a crawl budget consumed by ~4,300 junk URLs, leaving 1,130 real pages crawled but unindexed.

> **Revision note (2026-08-17, after browser inspection).** This report was revised following client challenge. Three corrections were made: (a) the store **address and opening hours appear in the site-wide footer on every page**, not just the homepage and store page — a stronger local signal than originally credited; (b) the claim "no About page anywhere on the site" was **over-stated and has been withdrawn** — `/pages/visit-our-store` serves the About role and is linked from both the nav and the footer; the accurate finding is that it carries no founder or brand-story content; (c) `/pages/about-us` and `/pages/about` returning 404 was reported as a defect, but those were **URLs assumed during the audit, not links the site publishes** — that finding is downgraded to a minor note. (d) The **Google Business Profile is claimed** — confirmed by the client; the earlier "unverified" status has been replaced with a scoped optimisation review. A reported Facebook/Instagram handle mismatch was also withdrawn: the client confirms all social handles are correct and fixed. Score revised 50 → 53.

> **Note on the two scores in this folder:** the **Index Health Score of 42/100** in `AA-INDEX-COVERAGE-ANALYSIS-2026-08-17.md` measures indexation and crawl health only, from GSC data. This **50/100** is the full-site score across all six audit areas. They are measuring different things and are not in conflict.

**Score breakdown:**

| Area | Score | Max |
|---|---:|---:|
| Technical foundation | 12 | 20 |
| On-page optimization | 14 | 20 |
| Content depth & quality | 9 | 20 |
| E-E-A-T signals | 8 | 20 |
| Local SEO | 6 | 10 |
| Schema / structured data | 4 | 10 |
| **Total** | **53** | **100** |

**Third-party apps identified (browser inspection):** Yotpo (loyalty/rewards), Klaviyo (email capture), a Shopify store-locator modal (`sbStoreLocatore`), PhotoSwipe (gallery), and a custom size-guide drawer. The app emitting the `pr_*` recommendation parameters remains unidentified and must be confirmed from the client's installed app list.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Build a real About / founder page for Callie and add phone + email sitewide — the site currently has zero contactable identity | High | Low |
| 2 | Launch product reviews with `AggregateRating` schema — 0 reviews across the entire catalogue | High | Medium |
| 3 | Stop the recommendation app emitting `pr_*` parameters; reclaim crawl budget from ~4,300 junk URLs | High | Low |
| 4 | 301-redirect the ~34 dead product 404s and fix `/collections/callie` | Medium | Low |
| 5 | Launch a blog targeting plus-size fit, Judy Blue sizing, and Nashville shopping intent | High | High |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] **Add a phone number and email address to the site footer alongside the existing address and hours.** The footer already carries a complete, site-wide NAP block minus the phone — adding it completes the local signal at essentially zero effort. Verified absent from every page tested.
- [ ] **Confirm whether the Yotpo rewards bar overlaps the navigation dropdown.** Browser inspection confirms a **50px full-width geometric overlap band (y=314–364)** where the rewards bar and the top of every nav dropdown occupy the same space. Which element wins visually could not be confirmed headlessly — see §9 for the 20-second manual test.
- [ ] **Review the Klaviyo email popup.** It renders as a **fixed, full-viewport overlay (1280×720, z-index 90000)** on page load, intercepting all clicks until dismissed. Worth checking against Google's intrusive interstitial guidance and measuring its effect on interaction metrics.
- [ ] **Add founder/brand-story content to `/pages/visit-our-store`, or split it into a dedicated About page.** The page exists and is properly linked from both nav and footer — the gap is content, not architecture. It currently carries ~60 words of unique text (address, parking, hours) and no story, no founder, no photography.
- [ ] **Fix `/collections/callie` (404).** The homepage promotes "Callie's Newest Favs" and product pages carry "Callie's Comments", but the collection URL is dead. Either restore the collection or redirect it.
- [ ] **301-redirect the ~34 dead product URLs** identified in the GSC 404 export to their nearest live equivalent or parent collection. Bulk-upload via Shopify Admin → Content → URL Redirects.
- [ ] **Turn off click-tracking parameters in the product-recommendation app.** ~3,600 URLs across two GSC buckets carry `?pr_prod_strat=…&pr_rec_id=…&pr_rec_pid=…&pr_ref_pid=…&pr_seq=`. Fix at source — **do not** block these in robots.txt (see §6).
- [ ] **Add `Disallow: /services/` and `Disallow: /*.atom` to `robots.txt.liquid`.** Removes ~700 Shopify login endpoints and ~20 RSS feed URLs from the crawl queue.
- [ ] **Unblock `/policies/contact-information` or replace it with a proper `/pages/contact` page.** Shopify's default robots.txt disallows `/policies/` — meaning the site's only contact route is invisible to Google.
- [ ] **Trace the truncated-URL source.** `/products/flora-tiered-`, `/products/kathy-`, `/products/sarah-`, `/products/talia-sleeveless-` are all 404ing, all over `http://` not `https://`. Something (an email template, social bio tool, or feed) is emitting truncated handles.
- [ ] **Remove or repair the broken video-app embeds** producing `/resource/video_files/….mp4?t={seek_to_start_number}` — note the literal unresolved template variable.

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] **Build a proper About page** at `/pages/about-us`, 600–900 words: how Ari + Ava started, why plus-size specifically, Callie's buying philosophy, a photo of Callie and the East Nashville store, the size-inclusivity commitment, and the "Callie's Comments" fit-notes explanation. This is the single biggest E-E-A-T gap on the site.
- [ ] **Install a review app** (Judge.me, Loox, or Okendo) and run a back-in-time review request campaign against the existing customer list. Target 3+ reviews on the top 100 products within 8 weeks. This solves three problems at once: E-E-A-T, unique on-page content for the crawled-not-indexed pages, and star rich results in the SERP.
- [ ] **Expand product descriptions to 150–250 words** on the top 100 revenue products. Current copy runs 50–115 words. Critically, these are wholesale garments (vendor field shows *Heimish* and similar) sold by hundreds of competing boutiques — **generic vendor copy is a duplicate-content liability**. Add: fit notes by body type, fabric hand and stretch, care instructions, styling pairings, and Callie's personal take.
- [ ] **Add fabric composition and care instructions to every product.** Present on some (e.g. "90% Polyester | 10% Spandex" on the Rae cardigan), missing on most.
- [ ] **Add `LocalBusiness` / `ClothingStore` schema** to the store page with NAP, opening hours, and geo coordinates (see Appendix).
- [ ] **Embed a Google Map** on `/pages/visit-our-store` and add store interior/exterior photography.
- [ ] **Audit and complete image alt text** across product and collection imagery.
- [ ] **Replace the recommendation-app internal links** with clean, crawlable related-product links so internal link equity flows to products via indexable URLs.
- [ ] **Implement a retired-product policy.** Stop deleting sold-out products — either keep them live with a "notify me" and related-product links, or redirect on removal. This prevents the 404 backlog rebuilding.

---

## 5. Long-Term / Strategic Recommendations

- [ ] **Own the plus-size Judy Blue category nationally.** The catalogue is dense with Judy Blue denim and the collection page already carries 350–400 words of solid copy. Build a content hub around it: wash comparison, fit-by-body-type guides, sizing vs. straight-size denim, tummy-control explainer. This is the highest-ceiling opportunity on the site.
- [ ] **Launch the blog.** `/blogs/news` exists as an empty template. Plus-size fashion carries enormous informational search demand that this store is capturing none of — and the blog sitemap is already being generated and submitted.
- [ ] **Build the Nashville local authority layer.** GBP optimisation, local citations, an East Nashville / Five Points shopping guide, partnerships with Nashville lifestyle publishers. "Plus size boutique Nashville" is a winnable, high-intent local term with a physical store to back it.
- [ ] **Resolve the near-duplicate colourway problem.** Where one garment exists as three colours on three URLs with near-identical copy, decide deliberately: consolidate to variants on a single URL, or differentiate the copy meaningfully. The current middle ground is producing pages Google declines to index.
- [ ] **Video and UGC content strategy.** Instagram, Facebook and TikTok are all linked and presumably active — that content is not being repurposed onto the site where it could add unique page content and dwell time.
- [ ] **Monthly index monitoring** so a 1,130-page unindexed backlog never accumulates again.

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | **Optimized** | Genuinely good. "Nashville Plus Size Women's Boutique Clothing – Ari + Ava"; "Plus-Size Maxi Dresses – Flowy & Chic \| Ari + Ava Boutique". Keyword-targeted, not default Shopify output. |
| Meta Descriptions | **Not confirmed** | Not exposed in fetched output on any page tested — likely absent or auto-generated. Needs direct source verification. |
| H1 Tags | **Present** | One per page, descriptive. Homepage: "Ari + Ava – Plus-Size Women's Boutique in Nashville and Online". |
| Heading Hierarchy (H2–H4) | **Clean** | Homepage H2s map logically to collections. |
| Schema / Structured Data | **Partial** | Shopify default `Product` schema presumed present. **No `AggregateRating`** (no reviews exist). **No `LocalBusiness`** despite a physical store. No `BreadcrumbList` confirmed. |
| Canonical Tags | **Present** | Working correctly — 2,995 parameter URLs are being consolidated properly, which is why they appear as "Alternate page with proper canonical tag" rather than duplicates. |
| XML Sitemap | **Present** | `/sitemap.xml` with 5 children: agentic discovery, products, pages, collections, blogs. Declared in robots.txt. |
| Robots.txt | **Present** | Shopify default plus affiliate-parameter blocks and crawl-delays (AhrefsBot 10s, MJ12bot 10s, Pinterest 1s, Nutch disallowed). **Disallows `/policies/`** — which blocks the only contact page. |
| Page Speed | **Not measured** | Requires PageSpeed Insights / CrUX. Flag as an open item. |
| Mobile Friendliness | **Good** (presumed) | Modern responsive Shopify theme. Not device-tested. |
| HTTPS / SSL | **Present** | Site serves over HTTPS on `www.`. Note: several 404s were recorded on `http://` URLs — verify the http→https→www redirect chain is a single hop. |
| Broken Links | **Yes** | 129 URLs in GSC "Not found (404)", including ~34 real product URLs and `/collections/callie`. *(Note: `/pages/about-us` and `/pages/about` also 404, but these are URLs assumed during the audit, not links the site publishes — no action required.)* |
| Third-party overlays | **Issues** | Klaviyo popup renders as a fixed full-viewport overlay (1280×720, z-index 90000) on load. Yotpo rewards bar (z-index 10000) shares a 50px full-width band with the top of the nav dropdown (z-index 99999). |
| Social profiles | **Present** | Instagram, Facebook and TikTok linked site-wide in the footer. Handles verified correct with the client — no action. |
| Image Alt Text | **Unknown** | Not verifiable via fetch — requires a crawl. |

### Key Technical Issues

1. **Crawl budget exhaustion.** ~3,600 recommendation-app parameter URLs + ~700 `/services/login_with_shop/authorize` endpoints + ~20 `.atom` feeds are consuming the crawl allowance while 1,130 real pages sit unindexed. **Proof point:** `/collections/valentine-s-day` has 41 products and unique intro copy, yet is crawled-not-indexed — that is a crawl budget symptom, not a content quality one.
2. **The contact page is robots-blocked.** `/policies/contact-information` is the site's only contact route and sits under the disallowed `/policies/` path. Build `/pages/contact` instead.
3. **129 unhandled 404s** with no redirects — direct link-equity loss.
4. ⚠️ **Do not fix the `pr_*` parameters with robots.txt.** They currently resolve cleanly *because* Google can crawl them and read the canonical tag. Blocking them would convert ~3,000 correctly-handled URLs into "Blocked by robots.txt" limbo. Fix at source in the app settings.
5. **Unresolved template variable in production** — `{seek_to_start_number}` appearing literally in crawled video URLs indicates a misconfigured media app.
6. **1 page indexed despite being robots-blocked** — a contradictory directive. Either unblock it or serve a proper `noindex`.

---

## 7. On-Page SEO & Content Analysis

**Homepage (`/`)**
- Title: `Nashville Plus Size Women's Boutique Clothing – Ari + Ava`
- Meta Description: Not confirmed
- H1: `Ari + Ava – Plus-Size Women's Boutique in Nashville and Online`
- Notes: Strong. Combines the category, the modifier ("plus size"), and the city. H2s map to collections. Includes four customer testimonials (Lenora, Julie, Karen, Karin), a "Visit Us" block with the Nashville address, and a rewards programme. Good commercial structure.

**Judy Blues collection (`/collections/judy-blues`)**
- Title: `Judy Blue Plus-Size Jeans – Ari & Ava Boutique – Ari + Ava`
- H1: `Judy Blues`
- Notes: ~350–400 words of genuinely useful unique copy. Extensive faceted filtering (availability, price, 12 colours, 14 sizes, 5 fabric types, 5 fit styles). 24 products, paginated. **This is the site's best page.** Minor fix: brand duplication in the title tag ("Ari & Ava Boutique – Ari + Ava") — trim it. H1 "Judy Blues" is weaker than the title; make it "Judy Blue Plus-Size Jeans".

**Maxi Dresses collection (`/collections/maxi-dresses`)**
- Title: `Plus-Size Maxi Dresses – Flowy & Chic | Ari + Ava Boutique`
- H1: `Maxi Dresses`
- Notes: ~85 words of unique intro copy, 18 products. Well-targeted title. **Currently crawled-but-not-indexed** despite being a legitimate, well-optimised commercial page — strong evidence for the crawl budget diagnosis.

**Visit Our Store (`/pages/visit-our-store`)**
- Title: `Visit Our Store – Ari + Ava`
- H1: `Visit Our Store`
- Notes: ~850 words but the vast majority is navigation and footer boilerplate. The actual unique content is roughly 60 words: address, parking note, and opening hours. **No phone, no email, no map embed, no store photography, no founder story.** This page is the site's About destination — correctly linked from both nav and footer — but it is a store-hours page being asked to do a brand-story job. The fix is content, not architecture: it does not need replacing, it needs writing.

**Product pages (sampled 3)**

| Product | Description length | Reviews | Fabric/care |
|---|---:|---|---|
| Austin V-Neck Sweater Top | ~50 words | None | None |
| Tessa Metallic Maxi Skirt | ~115 words | None | None |
| Rae Animal Print Tie Front Long Cardigan | ~115 words | None | Composition only |

- Titles follow `[Product Name] – Ari + Ava` — functional but leave keyword opportunity unused.
- **Genuine positive:** every product carries a detailed size chart with model measurements and US/AU/UK/EU conversion, plus a "Callie's Comments" fit-notes section and a transparency statement about showing styles across size ranges. That is real, differentiated content most competitors lack — it should be expanded, not just retained.
- **Genuine negative:** zero reviews on 3 of 3 pages tested. Products are wholesale (vendor field: *Heimish*), meaning identical garments are sold by many competing boutiques.

**Content Quality Summary**
- Word count: Collections 85–400 words (good). Products 50–115 words (thin). Store page ~60 unique words (very thin).
- Keyword targeting: **Strong** at title/H1 level, **weak** in body copy.
- Internal linking: **Weak** — the primary internal link layer is a recommendation app emitting parameterised URLs rather than clean crawlable links.
- CTAs: **Strong** commercially (free shipping over $99, rewards, newsletter 10% off, Shop Pay). **Weak** for contact — no phone or email to act on.

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [x] Client testimonials (4 on homepage)
- [x] Physical business address and opening hours published **in the site-wide footer on every page** (203 N 11th St, Nashville, TN 37206) — verified on product pages as well as the homepage. A genuine and consistently-implemented local signal.
- [x] Model Information page (`/pages/model-information`) — supports the sizing transparency claim
- [x] Opening hours published
- [x] Social proof channels linked site-wide (Instagram, Facebook, TikTok) — handles confirmed correct by the client
- [x] Size chart with model measurements and transparency statement — **genuine expertise signal**
- [x] "Callie's Comments" personal fit notes on product pages — **genuine experience signal**
- [ ] Owner / founder bio
- [ ] About page
- [ ] Phone number
- [ ] Email address
- [ ] Product reviews
- [ ] Media mentions or press
- [ ] Awards or affiliations

**E-E-A-T Gaps:**

1. **The About page exists but carries no story.** `/pages/visit-our-store` is correctly linked from both the main navigation and the site-wide footer — the architecture is fine. The problem is content: roughly 60 words of unique text covering address, parking and hours, with no founder, no origin story, no buying philosophy and no photography. For a boutique competing on curation and personality, that is the most damaging content gap in the audit. *(An earlier draft claimed there was "no About page anywhere on the site" — over-stated, and withdrawn.)*
2. **Callie is the brand's public face but has no presence as a person.** "Callie's Newest Favs" on the homepage, "Callie's Comments" on product pages — and `/collections/callie` returns 404. There is no bio, no photo, no buying philosophy, no name attached to the business publicly. Naming and profiling her is a fast, high-impact win.
3. **No phone number or email address anywhere.** Not in the header, footer, store page, or homepage. The only contact route is `/policies/contact-information`, which robots.txt disallows.
4. **Zero product reviews across the catalogue.** No `AggregateRating`, no star rich results, no user-generated content. In apparel — where fit uncertainty is the primary purchase barrier and the primary *return* driver — this is both an SEO and a margin problem.
5. **No editorial or expertise content.** No blog, no fit guides, no styling content that would demonstrate topical authority in plus-size fashion.

**Local SEO:**
- **Google Business Profile: Claimed.** Confirmed — no claiming work required. What has **not** been audited is how well it is *optimised*: primary and secondary categories, product feed, photo volume and recency, Posts, Q&A seeding, review volume and response rate, attributes (accessibility, payment types), and whether the profile's hours and forthcoming phone number match the site exactly. A claimed but under-optimised profile is a common source of missed local pack visibility, and this should be the first thing reviewed once engaged.
- **NAP Consistency:** **Better than initially assessed.** Name, address and opening hours appear in the site-wide footer on **every page** — verified on product pages, not just the homepage and store page. This is a correctly-implemented local signal and a real strength. The only missing element is the **phone number**, which is absent site-wide; adding it to the existing footer block completes the NAP at near-zero effort and is the single cheapest local SEO win available.
- **Geographic targeting in content:** **Moderate.** "Nashville" appears in the homepage title and H1 — good. But there is no local content depth: no neighbourhood context, no map, no store photography, no local landing content.
- **Local citations / directories:** Not audited — requires a citation audit. Without a phone number, any existing citations are likely inconsistent.
- **Schema:** No `LocalBusiness` or `ClothingStore` markup detected. See Appendix.

---

## 9. Conversion & User Experience Issues

- **CTAs:** Strong on the commercial path — free shipping threshold, rewards programme, newsletter incentive, Shop Pay / Apple Pay / PayPal all present. The purchase journey is well built.
- **Contact friction:** **Severe.** No phone, no email, no contact form surfaced in navigation. A customer with a sizing question — the single most common pre-purchase question in plus-size apparel — has no obvious way to ask it.
- **Mobile UX:** Presumed good (modern responsive theme), not device-tested.
- **Page load perception:** Not measured. The recommendation app and video app both add third-party JavaScript weight; worth measuring.
- **Trust signals above the fold:** **Weak.** No reviews, no star ratings, no "as seen in", no founder presence. Testimonials sit far down the homepage.
- **Navigation clarity:** **Clear and well-structured.** Shop → Denim / Tops / Dresses / By Color / Bottoms / Outerwear / Accessories, with sensible sub-categories. "By Color" is a nice merchandising touch. One flaw: "About Us" is a category containing store/policy links rather than an actual About page.

### Third-Party Overlay Findings (browser inspection, 2026-08-17)

**1. Klaviyo email popup renders as a full-viewport overlay — CONFIRMED.**
On page load, a Klaviyo container (`kl-private-reset-css-*`) renders at `position: fixed`, `z-index: 90000`, occupying the **entire 1280×720 viewport**. Hit-testing confirmed it is the topmost interactive element at every sampled point on the screen until dismissed. Worth assessing against Google's intrusive interstitial guidance and measuring against interaction-to-next-paint.

**2. Yotpo rewards bar overlaps the navigation dropdown zone — GEOMETRY CONFIRMED, VISUAL BEHAVIOUR UNCONFIRMED.**

Measured at a 1280px viewport:

| Element | Position | Y range | Width | z-index |
|---|---|---|---|---|
| Nav items | static | 272–305 | — | auto |
| **Nav dropdown** | absolute | **306–774** | — | **99999** |
| **Yotpo rewards bar** | relative | **314–364** | 1280 (full) | **10000** |

There is a **confirmed 50px full-width overlap band at y=314–364** where the rewards bar and the top of every navigation dropdown occupy the same space. On z-index alone the dropdown (99999) should paint above the bar (10000) — but the Yotpo bar carries an expand/collapse control (`yotpo-banner-up-down-icon`), and its expanded panel would drop into exactly this region.

**This could not be confirmed headlessly.** Screenshots were unavailable in the inspection environment, and the theme reveals dropdowns via true CSS `:hover`, which cannot be synthesised in script. The overlap is real and measured; which element wins on screen is not established.

**20-second manual test:** open the site on desktop, hover "Denim" in the main nav, and look at the top ~50px of the dropdown where it crosses the rewards bar. If the rewards bar sits over the dropdown, or the top dropdown links are unclickable, it is confirmed. Repeat with the rewards bar expanded. If confirmed, the fix is trivial — raise the dropdown's stacking context above the Yotpo widget, or reposition the bar below the header.

**Key Fixes:**
1. Add a phone number to the existing site-wide footer NAP block, plus an email address.
2. Add star ratings to product cards on collection pages once reviews are live.
3. Surface a sizing-help CTA on product pages ("Not sure on fit? Ask Callie") — directly addresses the primary conversion barrier.
4. Move testimonials higher on the homepage and add faces or names with locations.
5. Repair the broken video embeds — these are likely failing silently on product pages.

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| plus size boutique nashville | Commercial / Local | Low | `/pages/visit-our-store` → new `/pages/about-us` |
| plus size clothing store nashville | Commercial / Local | Low | Homepage |
| judy blue plus size jeans | Commercial | Medium | `/collections/judy-blues` |
| judy blue jeans size chart | Informational | Low | New blog post |
| judy blue tummy control jeans | Commercial | Medium | `/collections/judy-blues` |
| plus size maxi dresses | Commercial | High | `/collections/maxi-dresses` |
| plus size wedding guest dresses | Commercial | Medium | New collection |
| plus size dresses for apple shape | Informational | Low | New blog post |
| what size judy blue should i order | Informational | Low | New blog post |
| plus size jeans that don't gap at the waist | Informational | Low | New blog post |
| extended plus size 4x 5x clothing | Commercial | Low | `/collections/extended-plus-up-to-5xl` |
| east nashville shopping | Informational / Local | Low | New local guide page |

**Content Gap Analysis:**
- **No blog whatsoever** — `/blogs/news` is an empty template while the blog sitemap is already generated and submitted. Zero capture of informational demand.
- **No fit or sizing guidance content** despite fit being the #1 barrier and #1 return driver in plus-size apparel — and despite the store already having the raw material in "Callie's Comments".
- **No founder or brand story content** — the biggest differentiator a boutique has versus a national retailer.
- **No local content** beyond an address and opening hours.
- **No occasion-based collections** (wedding guest, vacation, work) beyond "Best Dressed Guest" on the homepage.
- **No Judy Blue authority content** despite it being the store's strongest commercial category.

**Recommended Content Pieces:**
1. **"What Size Judy Blue Should I Order? A Plus-Size Fit Guide"** — targets *judy blue jeans size chart* / *what size judy blue should i order*; captures high-intent traffic at the exact moment of purchase hesitation.
2. **"Judy Blue Washes Explained: Light, Medium, Dark and Everything Between"** — targets long-tail Judy Blue queries; internally links to every denim product.
3. **"The Plus-Size Guide to Shopping in East Nashville"** — targets *east nashville shopping*; builds local relevance and is genuinely linkable by Nashville publishers.
4. **"Meet Callie: Why We Built a Plus-Size Boutique in Nashville"** — the founder story. Serves E-E-A-T, brand differentiation, and *plus size boutique nashville*.
5. **"Plus-Size Wedding Guest Dresses: What to Wear to Every Kind of Wedding"** — targets a high-value seasonal commercial term; pairs with a new collection.
6. **"How to Find Jeans That Don't Gap at the Waist"** — classic plus-size pain point, high informational volume, funnels directly to Judy Blue.

---

## 11. Next Steps & Action Plan

1. **Week 1 — Developer / Store owner:** Add phone and email to header and footer. Create `/pages/contact` (outside the robots-disallowed `/policies/` path).
2. **Week 1 — Store owner:** Draft the About page content and provide a photo of Callie and the store interior.
3. **Week 1 — SEO:** Bulk-upload 301 redirects for the ~34 dead product URLs plus `/collections/callie`. Add `Disallow: /services/` and `Disallow: /*.atom` to `robots.txt.liquid`.
4. **Week 1 — SEO:** Disable click-tracking parameters in the product-recommendation app. Verify `/collections/valentine-s-day` and `/collections/maxi-dresses` re-enter the index over the following 2–4 weeks as crawl budget frees up.
5. **Week 1 — SEO:** Audit the existing (already claimed) Google Business Profile for optimisation — categories, product feed, photos, Posts, Q&A, review response rate and attributes. Push the new phone number to the profile so it matches the site exactly.
6. **Week 2 — Developer:** Publish the About page with `LocalBusiness` schema, Google Map embed, and store photography. Repoint the "About Us" nav.
7. **Week 2 — SEO:** Trace and fix the truncated-URL source and the broken video-app embeds.
8. **Weeks 2–4 — Store owner / SEO:** Install a review app and run a retrospective review request campaign to the existing customer list.
9. **Weeks 3–8 — Content:** Expand the top 100 product descriptions to 150–250 words with original fit, fabric and styling detail. Add care instructions throughout.
10. **Weeks 4–8 — Content:** Publish content pieces 1, 4 and 6 from §10. Establish a fortnightly publishing cadence.
11. **Ongoing — SEO:** Monthly GSC coverage review tracking indexed count against a target, plus impressions year-over-year to separate seasonality from ranking movement.

---

## Appendix

### Suggested Title Tags

| Page | Recommended Title Tag |
|---|---|
| Homepage | `Plus Size Boutique Nashville \| Sizes 1XL–5XL – Ari + Ava` |
| Judy Blues | `Judy Blue Plus Size Jeans \| Sizes 1XL–3XL – Ari + Ava` |
| Maxi Dresses | `Plus Size Maxi Dresses \| Flowy & Chic – Ari + Ava` |
| Visit Our Store | `Visit Our Nashville Plus Size Boutique \| 203 N 11th St – Ari + Ava` |
| About Us (new) | `About Ari + Ava \| Nashville's Plus Size Boutique` |
| Contact (new) | `Contact Us \| Ari + Ava Plus Size Boutique Nashville` |
| Product template | `[Product Name] \| Plus Size [Category] – Ari + Ava` |

### Suggested Meta Descriptions

| Page | Recommended Meta Description |
|---|---|
| Homepage | `Shop plus size women's clothing at Ari + Ava — Nashville's size-inclusive boutique. Judy Blue denim, dresses and tops in 1XL–5XL. Visit us in East Nashville or shop online. Free shipping over $99.` |
| Judy Blues | `Shop Judy Blue plus size jeans at Ari + Ava. Tummy control, wide leg, skinny and bootcut in sizes 1XL–3XL. Ultra-soft stretch denim that holds its shape. Free shipping over $99.` |
| Maxi Dresses | `Plus size maxi dresses made to flatter every curve. Bold prints, tiered skirts and breezy solids for weddings, brunch and vacation. Sizes 1XL–3XL. Free shipping over $99.` |
| Visit Our Store | `Visit Ari + Ava, Nashville's plus size boutique at 203 N 11th St in East Nashville. Free parking in rear. Open Wed–Mon. Call or stop by to shop sizes 1XL–5XL in person.` |
| About Us (new) | `Meet Callie and the story behind Ari + Ava — a plus size boutique in East Nashville built on the belief that size-inclusive fashion should be genuinely fashionable.` |

### Schema Code

**LocalBusiness / ClothingStore — add to `/pages/visit-our-store`.** The NAP data is already published in the site-wide footer; this marks it up for Google. Replace the `telephone`, `email`, `image` and `geo` values with live details before deploying. The `sameAs` URLs below are the client's live profiles and are correct as written.

```json
{
  "@context": "https://schema.org",
  "@type": "ClothingStore",
  "@id": "https://www.shopariandava.com/#store",
  "name": "Ari + Ava",
  "description": "Plus-size women's clothing boutique in East Nashville offering size-inclusive fashion in sizes 1XL to 5XL, including Judy Blue denim, dresses, tops and accessories.",
  "url": "https://www.shopariandava.com",
  "logo": "https://www.shopariandava.com/[LOGO-PATH]",
  "image": "https://www.shopariandava.com/[STOREFRONT-PHOTO]",
  "telephone": "[ADD PHONE NUMBER]",
  "email": "[ADD EMAIL ADDRESS]",
  "priceRange": "$$",
  "currenciesAccepted": "USD",
  "paymentAccepted": "Cash, Credit Card, Apple Pay, Google Pay, PayPal, Shop Pay",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "203 N 11th St",
    "addressLocality": "Nashville",
    "addressRegion": "TN",
    "postalCode": "37206",
    "addressCountry": "US"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "[CONFIRM VIA GOOGLE MAPS]",
    "longitude": "[CONFIRM VIA GOOGLE MAPS]"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Sunday", "Monday", "Wednesday", "Thursday"],
      "opens": "12:00",
      "closes": "18:00"
    },
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Friday", "Saturday"],
      "opens": "10:00",
      "closes": "18:00"
    }
  ],
  "sameAs": [
    "https://www.instagram.com/shopariandava",
    "https://www.facebook.com/shopariava",
    "https://www.tiktok.com/@shopariandava"
  ],
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Plus Size Women's Clothing",
    "itemListElement": [
      { "@type": "OfferCatalog", "name": "Judy Blue Plus Size Jeans" },
      { "@type": "OfferCatalog", "name": "Plus Size Dresses" },
      { "@type": "OfferCatalog", "name": "Plus Size Tops" },
      { "@type": "OfferCatalog", "name": "Plus Size Bottoms" },
      { "@type": "OfferCatalog", "name": "Outerwear" },
      { "@type": "OfferCatalog", "name": "Accessories" }
    ]
  }
}
```

**Product schema with AggregateRating — extend the existing Shopify product schema once reviews are live.**

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Austin V-Neck Sweater Top",
  "image": "https://www.shopariandava.com/[PRODUCT-IMAGE]",
  "description": "[EXPANDED 150-250 WORD DESCRIPTION]",
  "sku": "[SHOPIFY VARIANT SKU]",
  "brand": { "@type": "Brand", "name": "Ari + Ava" },
  "material": "[ADD FABRIC COMPOSITION]",
  "size": ["1XL", "2XL", "3XL"],
  "color": ["Cream", "Red", "Taupe"],
  "offers": {
    "@type": "Offer",
    "url": "https://www.shopariandava.com/products/austin-v-neck-sweater-top",
    "priceCurrency": "USD",
    "price": "32.00",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition",
    "seller": { "@type": "Organization", "name": "Ari + Ava" }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "[FROM REVIEW APP]",
    "reviewCount": "[FROM REVIEW APP]"
  }
}
```

**BreadcrumbList — add to product pages.**

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.shopariandava.com" },
    { "@type": "ListItem", "position": 2, "name": "Denim", "item": "https://www.shopariandava.com/collections/judy-blues" },
    { "@type": "ListItem", "position": 3, "name": "Austin V-Neck Sweater Top" }
  ]
}
```

### Additional Notes

**Tools recommended:**
- Screaming Frog — full crawl to confirm alt text coverage, word counts at scale, and internal link depth
- PageSpeed Insights / CrUX — page speed is currently unmeasured
- Judge.me, Loox or Okendo — product reviews (the single highest-leverage fix)
- Shopify Admin → Content → URL Redirects — bulk 301 upload
- Google Business Profile — already claimed; audit for optimisation (categories, product feed, photos, Posts, Q&A, review responses)

**Platform-specific notes:**
- `robots.txt.liquid` is editable in Shopify — required for the `/services/` and `.atom` disallows.
- Shopify's default robots.txt disallows `/policies/`, which is why the contact page is invisible. Build `/pages/contact` instead of trying to unblock it.
- Shopify auto-generates `.atom` RSS feeds for every collection. Harmless but crawl-wasteful at this scale.
- The `pr_prod_strat` / `pr_rec_id` / `pr_rec_pid` / `pr_ref_pid` / `pr_seq` parameter set comes from a product-recommendation app. Identify it in the installed app list and disable click tracking there.

**Audit limitations — disclose these to the client:**
- Page speed, alt text coverage, and full internal link structure were not measured; these require a crawl and CrUX data.
- Meta descriptions were not confirmed on any page and need direct source verification.
- Product-level findings are based on a **3-page sample** of a ~700+ product catalogue.
- The Google Business Profile is **claimed** (confirmed), but its optimisation level — categories, product feed, photos, Posts, Q&A, review volume and response rate — has not been audited.
- Both large GSC drilldowns are **capped at 1,000 rows** by Google (actual totals 2,995 and 1,130), so those percentages are extrapolated.
- No keyword volume or difficulty data was pulled — the §10 difficulty ratings are directional estimates, not tool-sourced.
- **The Yotpo/dropdown overlay could not be visually confirmed** — screenshots were unavailable and the theme's CSS `:hover` dropdown cannot be triggered from script. The 50px overlap is measured and real; which element renders on top requires manual confirmation (§9).
- **Corrections applied 2026-08-17 after client challenge:** the site-wide footer NAP was initially under-credited; the "no About page" claim was over-stated and withdrawn; `/pages/about-us` and `/pages/about` 404s were assumed URLs, not published broken links, and have been downgraded to a note. Score revised 50 → 52. Verified against live product-page footers and browser DOM inspection.
