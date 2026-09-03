# SEO AUDIT REPORT

**Client:** Cleanroom Film & Bags (CFB)
**Website:** https://www.cleanroomfilm.com/
**Audit Date:** 2026-07-16
**Auditor:** SEO AI Assistant
**Platform:** HubSpot CMS

---

## 1. Overall SEO Health Score: 62 / 100

**Summary:** CFB has a healthy technical foundation and genuinely strong credibility assets (35+ years, ISO 9001:2015, ISO 14644 Class 5, exclusive CLEANTUFF® manufacturing), but is losing significant search visibility to a missing homepage meta description, mis-targeted title tags, near-total absence of structured data, and thin category-page content — all highly fixable within weeks.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Add homepage meta description + fix homepage title tag (currently bag-focused, 74 chars, truncating in SERPs) | High | Low |
| 2 | Rewrite the mis-targeted /our-story title tag ("Sterile Packaging: Healthcare, Pharmaceutical, & Food Bags") to match page content | High | Low |
| 3 | Deploy Organization + Product + BreadcrumbList schema sitewide (only a VideoObject exists today) | High | Medium |
| 4 | Expand thin category pages (/products ~150 words, /standards ~280, /markets ~450) to 600–1,000 words of unique, spec-driven copy | High | Medium |
| 5 | Surface E-E-A-T: team/engineering bios, customer logos/testimonials, and dated case studies in the Learning Center | Medium | Medium |

---

## 3. Quick Wins (Do in < 1 Week)

- [x] **Add homepage meta description** ✅ **Completed 2026-07-16.** Copy: `ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years. Get a quote.` (150 chars)
- [x] **Rewrite homepage title tag.** ✅ **Completed 2026-07-16.** New: `Cleanroom Packaging: Film & Bags Manufacturer | CFB` (52 chars); replaced `Cleanroom Bags: Medical & Electronic Packaging | ESD Bags | CFB California` (74 chars)
- [ ] **Fix /our-story title tag.** Current title is a keyword-stuffed mismatch (`Sterile Packaging: Healthcare, Pharmaceutical, & Food Bags | CFB`). Suggested: `Our Story: 35+ Years of Cleanroom Packaging Excellence | CFB` (61 chars)
- [ ] **Add meta description to /markets** (currently missing). Suggested: `Cleanroom packaging for medical, pharmaceutical, semiconductor, aerospace, electronics & food industries. ISO-certified, USA-made film and bags.` (145 chars)
- [ ] **Add `Sitemap: https://www.cleanroomfilm.com/sitemap.xml` to robots.txt** (sitemap exists but is not declared).
- [x] **Paste Organization JSON-LD schema into the site header** ✅ **Completed 2026-07-16** (final client-verified code in Appendix) via HubSpot Settings → Website → Pages → Site Header HTML.
- [ ] **Add publish dates to all Learning Center articles** — only one article (Mar 26, 2024) shows a date; undated content weakens freshness and trust signals.
- [ ] **Fix the homepage H1.** The hero currently splits the H1 across three phrases ("From the tiniest of micro-chips…"). Consolidate to one keyword-bearing H1, e.g. `Cleanroom Packaging Film & Bags — Sterile Delivery When It Matters Most`, and demote the narrative lines to styled paragraph text.

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] **Expand /products to 600+ words**: add a comparison intro (film vs. bags decision guidance), typical applications by industry, cleanliness class options, and MOQ/customization notes. Currently ~150–200 words of thin hub content.
- [ ] **Expand /standards with per-standard explanations**: each ASTM/USP/MIL/NASA standard listed in the chart deserves 2–3 sentences on what it certifies and why a buyer should care. This page is a link-magnet opportunity for engineers.
- [ ] **Rework /materials title tag** — it currently targets only ESD (`ESD Packaging: Anti-Static Polyethylene Bags & Film | LDPE Film`) while the page covers 11 materials. Either split ESD onto its dedicated material page or retitle to `Cleanroom Packaging Materials: Nylon, Tyvek®, Aclar® & More | CFB`.
- [ ] **Add Product schema to every product page** (rollstock, tubing, sheeting, each bag type) with brand, material, and audience properties.
- [ ] **Add BreadcrumbList schema** sitewide — the site has a deep Markets → Products → Materials hierarchy that qualifies for breadcrumb rich results.
- [ ] **Create an About/Team layer on /our-story**: leadership and engineering bios with credentials ("120 years of combined blown film expertise" is claimed — name the people behind it).
- [ ] **Add customer proof**: client logos (Boeing case study already exists — leverage it), testimonials, and industries-served counts above the fold on the homepage.
- [ ] **Internal linking pass**: link Learning Center case studies contextually from the relevant market pages (e.g., Boeing/Aclar® case study → /markets/aerospace and /materials/aclar).
- [ ] **Descriptive image alt text** across product and market pages — most images carry generic or missing alt attributes.
- [ ] **Claim/optimize Google Business Profile** for the Placentia, CA facility (manufacturer category) — supports branded search and map-pack for "cleanroom packaging manufacturer near me" B2B queries in SoCal.
- [ ] **Build a VCI packaging landing page** (client priority, added 2026-07-16). VCI (volatile corrosion inhibitor) bags/film currently have zero presence in the sitemap — no page, no mention — so all VCI keywords track as "Not ranked." Create `/products/vci-bags` (or `/materials/vci`) targeting *VCI bags, VCI film, VCI packaging, anti-corrosion packaging, rust prevention packaging* with product specs, applications (metal parts, aerospace/defense components in storage and transit), and Product schema. Cross-link from aerospace and electronics market pages.

---

## 5. Long-Term / Strategic Recommendations

- [ ] **Build a technical resource hub** in the Learning Center: material selection guides, cleanliness class explainers (ISO 14644 Class 5 vs. Class 100), sterilization compatibility charts, outgassing spec sheets. This is the #1 defensible moat for a niche B2B manufacturer.
- [ ] **Promote the CLEANTUFF® and ULO branded-material category**: dedicated pages already exist and are strong (/materials/cleantuff-cleanroom-packaging is ~1,200 words with full spec tables; /materials/extreme-low-outgassing-cleanroom-packaging has good title/meta) — but they are buried. Feature them on the homepage, cross-link from case studies and market pages, and add comparison content ("CLEANTUFF® vs. standard polyethylene") since CFB is the exclusive manufacturer.
- [ ] **Systematic case-study program**: one dated, structured case study per quarter per key market (medical, semiconductor, aerospace), each with measurable outcomes and Article schema.
- [ ] **Digital PR / industry citations**: pursue listings and mentions in Thomasnet, industry directories (medical device, semiconductor supply chain), and trade publications to build authority backlinks.
- [ ] **Track and target bottom-funnel comparison queries**: "cleanroom bags supplier," "Class 100 packaging manufacturer," "Tyvek pouch manufacturer USA" — build dedicated landing pages where volume justifies.
- [ ] **Video SEO expansion**: the homepage overview video already has VideoObject schema — build a library (facility tour, material tests) with transcripts to capture video results.

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Present, needs work | Present on all pages but homepage is bag-only + 74 chars; /our-story and /materials titles mismatched to content |
| Meta Descriptions | Partial | **Missing on homepage and /markets**; present on /products, /materials, /standards, /contact-us, /our-story |
| H1 Tags | Present | Homepage H1 split across 3 narrative phrases with no keyword; interior pages use short generic H1s ("Products", "Standards") |
| Heading Hierarchy (H2–H4) | Issues | /markets and /our-story have no H2s; hierarchy is shallow across interior pages |
| Schema / Structured Data | Minimal | Only VideoObject on homepage. No Organization, LocalBusiness, Product, BreadcrumbList, or Article schema |
| Canonical Tags | Present | Self-referencing canonical confirmed on homepage |
| XML Sitemap | Present | Valid, 52 page URLs, images + video included, lastmod dates current (Apr–Jun 2026) |
| Robots.txt | Present | Clean HubSpot rules; **no Sitemap declaration** |
| Page Speed | Moderate | HubSpot-hosted with hero video; recommend Core Web Vitals check in PageSpeed Insights (video poster/lazy-load) |
| Mobile Friendliness | Good | Responsive HubSpot theme; mobile nav present |
| HTTPS / SSL | Present | Sitewide |
| Broken Links | None found | No 404s among primary pages tested; careers link goes off-domain to share.hsforms.com |
| Image Alt Text | Partial | Category images use generic alts ("cleanroom film products"); most images lack descriptive alt text |

**Key Technical Issues:**
- Homepage missing meta description — Google composes its own snippet, forfeiting CTR control on the highest-traffic page. Fix: add the description in HubSpot page settings.
- Only schema on the entire site is a single VideoObject — the site is invisible to rich results (org knowledge panel, breadcrumbs, product snippets). Fix: deploy the Appendix JSON-LD.
- Robots.txt lacks a sitemap declaration — one-line fix.
- Careers page links out to a raw HubSpot form URL (share.hsforms.com), leaking authority and looking untrustworthy; embed the form on an on-domain /careers page.

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: `Cleanroom Bags: Medical & Electronic Packaging | ESD Bags | CFB California` (74 chars — truncates; leads with only one product line and buries the brand)
- Meta Description: **Missing**
- H1: Split narrative — "From the tiniest of micro-chips" / "To life saving surgical tools" / "Our cleanroom packaging ensures a safe and sterile delivery when it matters most."
- Notes: ~800–1,000 words; strong benefit-focused copy and clear CTAs ("Request a Quote Today"), good trust markers (ISO 9001:2015, USA Made, 100% No Regrind Guarantee). Needs one consolidated keyword-bearing H1 and a meta description.

**/products:**
- Title: `Cleanroom Packaging Products | Cleanroom Film & Bags` — good
- Meta Description: Present
- H1: "Products"
- Notes: **Thin (~150–200 words)** — pure navigation hub. Biggest content-expansion opportunity on the site.

**/markets:**
- Title: `Markets | Poly Bag & Blown Film Manufacturing | Cleanroom Film & Bags`
- Meta Description: **Missing**
- H1: "Markets & Capabilities"
- Notes: ~400–500 words, tile-driven, no H2s. Each of the 7 industry tiles should link to a fleshed-out market page.

**/standards:**
- Title: `Cleanroom Packaging Standards | Cleanroom Film & Bags` — good
- Meta Description: Present
- H1: "Standards"
- Notes: ~280 words + standards chart (ASTM E595/F331/F2095/D1505/D6953/D257/F1140, USP 661/671, MIL-DTL-24466, NASA JPR 5322.1G, IEST-CC-1246, ANSI/ESD S-541). High-value chart, zero explanation — expand per-standard.

**/our-story:**
- Title: `Sterile Packaging: Healthcare, Pharmaceutical, & Food Bags | CFB` — **mismatched/keyword-stuffed**, page is actually company history
- Meta Description: Present but equally mismatched ("...such as food bags. Call today!")
- H1: "Our Story"
- Notes: ~400–450 words; good history (2003 CLEANFILM Inc. acquisition, ISO 14644 Class 5 equipment) but no people, no photos of team, no timeline.

**/contact-us:**
- Title: `Contact Us | Cleanroom Film & Bags` — good
- Meta Description: Present
- H1: "Contact Us"
- Notes: Clean NAP block; consider adding a map embed and LocalBusiness schema.

**/learning-center:**
- 10 articles including two strong case studies (Boeing/Aclar®, FOUP semiconductor packaging). Only one article shows a publish date. No visible Article schema or category filtering.

**Content Quality Summary:**
- Word count: Homepage healthy (~900); category pages thin (150–500 words)
- Keyword targeting: Inconsistent — strong on "cleanroom" family but title tags fight each other (/materials targets ESD only; /our-story targets sterile packaging)
- Internal linking: Navigation-strong, contextual-weak; case studies not linked from market/material pages
- CTAs: Strong — "Request a Quote Today" prominent, phone/email in footer sitewide

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [ ] Author/team bios — none
- [x] Credentials — ISO 9001:2015, ISO 14644 Class 5, Class 100 facility
- [x] Years in business — "More than 35 years"; "120 years of combined blown film manufacturing expertise"
- [x] Professional standards — ASTM, USP, MIL, NASA, IEST, ANSI/ESD compliance chart
- [ ] Media mentions — none visible
- [ ] Client testimonials — none (case studies name Boeing but no quotes)
- [ ] Awards — none visible (Verified Vendor Seal 2024 in footer only)
- [x] About page — /our-story exists but is thin

**E-E-A-T Gaps:**
- No named humans anywhere on the site. Fix: leadership + engineering bios with credentials on /our-story.
- "120 years of combined expertise" is claimed but unsubstantiated. Fix: attribute it to named team members.
- Boeing and organ-transplant case studies exist but carry no dates, client quotes, or Article schema. Fix: restructure as dated, quoted, schema-marked case studies.
- Verified Vendor Seal (2024) is aging — renew/update or remove.

**Local SEO:**
- Google Business Profile: Unknown — verify claim status for 1700 Barcelona Circle, Placentia, CA (category: Packaging Supply Store / Manufacturer)
- NAP Consistency: Consistent sitewide (address, phone (714) 744-8361, fax); note email domain is cleanbags.com vs. site domain cleanroomfilm.com — keep NAP citations consistent
- Geographic targeting in content: Weak — "California" appears only in the homepage title tag; "USA Made" is the stronger national angle
- Local citations / directories: Prioritize Thomasnet, industry-specific B2B directories over consumer citations — this is a national B2B play with a local facility anchor

---

## 9. Conversion & User Experience Issues

- **CTAs:** Strong primary CTA ("Request a Quote Today"); present on homepage and via nav. Add quote CTAs to every product/material page bottom.
- **Contact friction:** Low — phone, email, and form all available. Form fields not excessive.
- **Mobile UX:** Responsive theme, functional mobile nav.
- **Page load perception:** Hero video on homepage — ensure poster image + lazy-load so LCP isn't video-blocked.
- **Trust signals above the fold:** Partial — certifications live mid-page/footer; move ISO badges + "35+ Years / USA Made" strip up near the hero.
- **Navigation clarity:** Clear three-axis IA (Markets / Products / Materials) — genuinely good for a B2B catalog. Careers link exiting to share.hsforms.com is the one confusing element.

**Key Fixes:**
- Add ISO 9001:2015 + Class 100 badges and "35+ Years • USA Made" trust strip above the fold on homepage.
- Embed careers form on-domain.
- Add a map embed + directions on /contact-us.
- Add quote-request CTA blocks to the bottom of all product, material, and market pages.

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| cleanroom packaging | Commercial | Medium | Homepage |
| cleanroom bags | Commercial | Medium | /products/cleanroom-bags |
| cleanroom film | Commercial | Medium | /products/cleanroom-film |
| Class 100 cleanroom packaging | Commercial | Low | /standards |
| ESD bags manufacturer | Commercial | Medium | /materials/esd |
| Tyvek pouches medical packaging | Commercial | Low | /materials/tyvek |
| Aclar film packaging | Commercial | Low | /materials/aclar |
| medical device packaging manufacturer | Commercial | High | /markets/medical |
| semiconductor packaging bags | Commercial | Low | /markets/semiconductor |
| low outgassing packaging film | Commercial | Low | /materials/extreme-low-outgassing-cleanroom-packaging (exists — promote) |
| how to choose medical device packaging | Informational | Low | Learning Center (exists — optimize) |
| ISO 14644 Class 5 packaging requirements | Informational | Low | New Learning Center guide |
| sterile barrier packaging materials | Informational | Medium | New Learning Center guide |
| anti-static vs static shielding bags | Informational | Low | New Learning Center guide |
| VCI bags | Commercial | Low | New VCI page (client priority) |
| VCI packaging | Commercial | Low | New VCI page (client priority) |
| anti-corrosion packaging | Commercial | Low | New VCI page (client priority) |

**Content Gap Analysis:**
- CLEANTUFF®/ULO pages exist and are substantial (spec tables, good metas) but are under-promoted: not featured on the homepage, no comparison content, and weak contextual internal links — the single most defensible keyword territory CFB owns, left unamplified.
- Standards page lists 15+ certifications with zero explanatory content — engineers search these standard numbers directly.
- No comparison/selection guides (film vs. bag, material vs. material, cleanliness class vs. class) — the highest-intent informational queries in this niche.
- Market pages are tiles, not pages — each of 7 industries deserves 800+ words of application-specific content.
- **VCI packaging has zero site presence** despite being a client priority — no page, no product mention, no sitemap entry. Every VCI keyword is an uncontested "Not ranked" until a page exists.

**Recommended Content Pieces:**
1. "ISO 14644 Cleanroom Classes Explained: What Class 5 / Class 100 Means for Your Packaging" — targets *ISO 14644 Class 5 packaging*, serves spec-driven engineers
2. "Anti-Static vs. Static Shielding vs. ESD Bags: Which Does Your Product Need?" — targets *anti-static vs static shielding bags*, high-converting comparison intent
3. "CLEANTUFF® vs. Standard Polyethylene: Outgassing and Particulate Performance" — owns exclusive branded material territory
4. "Sterilization Compatibility Guide: Which Packaging Materials Survive Gamma, EtO, and Autoclave" — targets medical device packaging engineers
5. "Tyvek® vs. Medical-Grade Paper for Sterile Barrier Systems" — targets *Tyvek pouches medical packaging*
6. Quarterly dated case studies per market (semiconductor FOUP and Boeing/Aclar® already exist — restructure with dates, quotes, outcomes, Article schema)
7. **VCI packaging landing page** (client priority) — targets *VCI bags / VCI packaging / anti-corrosion packaging*; specs, applications, Product schema, cross-links from aerospace + electronics market pages

---

## 11. Next Steps & Action Plan

1. **Week 1 — CFB marketing/HubSpot admin:** Fix homepage title + add meta description; fix /our-story title/meta; add /markets meta description; add sitemap line to robots.txt; add Organization schema to site header.
2. **Week 2 — Start Advertising:** Homepage H1 consolidation; trust-badge strip above fold; Learning Center publish dates; GBP claim/verification for Placentia facility.
3. **Weeks 3–4 — Start Advertising + CFB product team:** Expand /products and /standards copy; /materials title fix; descriptive alt-text pass on top 20 images.
4. **Weeks 5–8 — Start Advertising:** Product + BreadcrumbList schema rollout; team bios on /our-story; internal-linking pass (case studies ↔ market/material pages); on-domain careers page.
5. **Quarter 2 — joint:** CLEANTUFF®/ULO promotion (homepage feature + comparison guides); case-study program kickoff; Thomasnet + industry directory citations.

---

## Appendix

### Suggested Title Tags
| Page | Recommended Title Tag |
|---|---|
| Homepage | `Cleanroom Packaging: Film & Bags Manufacturer | CFB` |
| /products | `Cleanroom Packaging Products: Film & Bags | CFB` (current is fine; keep) |
| /markets | `Industries We Serve: Medical, Semiconductor, Aerospace | CFB` |
| /materials | `Cleanroom Packaging Materials: Nylon, Tyvek®, Aclar® & More | CFB` |
| /standards | `Cleanroom Packaging Standards & Certifications | CFB` |
| /our-story | `Our Story: 35+ Years of Cleanroom Packaging Excellence | CFB` |
| /contact-us | `Contact Us: Request a Cleanroom Packaging Quote | CFB` |

### Suggested Meta Descriptions
| Page | Recommended Meta Description |
|---|---|
| Homepage | `ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years. Get a quote.` (150 chars) |
| /markets | `Cleanroom packaging for medical, pharmaceutical, semiconductor, aerospace, electronics & food industries. ISO-certified, USA-made film and bags.` |
| /our-story | `35+ years of cleanroom packaging excellence. Class 100 vertically integrated facility in Placentia, CA. Exclusive manufacturer of CLEANTUFF® and ULO films.` |
| /products | `Cleanroom film (rollstock, tubing, sheeting) and cleanroom bags (zipper, header, heat-seal pouches) manufactured in ISO-certified Class 100 cleanrooms.` |

### Schema Code

**Organization schema (site header, all pages):**
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Cleanroom Film & Bags",
  "alternateName": "CFB",
  "url": "https://www.cleanroomfilm.com/",
  "logo": "https://www.cleanroomfilm.com/hubfs/CFB%20Logo.png",
  "description": "ISO-certified cleanroom packaging manufacturer. Class 100 film & bags for medical, semiconductor, aerospace & pharma. USA made 35+ years.",
  "foundingDate": "1990",
  "slogan": "Sterile Packaging Solutions That Fuel Life",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "1700 Barcelona Circle",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-714-744-8361",
    "contactType": "sales",
    "email": "sales@cleanbags.com",
    "areaServed": "US"
  },
  "sameAs": [
    "https://www.linkedin.com/company/cleanbags"
  ],
  "hasCredential": [
    { "@type": "EducationalOccupationalCredential", "name": "ISO 9001:2015" },
    { "@type": "EducationalOccupationalCredential", "name": "ISO 14644-1 Class 5 Cleanroom" }
  ],
  "knowsAbout": [
    "cleanroom packaging",
    "cleanroom film",
    "cleanroom bags",
    "ESD packaging",
    "low outgassing films",
    "CLEANTUFF films",
    "Tyvek pouches",
    "sterile barrier packaging"
  ]
}
```

**LocalBusiness schema (/contact-us):**
```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "@id": "https://www.cleanroomfilm.com/#business",
  "name": "Cleanroom Film & Bags",
  "image": "https://www.cleanroomfilm.com/hubfs/facility.jpg",
  "url": "https://www.cleanroomfilm.com/",
  "telephone": "+1-714-744-8361",
  "faxNumber": "+1-714-744-8360",
  "email": "sales@cleanbags.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "1700 Barcelona Circle",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "geo": { "@type": "GeoCoordinates", "latitude": 33.8925, "longitude": -117.8531 },
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
    "opens": "08:00",
    "closes": "17:00"
  }
}
```
*(Verify geo coordinates and business hours with client before deploying.)*

**BreadcrumbList schema (example — product page):**
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.cleanroomfilm.com/" },
    { "@type": "ListItem", "position": 2, "name": "Products", "item": "https://www.cleanroomfilm.com/products" },
    { "@type": "ListItem", "position": 3, "name": "Cleanroom Bags", "item": "https://www.cleanroomfilm.com/products/cleanroom-bags" }
  ]
}
```

### Full URL Inventory (from sitemap.xml — 52 page URLs)

**Core (7):** `/`, `/products`, `/markets`, `/materials`, `/standards`, `/our-story`, `/contact-us`, `/learning-center`

**Markets (7):** aerospace, electronic, food, healthcare, medical, pharmaceutical, semiconductor — all at `/markets/[industry]-cleanroom-packaging`

**Materials (11):** `/materials/aclar-cleanroom-packaging`, `anti-static-nylon-`, `barrier-`, `cleantuff-`, `esd-`, `extreme-low-outgassing-` (ULO), `nylon`, `nylon-polyethylene-`, `polyethylene-`, `static-shielding-`, `tyvek-cleanroom-packaging`

**Products (15):** `/products/cleanroom-bags` (+ 7 children: bags-on-a-roll, bottom-seal, header, zipper, gusseted, high-heat-oven, tyvek-heat-sealing-pouches), `/products/cleanroom-film` (+ 4 children: rollstock, sheeting, square-bottom-covers, tubing), `/products/aluminum-foil-bags`, `/products/medical-film-rolls`, `/products/medical-grade-paper-rolls`

**Learning Center (10):** 2 case studies (Boeing/Aclar®, FOUP high-density resin), 2 story pieces (organ transplant, outer space), 5 announcements, 1 how-to guide (medical device packaging)

Use this inventory as the optimization checklist for the Weeks 3–8 title/meta/schema/alt-text passes.

### Additional Notes
- **Platform:** HubSpot CMS — meta titles/descriptions edited per-page under Page Settings; sitewide schema goes in Settings → Website → Pages → Site Header HTML.
- **Tools recommended:** Google Search Console (verify + submit sitemap), PageSpeed Insights for Core Web Vitals on the video-heavy homepage, Schema Markup Validator for the JSON-LD above, Thomasnet profile audit.
- **Schema values confirmed 2026-07-16:** logo URL (`/hubfs/CFB%20Logo.png`), LinkedIn (`linkedin.com/company/cleanbags`), and `foundingDate: 1990` all confirmed by client; no active Facebook, Instagram, or YouTube. Schema deployed to site header 2026-07-16.
- **Footer trust leak:** the footer displays social icons for platforms the client isn't active on — remove dead icons or link only LinkedIn.
- **Watch-out:** Email domain (cleanbags.com) differs from web domain (cleanroomfilm.com). Not an SEO error, but keep NAP citations identical everywhere and consider a redirect strategy if cleanbags.com has legacy backlinks worth consolidating.
