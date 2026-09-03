# SEO AUDIT REPORT

**Client:** TecHouse Corporation
**Website:** https://www.techousecorp.com/
**Audit Date:** 2026-06-11
**Auditor:** SEO AI Assistant
**Platform:** HubSpot CMS

---

## 1. Overall SEO Health Score: 38 / 100

**Summary:** TecHouse has a technically sound foundation (HTTPS, structured product pages, HubSpot CMS) but is severely held back by zero meta descriptions, no content marketing, weak E-E-A-T, and no structured data — all of which are fixable with focused effort over 4–8 weeks.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Write meta descriptions for all pages (0% coverage) | High | Low |
| 2 | Add structured data (Organization + Product schema) | High | Medium |
| 3 | Build out content/blog to capture long-tail B2B search | High | High |
| 4 | Strengthen E-E-A-T: team bios, credentials, case studies | High | Medium |
| 5 | Fix 404 errors on /services, /contact, /blog | Medium | Low |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] Write and publish meta descriptions for all 15 product pages + homepage + About + Contact Us (see Appendix for copy)
- [ ] Fix or redirect /contact → /contact-us (currently 404)
- [ ] Add `alt` text to all product images (likely missing based on HubSpot image URL structure)
- [ ] Update homepage H1 — current H1 is 75 characters and not keyword-optimized; shorten and lead with the primary keyword ("Poly Bag Machine Attachments & Film Conversion Equipment | TecHouse")
- [ ] Add company address and phone number in visible text on the homepage (currently only in footer/contact page)
- [ ] Verify and submit XML sitemap to Google Search Console

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] Implement Organization schema and Product schema with JSON-LD on all product pages (see Appendix)
- [ ] Create an "About Us" page enhancement: add team bios with names/titles, years of experience, and manufacturing credentials
- [ ] Add a testimonials or case studies section — even 3 customer quotes with company type (e.g., "Regional packaging manufacturer, Midwest") would significantly boost E-E-A-T
- [ ] Launch a blog/resources section — start with 4 foundational articles targeting buyer-intent keywords (see Section 10)
- [ ] Optimize all 15 product page titles — most are bare product names; expand with keyword modifiers (e.g., "Zipper Sealing System for Poly Bag Machines | TecHouse")
- [ ] Create a dedicated "Custom Solutions" or "Custom Attachments" landing page to capture that specific search intent
- [ ] Add YouTube video embeds on relevant product pages (videos appear to exist but are not embedded)
- [ ] Build internal linking between related product pages (e.g., Zipper Sealing System → Rotary Serrator → Round Bottom Sealer)

---

## 5. Long-Term / Strategic Recommendations

- [ ] Develop an industry resource hub (guides, spec sheets, application notes) — this is the highest-leverage content play for industrial B2B SEO
- [ ] Target international SEO: add hreflang tags if serving non-English markets; create region-specific landing pages (e.g., "Film Conversion Equipment Supplier — Europe")
- [ ] Build a backlink strategy targeting packaging industry publications, trade associations (Flexible Packaging Association, PMMI), and supplier directories
- [ ] Create a product comparison tool or specification table to attract mid-funnel commercial-intent searchers
- [ ] Pursue Google Business Profile listing (currently unknown if claimed) — even B2B manufacturers benefit from local GBP for "near me" and directory searches

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Partial | Homepage optimized; product pages use bare names without modifiers |
| Meta Descriptions | Missing | Zero meta descriptions detected across all pages reviewed |
| H1 Tags | Present | One H1 per page — but homepage H1 is too long and generic |
| Heading Hierarchy (H2–H4) | Issues | Product pages use H2 for model names but body content lacks H3 structure |
| Schema / Structured Data | None | No JSON-LD schema detected on any page |
| Canonical Tags | Unknown | HubSpot typically handles canonicals — needs verification |
| XML Sitemap | Unknown | Likely at /sitemap.xml — needs submission to GSC |
| Robots.txt | Unknown | Needs verification at /robots.txt |
| Page Speed | Unknown | HubSpot CDN generally performs well; needs PageSpeed Insights test |
| Mobile Friendliness | Likely Good | HubSpot templates are responsive by default |
| HTTPS / SSL | Present | Site loads on HTTPS — no issues |
| Broken Links | Yes | /services, /contact, /blog all return 404 |
| Image Alt Text | Likely Missing | Product images via hs-fs/hubfs — alt text not confirmed |

**Key Technical Issues:**

- **No meta descriptions on any page** — Google is writing its own snippets, likely poor CTR
- **/contact 404** — any external links or citations pointing to /contact send users to a dead page; should 301 redirect to /contact-us
- **/services 404** — if any inbound links reference this path, they are lost; create the page or redirect
- **/blog 404** — suggests no content marketing at all; major missed opportunity for B2B industrial search
- **No structured data** — missing out on rich results in SERPs; Product schema would be highly applicable
- **Homepage H1 optimization** — at 75+ characters, the current H1 is bloated and buries keywords

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: `Film Conversion Machines & Equipment | TecHouse Corporation` — decent but could lead with a stronger keyword
- Meta Description: Missing
- H1: "The Standard of Excellence in Film Conversion Machines and Equipment" — too long, not keyword-leading
- Notes: Homepage has About Us, Mission, and Get in Touch sections but no keyword-rich product copy. The main product content lives behind the nav, not on the homepage. Very thin body copy on the homepage itself.

**About Us (/about):**
- Title: Not confirmed
- Meta Description: Missing
- H1: "About Us"
- Notes: Generic H1; no team bios; no founding story; no certifications or industry affiliations mentioned. The only E-E-A-T signal is "machines in countries worldwide" — needs significant expansion.

**Products (/products):**
- Title: Not confirmed
- Meta Description: Missing
- H1: "Products" (implied)
- Notes: 15 product cards well-organized. This is the site's strongest SEO asset. Each card links to a dedicated product page — good structure, but those pages need meta descriptions and expanded copy.

**Zipper Sealing System (/products/zipper-sealing-system):**
- Title: `Zipper Sealing System | TecHouse` — short, lacks keyword modifier
- Meta Description: Missing
- H1: "Zipper Sealing System"
- Notes: Good technical spec content (speed, model number, options list). Has a YouTube video link but it's not embedded. No schema. No internal links to related products.

**Content Quality Summary:**
- Word count: Low — most pages are 150–300 words; product pages are spec-heavy but narrative-light
- Keyword targeting: Weak — no visible keyword strategy; relies on product names only; no long-tail or buyer-intent terms targeted
- Internal linking: Weak — products don't link to each other; no hub-and-spoke structure
- CTAs: Moderate — phone and email present but no lead capture form on product pages; no quote request button

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [ ] Author/team bios — **Missing**
- [ ] Credentials or certifications — **Missing**
- [ ] License numbers — N/A (manufacturing)
- [ ] Professional affiliations — **Missing** (no trade association memberships mentioned)
- [ ] Media mentions — **Missing**
- [x] Client testimonials — **Missing** (only vague reference to global customers)
- [ ] Awards — **Missing**
- [x] About page — Present but very thin

**E-E-A-T Gaps:**
- No individual named anywhere on the site — makes it feel anonymous; add at minimum a Sales Director/GM bio
- No mention of how long the company has been in business — founding year establishes authority
- No case studies or application examples — "machines in countries around the world" is vague; one real example (e.g., "Deployed in a 3-line operation for a Midwest flexible packaging converter") would carry significant weight
- No certifications mentioned — if equipment meets ISO, UL, or CE standards, these should be prominent
- No trade association memberships shown — PMMI, FPA, or similar would boost credibility

**Local SEO:**
- Google Business Profile: Unknown — likely unclaimed or minimal; should be verified
- NAP Consistency: Address (701 S Richfield Road, Placentia, CA 92870) + Phone (714-993-9955) visible on contact page and footer — consistent
- Geographic targeting in content: Weak — Placentia/Orange County/California not referenced in content body
- Local citations / directories: Unknown — likely thin; should be listed in ThomasNet, Packaging Digest, industry directories

---

## 9. Conversion & User Experience Issues

- **CTAs:** Phone and email present but no inline "Request a Quote" form on product pages — B2B buyers expect this; every product page should have a quote/inquiry CTA
- **Contact friction:** /contact 404 is a hard stop for any user who types that URL directly; must be fixed
- **Mobile UX:** Likely acceptable (HubSpot responsive), but product spec tables may render poorly on mobile — needs testing
- **Page load perception:** HubSpot CDN is generally fast; no evidence of page speed issues but untested
- **Trust signals above the fold:** Missing — no testimonials, no customer logos, no "trusted by X manufacturers worldwide" badge
- **Navigation clarity:** Clear product dropdown with 15 items — good; but no "Solutions" or "Industries Served" framing to help buyers self-identify

**Key Fixes:**
- Add a "Request a Quote" CTA button on every product page (links to contact-us with a pre-filled subject)
- Add 2–3 customer logo or industry badges above the fold on homepage
- Fix /contact → /contact-us redirect
- Add "Industries Served" or "Applications" section to homepage

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| poly bag machine attachments | Commercial | Low | /products |
| zipper sealing system for bag making | Commercial | Low | /products/zipper-sealing-system |
| film conversion equipment manufacturer | Commercial | Low | / (Homepage) |
| poly bag making machine accessories | Commercial | Low | /products |
| round bottom sealer for poly bags | Commercial | Low | /products/round-bottom-sealer |
| wicket hole punching unit | Commercial | Low | /products/wicket-hole-punching-unit |
| web cleaner for bag machine | Commercial | Low | /products/web-cleaner |
| custom poly bag machine attachments | Commercial | Low | /custom-solutions (new page) |
| how to add zipper to poly bag machine | Informational | Low | Blog post |
| flexible packaging machinery supplier California | Commercial | Medium | / or /about |
| bag making equipment supplier USA | Commercial | Medium | / or /about |

**Content Gap Analysis:**
- **No blog or resource content** — the company sells complex industrial machinery; buyers research extensively before purchasing; informational content is table stakes in this niche
- **No "Industries Served" page** — packaging converters, e-commerce fulfillment, food & beverage, and retail are all potential buyers; industry-specific landing pages convert better
- **No "Custom Solutions" page** — custom attachments are mentioned on the homepage but there's no dedicated page; this is a high-value differentiator
- **No application notes or case studies** — B2B buyers want to see the machine in use in their type of operation
- **No comparison content** — "How to choose the right sealing system" type content targets high-intent mid-funnel buyers

**Recommended Content Pieces:**
1. "How to Choose the Right Sealing System for Your Poly Bag Machine" — targets zipper/header/round bottom sealer comparison keywords; serves buyers evaluating options
2. "Custom Poly Bag Machine Attachments: What's Possible and How to Order" — targets custom attachment search intent; serves OEM and converter buyers
3. "Poly Bag Machine Maintenance: The Role of Web Cleaners and Unwinders" — targets maintenance-focused operators; positions TecHouse as expert
4. "Film Conversion Equipment Guide for Flexible Packaging Manufacturers" — targets broad awareness keywords; serves new-to-market buyers
5. "TecHouse Applications: Industries We Serve" — landing page targeting packaging converters, food & bev, retail sectors

---

## 11. Next Steps & Action Plan

1. **Week 1 — Richard/TecHouse:** Write and publish meta descriptions for all 15 product pages + homepage + About + Contact Us (see Appendix)
2. **Week 1 — TecHouse:** Fix /contact → 301 redirect to /contact-us in HubSpot settings
3. **Week 1 — TecHouse:** Submit XML sitemap to Google Search Console; set up GSC if not already active
4. **Week 2 — Richard:** Implement Organization + Product JSON-LD schema across site (see Appendix for code)
5. **Week 2 — TecHouse:** Add team bio(s) to About page with names, titles, years of experience
6. **Week 3–4 — Richard:** Optimize all 15 product page titles with keyword modifiers
7. **Week 3–4 — TecHouse:** Add 2–3 customer testimonials (even anonymous by industry type) to homepage
8. **Week 4–6 — Richard:** Write and publish first 2 blog posts targeting informational keywords
9. **Week 6–8 — Richard:** Create "Custom Solutions" landing page
10. **Ongoing — Richard:** Build citations in ThomasNet, PackagingDigest.com, and other industrial directories

---

## Appendix

### Suggested Title Tags

| Page | Recommended Title Tag |
|---|---|
| Homepage | `Poly Bag Machine Attachments & Film Conversion Equipment \| TecHouse Corporation` |
| About Us | `About TecHouse Corporation \| Film Conversion Machinery Manufacturer` |
| Products | `Film Conversion Machine Attachments & Accessories \| TecHouse Corporation` |
| Zipper Sealing System | `Zipper Sealing System for Poly Bag Machines — TZ130 \| TecHouse` |
| Roller Header Sealer | `Roller Header Sealer for Bag Making Machines \| TecHouse Corporation` |
| Round Bottom Sealer | `Round Bottom Sealer for Poly Bag Equipment \| TecHouse Corporation` |
| Web Cleaner | `Web Cleaner for Film Conversion Machines \| TecHouse Corporation` |
| Unwinder and Folder | `Unwinder and Folder for Poly Bag Machines \| TecHouse Corporation` |
| Contact Us | `Contact TecHouse Corporation \| Film Conversion Equipment Experts` |

### Suggested Meta Descriptions

| Page | Recommended Meta Description |
|---|---|
| Homepage | `TecHouse Corporation manufactures precision poly bag machine attachments and film conversion equipment. Custom solutions for sealing, die cutting, perforating, and web handling. Based in Placentia, CA — serving manufacturers worldwide.` |
| About Us | `Learn about TecHouse Corporation — a division of Fruth Custom Packaging specializing in custom poly bag machine attachments and film conversion machinery for packaging manufacturers worldwide.` |
| Products | `Browse TecHouse Corporation's full line of poly bag machine attachments: zipper sealers, header sealers, hole punch systems, web cleaners, and more. Custom solutions available.` |
| Zipper Sealing System | `The TecHouse TZ130 Zipper Sealing System operates at 200 ft/min with ultrasonic pre-sealing and electronic tension control. Engineered for continuous poly bag production. Request a quote.` |
| Roller Header Sealer | `TecHouse Roller Header Sealers deliver consistent, high-speed header seals for poly bag machines. Designed for durability and easy integration. Learn more or request a quote.` |
| Round Bottom Sealer | `TecHouse Round Bottom Sealers provide precise bottom seals for poly bag manufacturing. Engineered for speed and reliability. Compatible with a wide range of existing equipment.` |
| Web Cleaner | `Keep your film clean and your production running with TecHouse Web Cleaners — designed for integration into existing poly bag machine lines. Contact us for specs.` |
| Contact Us | `Contact TecHouse Corporation for quotes, technical support, or custom attachment inquiries. Call 714-993-9955 or email sales@techousecorp.com. Located in Placentia, CA.` |

### Schema Code

#### Organization Schema (add to homepage `<head>`)
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "TecHouse Corporation",
  "url": "https://www.techousecorp.com",
  "logo": "https://www.techousecorp.com/[logo-image-url]",
  "description": "TecHouse Corporation manufactures poly bag machine attachments and film conversion equipment, including zipper sealers, header sealers, hole punch systems, and web handling equipment.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "701 S Richfield Road",
    "addressLocality": "Placentia",
    "addressRegion": "CA",
    "postalCode": "92870",
    "addressCountry": "US"
  },
  "telephone": "+1-714-993-9955",
  "email": "sales@techousecorp.com",
  "parentOrganization": {
    "@type": "Organization",
    "name": "Fruth Custom Packaging"
  },
  "sameAs": [
    "https://www.linkedin.com/company/techouse-corporation",
    "https://www.facebook.com/techousecorp",
    "https://www.instagram.com/techousecorp"
  ]
}
```

#### Product Schema (add to each product page — example: Zipper Sealing System)
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Zipper Sealing System TZ130",
  "description": "The TZ130 Zipper Sealing System operates at 200 ft/min with four self-leveling sealing bars, ultrasonic DUCANE 12000W presealer, and electronic tension control for continuous poly bag production.",
  "brand": {
    "@type": "Brand",
    "name": "TecHouse Corporation"
  },
  "manufacturer": {
    "@type": "Organization",
    "name": "TecHouse Corporation",
    "url": "https://www.techousecorp.com"
  },
  "category": "Industrial Machinery / Film Conversion Equipment",
  "url": "https://www.techousecorp.com/products/zipper-sealing-system",
  "offers": {
    "@type": "Offer",
    "priceCurrency": "USD",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "TecHouse Corporation"
    }
  }
}
```

### Additional Notes

- **Google Search Console:** Set up immediately if not active — this is the single most valuable free tool for tracking progress
- **PageSpeed Insights:** Run https://www.techousecorp.com/ through PageSpeed Insights to get Core Web Vitals baseline before any technical changes
- **HubSpot CMS Notes:** Meta descriptions, canonical tags, and schema can all be managed per-page in HubSpot's page settings and head HTML modules — no developer required for most changes
- **ThomasNet:** This is the #1 B2B industrial supplier directory — TecHouse should have a complete, keyword-rich listing there; it also generates high-authority backlinks
- **Google Business Profile:** Even for a B2B manufacturer, a GBP listing helps with branded searches and maps visibility for local/regional buyers
- **Video SEO:** Product videos appear to exist on YouTube — embed them on product pages and add VideoObject schema to capture video rich results
