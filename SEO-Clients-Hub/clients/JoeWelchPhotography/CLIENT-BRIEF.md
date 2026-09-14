# CLIENT: Joe Welch Photography

**Website:** https://joewelchphoto.com
**Niche:** Real estate & aerial photography for residential, commercial, hospitality, and construction clients across South Florida and the Caribbean
**Services:** Residential Real Estate Photography, Commercial Photography, Construction Photography, Hotel & Resort Photography, Aerial/Drone Photography, Dusk/Twilight Photography, Vacation Rental (HDR) Photography
**Goals (per Joe's own inquiry, 2026-09-14):** Generate more qualified photography inquiries — not simply more traffic. Priority services to rank for: aerial/drone photography, commercial photography, and hotel/resort photography. **Fort Lauderdale and Broward County are the primary target market; Miami-Dade and Palm Beach are secondary.** This reorders the market priority assumed in the first pass (which had led with Miami-Dade) and elevates aerial/commercial/resort above residential real estate as the top service lines to optimize for.
**Platform:** Not directly confirmed — mix of clean extension-less URLs and legacy `.html` URLs suggests an older CMS or a partial platform migration
**Location:** Serves Miami-Dade, Broward, and Palm Beach Counties, FL, and the Caribbean per on-site copy, with Fort Lauderdale/Broward as the primary market per the client directly; the business's Yelp listing surfaces under Pompano Beach, FL — reconcile which is the primary address before publishing citations
**Prepared by:** Parts of Practice (richard@partsofpractice.com)
**Referral source:** Joe is the brother of existing client Karen Welch Buttars — reached out directly 2026-09-14 citing that relationship

**Notes:** 15+ years in business, 1,000+ shoots completed, 100% satisfaction guarantee (free reshoot policy). Originally founded in 2004 as "The Elevated Photography Company" (mast-mounted aerial photography). Publicly listed contact numbers: 954-695-8730 (Broward), 1-877-253-8611 (toll-free/Caribbean). A near-identically named, unrelated business — joewelchphotography.com — operates independently and creates real brand-search confusion; this is the single highest-priority fix. Live technical crawl completed 2026-09-14 via `seo-tool` (39 pages, SEO Health Score 70/100 after a scoring-engine fix — see below) — see `JWP-SEO-AUDIT-2026-09-14.md` and the regenerated HTML report for full page-by-page data. Neither `/real-estate-photography.html` nor `/hdr-photography.html` (the two legacy duplicate URLs flagged in the first pass) surfaced in this crawl — worth confirming directly whether they've been redirected/removed or are just unlinked/unsitemapped. Confirmed via live crawl: the site does carry JSON-LD schema (`Organization`, `WebSite`, `WebPage`, `BreadcrumbList`) but **no `LocalBusiness` or `Person` type** — the entity-disambiguation fix (Priority #1) still needs to be added, it isn't already covered by existing schema.

**Audit tool update (2026-09-14):** The `seo-tool/audit.js` scoring engine previously only deducted for *missing* title/H1 tags, not for present-but-wrong ones (too short, too long, or duplicated) — those were silently folded into a small, capped, generic "improvement opportunities" bucket worth at most -15 total regardless of severity. Fixed to score poorly-sized titles and duplicate H1s as their own weighted deductions, and fixed schema-type detection to unwrap `@graph`-structured JSON-LD (previously reported "Unknown" for every page with this pattern, which is common on Yoast-based WordPress sites). This dropped Joe Welch Photography's score from 80 to 70 — the site didn't get worse, the score just got more accurate. This fix applies to all future runs for every client.

**Key pages identified via search index:**
- Homepage (`/`)
- `/about/`, `/contact/`, `/pricing/`, `/sitemap/`, `/portfolio/`
- `/residential-real-estate-photography/` — has a duplicate at the legacy `/real-estate-photography.html`
- `/commercial-photography/`, `/construction-photography/`, `/resort-photography/`
- `/aerial-photography/`, `/dusk-twilight-photography/`
- `/hdr-photography.html` — URL slug no longer matches its vacation-rental content
