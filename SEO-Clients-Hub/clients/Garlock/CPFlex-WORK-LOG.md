# CPFlex — SEO Implementation Work Log

**Client:** C-P Flexible Packaging | **Site:** https://gcpflexpack.com  
**HubSpot staging:** https://gcpflexpack-24024882.hs-sites.com  
**Source of truth:** `Garlock-On-Page-SEO-Audit-Running-Log.docx` (site pages) · `Garlock-Blog-SEO-Audit-Running-Log.docx` (31 articles)

**Progress: 77 / 77 site pages implemented on staging · 28 / 31 blog articles (title/meta/schema) — verified 2026-09-08. Remaining: 3 blog articles + internal-link pass.**

> **Site status (verified 2026-09-07):** the new site is a PRE-LAUNCH HubSpot build. All SEO work lives on the staging domain. gcpflexpack.com is registered (May 2026, Cloudflare NS) but currently a Directnic parking page. Today's live sites are www.cpflexpack.com (WordPress) and www.garlockflexibles.com (HubSpot, same portal 24024882). Launch cutover (connect domain, HTTPS, 301 maps from both old sites, sitemap, robots, GSC) is a separate open workstream.

---

## SITE PAGES — log clean-up still needed

Word log entries for the 5 former "pending" pages were updated 2026-09-07 after verifying each on staging:

| Page | Verified on staging | Word log status | Billing in log |
|------|------|------|------|
| ~~Homepage~~ | ✅ title/meta/schema/9 of 10 alts | **updated 2026-09-07** | Billed |
| About Us | ✅ title/meta/alt/AboutPage schema | **updated 2026-09-07** | Ready to bill |
| Premade Pouches Glossary | ✅ title/meta/H1/DefinedTermSet schema (published 2026-09-07) | **updated 2026-09-07** | Ready to bill |
| Flexible Packaging Calculator | ✅ title/meta/H1/WebApplication schema | **updated 2026-09-07** | Billed |
| Careers | ✅ title/meta/10 of 12 alts (2 filename alts remain) | **updated 2026-09-07** | Billed |

Contact Us: already Implemented + Billed in the Word log; verified live 2026-09-07 and editor link fixed.

**Open fixes on the Homepage (HubSpot editor 212643437655):**
1. Hero image `sustainable-paper-packaging.webp` alt → "Sustainable paper-based flexible packaging from C-P Flexible Packaging" (currently duplicates the sustainability-block alt).
2. Global logo alt → "C-P Flexible Packaging company logo" (currently "Garlock CP Flex Logo" / "Garlock CP Flex Logo (1)"); remove duplicate "(1)" logo file.

**Optional:** Contact page map pins all share alt="Preferred Packaging" — set alt="" or per-location names.

---

## BLOG / Learning Center (31 articles) — verified against staging 2026-09-07

Source of truth: **`Garlock-Blog-SEO-Audit-Running-Log.docx`** — every article now has a green status line under its heading (added 2026-09-07).

**Title + meta + BlogPosting schema + alt text: 28 of 31 done** (Cut Costs published 2026-09-07 and Hand Sanitizer 2026-09-08, 1.25 h each). HubSpot DOES auto-emit a structured-data block on every post (author "Admin", publisher "Garlock Flexibles", Garlock Logo.jpg) — usually typed WebPage, but BlogPosting on posts with a featured image. The audited BlogPosting block sits alongside it. Fix the publisher name/logo at blog-settings level (Settings → Website → Blog) so the auto block stops saying Garlock Flexibles.

### Still to do in HubSpot (3 articles)

| # | Article | What's missing | Editor |
|---|---|---|---|
| 5 | Commercializing Recyclable Stand-Up Pouches | page title only | blog editor → Settings |
| 30 | GreenStream Compostable Fiber Trays | page title only | blog editor → Settings |
| 15 | Highest BRCGS Ratings for Quality & Safety | meta only (+ add "2024" to H1) | blog editor → Settings |

Paste-ready title/meta/schema for each is in the Word log under the article heading. A consolidated sheet is in `Blog-Remaining-7-Paste-Sheet.md`.

> ⚠️ **Logo defect:** all 24 live BlogPosting blocks use the placeholder publisher logo `https://gcpflexpack.com/hubfs/logo.png`, which does not exist. The real file is `https://gcpflexpack.com/hubfs/Garlock%20CP%20Flex%20Logo.png`. Fix in one pass (or move the publisher block into the blog template) before launch.

### Internal-link pass (second phase, body edits)

The audit's "Internal Links to Add" are mostly not in the post bodies yet (18 articles), and the three cold-seal articles (#1, #2, #4) do not yet link up to the cluster hub (#3 How to Choose a Cold-Seal Supplier). Do this as one pass after the 7 above.

### Billing

3 articles billed (HPP Lidding, Virtual Press Check, Hand Sanitizer). 28 unbilled — bill by hours logged under Garlock, per the usual model.

---

## COMPLETED — Site Pages (~58 pages)

Implemented June–July 2026. Documented in `Garlock-On-Page-SEO-Audit-Running-Log.docx`.

| Page | Date |
|------|------|
| Aerospace Packaging | 2026-06-24 |
| Autoclave Bags | 2026-06-24 |
| Bags Hub (Functional Bags & Pouches) | 2026-06-24 |
| Laser Die-Cut Window Rollstock | 2026-06-24 |
| Child-Resistant Flexible Packaging | 2026-06-24 |
| Coffee & Beverage Packaging | 2026-06-24 |
| Laser-Scored Pouches (Easy-Open) | 2026-06-25 |
| Cleanroom Bags | 2026-06-25 |
| Cold-Seal Packaging | 2026-06-25 |
| Compostable Flexible Packaging | 2026-06-25 |
| Compostable Pouches | 2026-06-25 |
| Confectionery Packaging | 2026-06-25 |
| Cookie & Bakery Packaging | 2026-06-25 |
| Custom Shrink Bands | 2026-06-26 |
| Cold-Seal Packaging Guide (Download) | 2026-06-26 |
| Flat-Bottom Pouches | 2026-06-26 |
| Flexible Package Design & Consultation | 2026-06-26 |
| Flexible Packaging Prepress Services | 2026-06-26 |
| GreenStream Sustainable Packaging | 2026-06-26 |
| Health & Wellness Packaging | 2026-06-26 |
| HFFS & VFFS Rollstock | 2026-06-26 |
| High Burst Strength Packaging | 2026-06-26 |
| High-Barrier Flexible Packaging | 2026-06-26 |
| HD Flexographic Printing | 2026-06-26 |
| Inno-Lok Pre-Zippered Rollstock | 2026-06-26 |
| Internal Recycling Facility | 2026-06-26 |
| Lidding Films | 2026-06-28 |
| Markets Hub | 2026-06-28 |
| Matte & Gloss Pouches | 2026-06-28 |
| Matte/Gloss Rollstock | 2026-06-28 |
| Medical & Electronics Cleanroom Packaging | 2026-06-29 |
| Nylon Bags | 2026-06-29 |
| Our Companies | 2026-06-29 |
| Flexible Packaging Technology | 2026-06-29 |
| Products Hub | 2026-06-29 |
| Package Finishing & Pouch Converting | 2026-07-08 |
| Paper Flexible Packaging | 2026-07-08 |
| Paper Pouches | 2026-07-09 |
| Peel & Reseal Rollstock | 2026-07-09 |
| Pet Food Packaging | 2026-07-10 |
| Poly Bags | 2026-07-13 |
| Pouches With Handles | 2026-07-13 |
| Privacy Policy | 2026-07-24 |
| Recyclable Flexible Packaging | 2026-07-24 |
| Recyclable Pouches | 2026-07-24 |
| Request a Consultation | 2026-07-24 |
| Roll-Fed Labels | 2026-07-24 |
| Shrink Sleeves | 2026-07-24 |
| Slider Pouches | 2026-07-24 |
| Snack Food Packaging | 2026-07-27 |
| Solar-Powered Manufacturing | 2026-07-27 |
| Spouted Pouches | 2026-07-27 |
| Stand-Up Pouches | 2026-07-27 |
| EMI Static Shielding Bags | 2026-07-27 |
| Stick Packs | 2026-07-27 |
| Stretch Sleeves | 2026-07-28 |
| Sustainable Packaging Hub | 2026-07-28 |
| Thermoformed Food Trays | 2026-07-28 |
| Zipper Pouches | 2026-07-28 |
| Lakeville, MN Location | 2026-08-13 |

---

## Notes

- `fruth-placentia-audit.md` in `Page Audits/` is a misplaced Fruth client file — do not implement for CPFlex.
- Location pages (York, Aurora, Bristol, Buffalo, Fond du Lac, Placentia, Preferred Packaging, Lakeville) are in the Word log as Billed with no status line — treat as done.
- The running log docx says "Pages audited: 77" — some implemented pages (e.g. Spouted Pouches, Stand-Up Pouches, Zipper Pouches, Stick Packs) don't have standalone `.md` audit files; their copy-paste content is in the docx only.
