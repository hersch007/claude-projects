# Ari & Ava (shopariandava.com) — Google Index Coverage Analysis
**Date:** 2026-08-17
**Status:** PROSPECT — not yet a client
**Data source:** Google Search Console coverage export + 4 drilldown exports (property: shopariandava.com, sitemap scope: "All known pages", data through 2026-08-13)
**Platform:** Shopify
**Scope note:** This analysis is built entirely from the GSC exports supplied. The site has **not** been crawled yet — page-level content, speed, schema and on-page findings are not covered here and would require a full audit.

---

## 1. Index Health Score: **42 / 100**

**Justification:**
- Only **490 of 5,488 known URLs are indexed (8.9%)** — but ~75% of the non-indexed URLs are junk/system URLs that *should* be excluded, so the raw number overstates the problem.
- The genuine issue is **1,130 pages in "Crawled – currently not indexed"** — Google has fetched these and actively declined to index them. That is a *quality* signal, not a technical one, and it is the hardest bucket to fix.
- **129 unhandled 404s**, including ~34 real product URLs with no redirect — direct loss of link equity.
- **Impressions down ~49% since May** while indexed pages went *up* 19% — the site is losing visibility per page, not losing pages.
- Nothing is catastrophically broken: canonicals are working correctly, only 2 server errors, only 9 robots blocks. The foundation is sound; the execution is not.

Score is held down by the crawled-not-indexed volume and the traffic trend, not by technical breakage.

---

## 2. The Headline Numbers

| Metric | Value |
|---|---|
| Total known URLs | 5,488 |
| **Indexed** | **490 (8.9%)** |
| Not indexed | 4,998 (91.1%) |
| Indexed pages, 21 May → 13 Aug | 411 → 490 (**+19%**) |
| Impressions/day, May avg | 5,407 |
| Impressions/day, June avg | 3,192 |
| Impressions/day, July avg | 3,185 |
| Impressions/day, Aug 1–13 avg | **2,775** |
| **Impression change, May → Aug** | **−49%** |
| Lowest day in dataset | 2026-08-13 — 1,697 impressions |

**The single most important observation:** indexed pages went **up** 19% while impressions fell **49%**. More pages in the index producing half the visibility. That rules out a deindexing event and points at (a) seasonality — May/June is peak dress-and-shorts season for a women's boutique — and (b) genuine ranking decline on the pages that *are* indexed.

---

## 3. Non-Indexed Breakdown — What's Real vs. What's Noise

| Reason | Pages | % of non-indexed | Verdict |
|---|---:|---:|---|
| Alternate page with proper canonical tag | 2,995 | 59.9% | ⚪ **Working as intended** — but wasting crawl budget |
| Crawled – currently not indexed | 1,130 | 22.6% | 🔴 **The real problem** |
| Excluded by 'noindex' tag | 696 | 13.9% | ⚪ **Correct by design** (Shopify system URLs) |
| Not found (404) | 129 | 2.6% | 🟠 **Fixable, real equity loss** |
| Page with redirect | 36 | 0.7% | ⚪ Normal |
| Blocked by robots.txt | 9 | 0.2% | 🟡 Spot-check |
| Server error (5xx) | 2 | <0.1% | 🟡 Spot-check |
| Blocked due to access forbidden (403) | 1 | <0.1% | 🟡 Spot-check |
| *Indexed, though blocked by robots.txt* | *1* | *(non-critical)* | 🟡 Contradictory directive |

**So: roughly 3,700 of the 4,998 "problems" are not problems at all.** Anyone pitching this client on "you have 5,000 pages missing from Google" is either misreading the report or being dishonest. The honest number to pitch is the ~1,130 crawled-not-indexed plus 129 404s.

---

## 4. Issue-by-Issue: Is It Fixable?

### 4.1 Alternate page with proper canonical tag — 2,995 pages
**Fixable: Yes, but it isn't broken. Fix it for crawl budget, not for indexation.**

Pattern analysis of the 1,000-row sample:
- **907 URLs** carry `?pr_prod_strat=…&pr_rec_id=…&pr_rec_pid=…&pr_ref_pid=…&pr_seq=uniform`
- **81 URLs** are `/collections/{collection}/products/{product}` duplicate paths
- 12 others (pagination `?page=`, `?phcursor=`, `?variant=`, `/?from=AppAgg.com`)

The `pr_*` parameters come from a **Shopify product-recommendation app** appending click-tracking to every internal "you may also like" link. Every recommendation widget impression mints a brand-new URL. Example:

```
/products/plus-cotton-oversized-raw-edge-boyfriend-tee-in-ivory
  ?pr_prod_strat=pinned&pr_rec_id=1fd75a8f4&pr_rec_pid=7877502533720
  &pr_ref_pid=7877508005976&pr_seq=uniform
```

Google is correctly consolidating these to the clean URL. Nothing is being lost. **But** Googlebot is burning the majority of its crawl allowance on ~3,000 duplicate URLs, which is a plausible contributor to why 1,130 real pages sit un-indexed.

⚠️ **Do NOT fix this by blocking the parameters in robots.txt.** These URLs are currently resolving cleanly *because* Google can crawl them and read the canonical tag. Block them and 3,000 correctly-handled URLs become "Blocked by robots.txt" limbo. Fix it at source: turn off click-tracking parameters in the recommendation app's settings, or have the theme strip them.

### 4.2 Crawled – currently not indexed — 1,130 pages 🔴
**Fixable: Yes — and this is where the money is. Hardest and highest-value bucket.**

Pattern analysis of the 1,000-row sample:
- 647 — `pr_*` parameter URLs (same app noise as above)
- 250 — `/collections/{x}/products/{y}` duplicate paths
- **21 — clean `/products/{handle}` URLs** ← real product pages Google refused to index
- **20 — clean `/collections/{handle}` URLs** ← real category pages Google refused to index
- ~20 — `.atom` RSS feed URLs (`/collections/dresses/maxi-dress.atom`, `/collections/judy-blues.atom`, etc.)
- Remainder — pagination, CDN files, misc

Real pages Google looked at and rejected include:
```
/products/rae-animal-print-tie-front-long-cardigan
/products/beatrix-floral-sleeveless-dress-with-keyhole-back
/products/lana-long-sleeve-turtle-neck-top-with-bow-sleeve-detail
/products/trina-scuba-half-zip-vest
/products/austin-v-neck-sweater-top
/products/tessa-metallic-maxi-skirt
/products/talia-hi-rise-29-5judy-blue-skinny-jeans-in-dark-wash
/collections/maxi-dresses
/collections/valentine-s-day
/collections/end-of-summer-blowout-20-off-regular-priced-items
```

**Why Google is rejecting them.** "Crawled – currently not indexed" is Google saying *"I read this page and it wasn't worth a slot."* For a Shopify boutique this almost always means:
1. **Thin / vendor-supplied product copy** — 40–80 words of generic description, often identical to what 200 other boutiques selling the same Judy Blue jeans are publishing.
2. **Near-duplicate products** — dozens of near-identical tops differentiated only by colour, each on its own URL with near-identical copy.
3. **No unique signals** — no reviews, no sizing/fit notes, no styling content, no original photography descriptions.
4. **Weak internal linking** — orphaned or deep products that receive almost no internal link equity.
5. **Crawl budget dilution** — see 4.1.

This is fixable, but it is a **content and information-architecture project, not a checkbox**. Realistic timeline: 8–16 weeks to move a meaningful share of these into the index.

### 4.3 Excluded by 'noindex' tag — 696 pages
**Fixable: No action needed. Correctly handled.**

Effectively 100% of these are Shopify's Login-with-Shop system endpoint:
```
/services/login_with_shop/authorize?analytics_context=loginWithShopPrequal
  &analytics_trace_id={uuid}&flow=prequal&…
```
Shopify noindexes these correctly. Every page view of the prequal widget generates a fresh `analytics_trace_id`, which is why there are ~700 of them.

**Only cleanup worth doing:** `Disallow: /services/` in `robots.txt.liquid` to stop Googlebot crawling ~700 dead-end URLs. Low risk — these are JS-generated system endpoints with no ranking value.

### 4.4 Not found (404) — 129 pages 🟠
**Fixable: Yes. Fastest win in the whole dataset.**

Three distinct groups:

**(a) ~34 deleted products with no redirect** — sold-out / discontinued items that were removed rather than redirected. Each may hold backlinks, email-campaign links, Pinterest and social traffic that is now dead-ending:
```
/products/rose-embellished-lace-hem-dress
/products/kylie-crushed-velvet-dress-in-dark-brown
/products/maggie-sleeveless-poplin-maxi-dress-in-red
/products/danielle-high-rise-elastic-waist-judy-blue-shorts-in-dark-wash
/products/2-pairs-of-judy-blue-shorts-mystery-bag-final-sale
/products/3-item-mystery-bag-final-sale
/collections/callie
… (28 more)
```

**(b) Truncated URLs — evidence of a broken link somewhere:**
```
http://www.shopariandava.com/products/flora-tiered-
http://www.shopariandava.com/products/kathy-
http://www.shopariandava.com/products/sarah-
https://www.shopariandava.com/products/talia-sleeveless-
```
Product handles cut off mid-word, and note several are `http://` not `https://`. This points at a link source that is truncating URLs — an email template, a social bio tool, a third-party feed, or a theme bug. Worth tracing; it's a small, cheap fix that signals competence.

**(c) Broken third-party media paths:**
```
/resource/video_files/8e8afdce334dcbf8.mp4?t={seek_to_start_number}
/content/…/ed21f523c3c3b7aa.mp4?auto=…&mode=…
/player?id=63531984&stream=hd
/static/…/v/…?api=…&player_id=…
/upload/…/video/…?t=…
```
Note the literal unresolved template variable `{seek_to_start_number}` — a video app (likely a shoppable-video or reels app) is emitting relative paths against the store domain instead of its own CDN. Broken video embeds are both an SEO and a **conversion** problem.

### 4.5 Page with redirect — 36 pages
**No action.** Normal for a store that renames handles and retires collections.

### 4.6 Blocked by robots.txt (9) / 5xx (2) / 403 (1) / Indexed-though-blocked (1)
**Fixable: Yes, trivially — but needs the actual URLs.** These four buckets weren't included in the exports supplied. Pull those drilldowns from GSC. The "Indexed, though blocked by robots.txt" page is the notable one: it's a contradictory directive (Google indexed it without being able to read it), and it should either be unblocked or given a proper `noindex`.

---

## 5. Top 5 Priorities

1. **Kill the `pr_*` recommendation-app parameters at source.** ~3,600 duplicate URLs across two buckets. Frees crawl budget for the 1,130 pages Google won't index. Fix in the app settings — *not* robots.txt.
2. **Redirect the ~34 dead product 404s** to the nearest live product or parent collection. Recovers link equity and stops dead-ending real referral traffic.
3. **Fix thin product content on the highest-value non-indexed products** — starting with the Judy Blue denim range, which is the store's most commercially valuable search territory.
4. **Diagnose the 49% impression decline.** Isolate how much is seasonal (spring/summer boutique peak) vs. genuine ranking loss, using GSC query and page-level comparison year-over-year.
5. **Trace the truncated-URL source** and the broken video-app paths.

---

## 6. Quick Wins (< 1 week)

| # | Action | Effort | Impact |
|---|---|---|---|
| 1 | 301-redirect the ~34 dead product URLs to nearest live equivalent (Shopify Admin → Content → URL Redirects, or bulk CSV) | 2–3 hrs | High |
| 2 | Add `Disallow: /services/` and `Disallow: /*.atom` to `robots.txt.liquid` | 30 min | Medium |
| 3 | Turn off click-tracking parameters in the product-recommendation app | 30 min | High |
| 4 | Pull the 4 missing drilldowns (robots-blocked, 5xx, 403, indexed-though-blocked) and clear all 13 URLs | 1 hr | Medium |
| 5 | Resolve or remove the broken video-app embeds (`{seek_to_start_number}` etc.) | 1–2 hrs | Medium (conversion + SEO) |
| 6 | Trace and fix the truncated `/products/flora-tiered-` style links | 1 hr | Low-Medium |
| 7 | Verify the XML sitemap contains only clean canonical URLs, resubmit | 30 min | Medium |

**All seven are achievable in one working day.** That matters for the pitch — see §9.

---

## 7. Medium-Term (2–8 weeks)

- **Product description rewrite programme.** Prioritise by revenue and by search demand, not alphabetically. Target 150–250 words of genuinely original copy per page: fit and sizing detail, fabric and care, styling suggestions, who it suits. Batch ~40–60 products/week.
- **Collection page content.** `/collections/maxi-dresses`, `/collections/judy-blues` etc. need 200–400 words of unique intro copy above or below the grid, plus proper `<h1>`s and internal links. Collection pages are the strongest ranking assets a boutique has and several are currently *not indexed at all*.
- **Internal linking overhaul.** The recommendation app is currently the primary internal link layer and it emits parameterised URLs. Replace with clean, crawlable, editorially-controlled links: related products, "complete the look", collection cross-links.
- **Product schema audit.** Verify `Product`, `Offer`, `AggregateRating` and `availability` markup is complete and valid on every product — critical for rich results in a category where competitors all have them.
- **Review collection.** Reviews are the single most reliable way to add unique content at scale to product pages and are a direct fix for the crawled-not-indexed problem.
- **Retired-product policy.** Stop deleting sold-out products. Either keep them live with "notify me" plus related-product links, or redirect on removal. This prevents the 404 problem recurring.

---

## 8. Long-Term / Strategic

- **Own the Judy Blue category.** The catalogue is dense with Judy Blue denim, a brand with real, sustained search demand. A dedicated content hub — fit guides, size comparison, wash comparison, style-by-body-type — is the highest-ceiling opportunity on this site.
- **Content marketing for non-transactional demand.** Styling guides, seasonal lookbooks, occasion-based edits. These earn links and internal-link equity that flows into product pages, which is exactly what the crawled-not-indexed pages are starving for.
- **Catalogue consolidation strategy.** Where a garment exists in six colours as six URLs with near-identical copy, decide deliberately: variants on one URL, or genuinely differentiated pages. The current middle ground is producing near-duplicates Google won't index.
- **Local SEO** (if there is a physical storefront — the Tennessee-themed products suggest one). GBP optimisation, local landing page, local citations.
- **Ongoing index monitoring.** Monthly coverage review so a 1,130-page backlog never accumulates again.

---

## 9. Prospect Strategy — How to Win This Client

### The hook
Lead with the two numbers that are impossible to ignore, and be honest about both:

> "Google has crawled 1,130 of your pages and chosen not to index a single one of them. And your search impressions are down 49% since May — while your indexed page count went *up*. You're publishing more and being seen less."

### Why this data is a strong pitching position
1. **It's their own Google data, not a third-party tool estimate.** Nothing to argue with.
2. **There's a same-week win available.** §6 is a full day's work with visible results — redirects live, crawl waste gone, robots cleaned. That is a very low-friction "let me prove it" offer.
3. **The real problem is genuinely hard.** Anyone can run Screaming Frog and hand over a 404 list. Diagnosing thin-content-driven index rejection and fixing it is a retainer-shaped problem, not a one-off.
4. **The decline is recent and ongoing.** 13 Aug was the worst day in the dataset. Urgency is real, not manufactured.

### The credibility differentiator
Most agencies pitching this store will open with *"you have 4,998 pages missing from Google!"* — which is wrong, and a switched-on owner will eventually work that out. **Open by dismantling that claim yourself:**

> "You'll get pitches telling you 5,000 pages are missing from Google. About 3,700 of those are Shopify system URLs and app tracking parameters that are *supposed* to be excluded — Google is handling them correctly. Your actual problem is 1,130 pages, and it's a content problem, not a technical one. Here's the difference and why it matters."

That single paragraph does more to win trust than a 40-page audit PDF. It demonstrates you read the data rather than exported it.

### Recommended offer structure
- **Step 1 — Free:** A 2-page teardown (not a 40-page PDF). The two headline numbers, the honest 3,700-vs-1,130 distinction, the 7 quick wins with time estimates, and 5 named product pages Google is refusing to index. Send it unsolicited.
- **Step 2 — Paid pilot:** Fixed-fee technical cleanup sprint — everything in §6 plus the 4 missing drilldowns, delivered in one week with before/after GSC screenshots.
- **Step 3 — Retainer:** Monthly content + index recovery. Product and collection copy at a defined weekly volume, internal linking, schema, review programme, monthly coverage reporting against a target index count.

### Objection to prepare for
*"Traffic always drops in summer for us."* — Partly true, and say so first. Then separate the two effects: pull year-over-year GSC data before the pitch call. If Aug 2026 is materially below Aug 2025, seasonality doesn't explain it, and you'll have the evidence in hand. If it *is* seasonal, pivot to the index gap — 1,130 rejected pages is a problem in any season, and Q4 is where a boutique makes its year.

### What to do before making contact
1. Crawl the site properly (Screaming Frog) — validate the thin-content hypothesis with actual word counts on the non-indexed products.
2. Pull GSC year-over-year to settle the seasonality question.
3. Check 5–10 of the non-indexed product URLs manually to confirm they're live, indexable and thin.
4. Check whether a physical store exists, for the local angle.
5. Identify which recommendation app is emitting `pr_*` — naming it specifically in the teardown is a strong credibility signal.

---

## 10. Overall Verdict

**Yes, all of it is fixable — but the buckets are not equally worth fixing, and they don't fix the same way.**

| Bucket | Pages | Fixable? | Effort | Worth doing? |
|---|---:|---|---|---|
| Alternate page w/ canonical | 2,995 | Yes (at source) | Low | Yes — crawl budget |
| Crawled – not indexed | 1,130 | Yes | **High** | **Yes — this is the value** |
| Excluded by noindex | 696 | N/A — correct | — | No, cosmetic only |
| 404s | 129 | Yes | Low | Yes — fast win |
| Redirects | 36 | N/A — normal | — | No |
| Robots / 5xx / 403 | 12 | Yes | Trivial | Yes |

**~80% of the flagged pages need no fixing at all. The remaining ~20% is a genuine, well-defined, retainer-sized project — and the traffic trend says it's urgent.**

---

## 11. Open Items / Data Still Needed

- Drilldown exports for: Blocked by robots.txt (9), Server error 5xx (2), 403 (1), Indexed-though-blocked (1)
- GSC **Performance** data (queries, pages, CTR, position) — the coverage export alone can't explain the 49% impression drop
- Year-over-year comparison to isolate seasonality
- Full site crawl to confirm the thin-content diagnosis
- Both drilldowns for "Alternate page" and "Crawled – not indexed" are **capped at 1,000 rows by GSC** (actual totals 2,995 and 1,130) — percentages in §4 are extrapolated from those samples
