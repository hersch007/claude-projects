# CLIENT: Ari + Ava

**Website:** https://www.shopariandava.com
**Niche:** Plus-size women's clothing boutique — bricks-and-mortar store in East Nashville plus a national ecommerce operation, targeting plus-size women (1XL–3XL, sizes up to 5XL) who want on-trend, size-inclusive fashion rather than utility plus-size wear.
**Services:** Plus-size women's apparel retail — Judy Blue denim, tops, dresses, bottoms, outerwear, accessories, fragrance, gift cards; in-store shopping, online ordering, rewards programme
**Goals:** Recover organic visibility (impressions down 49% since May 2026), get 1,130 crawled-but-unindexed pages into Google's index, and capture local "plus size boutique Nashville" demand alongside national Judy Blue denim search
**Platform:** Shopify
**Location:** 203 N 11th St, Nashville, TN 37206 (East Nashville) — ships nationally

Key pages to analyze:
- Homepage (/)
- /collections/judy-blues — strongest commercial collection
- /collections/maxi-dresses, /collections/dresses, /collections/bottoms
- /pages/visit-our-store — the de facto "About Us" destination
- /products/{handle} — ~700+ product pages
- /policies/contact-information — the site's only contact route

**Status:** PROSPECT — not yet a signed client. Onboarded to the hub 2026-08-17 off the back of Google Search Console coverage data supplied by the client/prospect.

Notes: Better optimised than expected — titles and H1s are genuinely keyword-targeted ("Nashville Plus Size Women's Boutique Clothing"), collection pages carry 85–400 words of unique copy, faceted navigation is in place, and **the store address and hours sit in the site-wide footer on every page** (verified on product pages), which is a properly-implemented local signal. The gaps are content and trust, not architecture: `/pages/visit-our-store` serves the About role and is linked from nav and footer, but carries only ~60 words of unique text — **no founder story, no photography, no Callie bio**. **No phone number or email anywhere on the site**; the only contact route is /policies/contact-information, which robots.txt disallows. **Zero customer reviews on any product page checked (3/3)** — no AggregateRating, no star rich results. No blog (/blogs/news is an empty template). Product copy runs 50–115 words on wholesale items (vendor: Heimish) sold by hundreds of competing boutiques. Owner/buyer "Callie" is the brand's public face — "Callie's Newest Favs" on the homepage, "Callie's Comments" on product pages — yet /collections/callie returns 404. Crawl budget is consumed by ~3,600 recommendation-app parameter URLs and ~700 Shopify /services/ endpoints.

**Apps identified (browser DOM inspection):** Yotpo (loyalty), Klaviyo (email — renders a full-viewport fixed overlay on load, z-index 90000), a Shopify store-locator modal, PhotoSwipe, custom size-guide drawer. The `pr_*` recommendation app is still unidentified. Social handles in the footer are confirmed correct and fixed by the client, and the **Google Business Profile is claimed** — do not raise either as a finding. A measured 50px full-width overlap band exists between the Yotpo rewards bar and the nav dropdown (needs manual visual confirmation — see audit §9).

See AA-INDEX-COVERAGE-ANALYSIS-2026-08-17.md for the full GSC index breakdown, and the revision note in AA-SEO-AUDIT-2026-08-17.md §1 for corrections applied after client challenge.
