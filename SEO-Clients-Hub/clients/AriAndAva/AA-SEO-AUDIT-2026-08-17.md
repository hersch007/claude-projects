# SEO AUDIT REPORT

**Client:** Ari + Ava
**Website:** https://www.shopariandava.com
**Audit Date:** 2026-08-17
**Auditor:** SEO AI Assistant (GroupRB)
**Platform:** Shopify

---

## 1. Overall SEO Health Score: 52 / 100

**Summary:** Ari + Ava has a solid Shopify foundation and strong local presence, but is losing significant organic traffic potential due to a missing About page (404), zero blog content, absent meta descriptions, and no structured data — all fixable with focused effort over 4–6 weeks.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Fix the 404 About/About-Us page and add founder story | High | Low |
| 2 | Write and publish meta descriptions for all key pages | High | Low |
| 3 | Launch blog with 4–6 plus-size fashion articles targeting search intent | High | Medium |
| 4 | Add LocalBusiness + BreadcrumbList schema to homepage and collection pages | High | Medium |
| 5 | Consolidate overlapping sale/clearance collections to reduce duplicate content | Medium | Medium |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] Create `/pages/about-us` in Shopify with founder story, brand mission, and inclusive sizing commitment — currently a 404
- [ ] Add meta descriptions to: Homepage, top 5 collection pages (Denim, Dresses, New Arrivals, Best Sellers, Tops)
- [ ] Add `rel="canonical"` to all sale/clearance duplicate collection pages pointing to primary collection
- [ ] Update the homepage `<title>` to include a secondary keyword: "Nashville Plus Size Women's Boutique | Ari + Ava | Sizes 0XL–5XL"
- [ ] Add alt text to all homepage hero and product imagery (likely missing or auto-generated from filenames)

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] Publish 4–6 blog articles targeting high-intent plus-size fashion searches (see Section 10 for titles)
- [ ] Add LocalBusiness JSON-LD schema to homepage (see Appendix for code)
- [ ] Create a dedicated "Size Guide" page and link it from every collection and product page
- [ ] Build out H1 and H2 tags on collection pages — Shopify auto-generates generic headings
- [ ] Add customer review schema (Product/AggregateRating) to top-selling product pages
- [ ] Set up Google Business Profile if not already claimed; ensure NAP matches site exactly
- [ ] Add internal linking from collection pages back to the About page and blog once live

---

## 5. Long-Term / Strategic Recommendations

- [ ] Develop a "Style Edit" blog series — monthly content around seasonal dressing for plus-size women, targeting low-competition informational keywords
- [ ] Build topical authority around "Nashville boutique" + "plus size" keyword cluster through blog + GBP posts
- [ ] Pursue backlinks from Nashville lifestyle publications, plus-size fashion influencers, and style bloggers
- [ ] Create a gift guide page (holiday, birthday, bridal shower) to capture seasonal commercial intent
- [ ] Explore Shopify SEO apps (e.g., Plug In SEO, SEO Manager) to automate meta tag generation across products

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Partial | Homepage has keyword-rich title; collection pages likely auto-generated |
| Meta Descriptions | Missing | None detected on homepage or collections |
| H1 Tags | Partial | Homepage H1 is brand name only; collection H1s likely generic |
| Heading Hierarchy (H2–H4) | Issues | Promotional H2s ("Shop Fresh New Arrivals") — not keyword-targeted |
| Schema / Structured Data | None | No LocalBusiness, Product, BreadcrumbList, or Organization schema detected |
| Canonical Tags | Unknown | Shopify adds these by default but sale collection duplication is a risk |
| XML Sitemap | Present | Shopify auto-generates at /sitemap.xml |
| Robots.txt | Present | Shopify auto-generates at /robots.txt |
| Page Speed | Unknown | Shopify-hosted; likely moderate — image optimization critical for product-heavy pages |
| Mobile Friendliness | Good | Shopify themes are responsive by default |
| HTTPS / SSL | Present | Site loads on HTTPS |
| Broken Links | Yes | /about and /pages/about-us both return 404 |
| Image Alt Text | Partial | Product images likely have minimal or auto-generated alt text |

**Key Technical Issues:**
- About page 404 — linked from navigation but returns 404; creates broken user and crawler path
- No meta descriptions — Google will auto-generate these, often pulling poor copy
- No structured data — missing LocalBusiness schema despite clear physical store presence
- Sale collection duplication — multiple overlapping clearance collections risk keyword cannibalization

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: `Nashville Plus Size Women's Boutique Clothing – Ari + Ava`
- Meta Description: Not set (missing)
- H1: "Ari + Ava" (brand name — not keyword-optimized)
- Notes: Title is strong. H1 should incorporate primary keyword. Promotional H2s are merchandising-driven, not SEO-driven. Newsletter CTA and free shipping threshold ($99) are above the fold — good for conversion.

**Collections — Denim:**
- Title: Likely auto-generated by Shopify
- Meta Description: Missing
- Notes: "Judy Blue jeans" is a branded keyword with search demand; capitalize on this with a dedicated brand landing page

**Collections — Dresses:**
- Title: Likely auto-generated
- Meta Description: Missing
- Notes: Subcategories (maxi, midi, mini) should each have optimized titles and descriptions

**Blog/News:**
- Status: Section exists but has zero published posts — missed organic traffic opportunity
- Notes: This is the single biggest content gap; even 4 articles would meaningfully expand keyword footprint

**Content Quality Summary:**
- Word count: Low — product pages are sparse; no editorial content exists
- Keyword targeting: Weak — homepage title is good, but collection pages are not optimized
- Internal linking: Weak — no blog to link from, About page is broken
- CTAs: Strong — "Shop Now," free shipping threshold, and newsletter discount are clear and well-placed

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [x] Physical store address + hours (Nashville, TN — 203 N 11th St)
- [x] Customer testimonials / "What Our Customers Say" section on homepage
- [x] Founder vision statement ("created to redefine boutique shopping for the plus-size community")
- [ ] Founder/team bios — not present (About page is 404)
- [ ] Media mentions or press coverage
- [ ] Professional credentials or affiliations
- [ ] Awards or recognitions

**E-E-A-T Gaps:**
- About page (404) removes the primary trust-building page from the site — fix immediately
- No founder bio or photos; for a brand built on personal mission, this is a missed trust signal
- No press or media mentions surfaced on-site

**Local SEO:**
- Google Business Profile: Unknown — not confirmed; should be verified and fully optimized
- NAP Consistency: Address on site is "203 N 11th St, Nashville, TN 37206" — ensure this matches GBP exactly
- Geographic targeting in content: Moderate — "Nashville" appears in title tag and homepage but not in collection page copy
- Local citations / directories: Unknown — recommend audit of Yelp, Foursquare, Nashville-specific directories

---

## 9. Conversion & User Experience Issues

- **CTAs:** Strong — "Shop Now" links are prominent, free shipping threshold is visible, newsletter 10% discount incentive is clear
- **Contact friction:** No contact page confirmed; store hours and address are on homepage which helps
- **Mobile UX:** Good — Shopify themes are inherently mobile-responsive
- **Page load perception:** Likely moderate; heavy image usage on product/collection pages could slow mobile load
- **Trust signals above the fold:** Present — customer reviews section, physical address visible
- **Navigation clarity:** Clear — organized by clothing type and color; sale collections could be consolidated

**Key Fixes:**
- Fix About page 404 — this is likely linked in the nav and creates a dead end for interested shoppers
- Add a Contact page or at minimum a prominent contact section with email + phone
- Consolidate the 5+ overlapping sale collections into one primary "Sale" collection

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| plus size boutique Nashville | Commercial | Low | Homepage |
| plus size denim Nashville | Commercial | Low | /collections/denim |
| Judy Blue jeans plus size | Commercial | Medium | /collections/judy-blue |
| plus size maxi dresses | Commercial | Medium | /collections/dresses |
| plus size summer outfits 2026 | Informational | Low | Blog |
| how to style Judy Blue jeans | Informational | Low | Blog |
| plus size Nashville boutique | Commercial | Low | Homepage + GBP |
| plus size casual outfits | Informational | Low | Blog |
| size inclusive women's clothing Nashville | Commercial | Low | Homepage |
| zodiac fragrance gift | Commercial | Low | /collections/fragrances |

**Content Gap Analysis:**
- Zero blog content — entire organic traffic footprint is product/collection pages only
- No Size Guide page — critical trust and conversion tool for a plus-size clothing brand
- No Style Guide or lookbook content — missed engagement and internal linking opportunity
- No dedicated Judy Blue brand page — branded keyword demand exists and is unaddressed
- No gift guide — seasonal commercial intent is completely untapped

**Recommended Content Pieces:**
1. "How to Style Judy Blue Jeans for Every Body" — targets branded + styling keywords, serves purchase-intent audience
2. "10 Plus-Size Summer Outfits We're Loving Right Now" — targets seasonal informational intent, links to active collections
3. "The Ari + Ava Size Guide: Finding Your Perfect Fit in Sizes 0XL–5XL" — targets trust + conversion, reduces returns
4. "Nashville's Best Plus-Size Boutique: What to Expect When You Visit Ari + Ava" — local SEO content, GBP alignment
5. "Zodiac Scents: Finding Your Signature Fragrance" — targets fragrance gift searchers, highlights unique product
6. "Best Dresses for Curvy Women: Our Top Picks for 2026" — targets high-volume commercial informational intent

---

## 11. Next Steps & Action Plan

1. **Week 1 — Richard/Client:** Create /pages/about-us in Shopify with founder story and brand mission
2. **Week 1 — Richard:** Write and add meta descriptions to homepage + top 5 collection pages
3. **Week 1 — Richard:** Fix or redirect /about 404 to /pages/about-us
4. **Week 2 — GroupRB:** Add LocalBusiness JSON-LD schema to homepage (see Appendix)
5. **Week 2 — GroupRB:** Add canonical tags to all sale/clearance collection pages
6. **Week 3–4 — GroupRB:** Publish first 2 blog articles (Size Guide + Judy Blue styling post)
7. **Week 5–6 — GroupRB:** Publish remaining 4 blog articles; begin GBP optimization
8. **Ongoing — GroupRB:** Monthly blog post; track keyword rankings for target terms

---

## Appendix

### Suggested Title Tags

| Page | Recommended Title Tag |
|---|---|
| Homepage | `Nashville Plus Size Women's Boutique | Sizes 0XL–5XL | Ari + Ava` |
| Denim Collection | `Plus Size Denim & Judy Blue Jeans | Ari + Ava Nashville` |
| Dresses Collection | `Plus Size Dresses | Maxi, Midi & Mini Styles | Ari + Ava` |
| New Arrivals | `New Plus Size Arrivals | Women's Boutique | Ari + Ava Nashville` |
| About Us | `About Ari + Ava | Plus Size Women's Boutique in Nashville, TN` |
| Blog/News | `Plus Size Style Tips & Guides | Ari + Ava Blog` |

### Suggested Meta Descriptions

| Page | Recommended Meta Description |
|---|---|
| Homepage | `Ari + Ava is Nashville's premier plus-size women's boutique. Shop sizes 0XL–5XL in denim, dresses, tops, and more. Free shipping on orders over $99.` |
| Denim Collection | `Shop plus-size denim and Judy Blue jeans at Ari + Ava. Sizes 0XL–5XL. Find your perfect fit at our Nashville boutique or shop online.` |
| Dresses Collection | `Discover plus-size dresses in maxi, midi, and mini styles at Ari + Ava. Sizes 0XL–5XL. Shop new arrivals from our Nashville boutique.` |
| About Us | `Learn the story behind Ari + Ava — Nashville's inclusive plus-size boutique created to celebrate every body in sizes 0XL–5XL.` |

### Schema Code

```json
{
  "@context": "https://schema.org",
  "@type": "ClothingStore",
  "name": "Ari + Ava",
  "url": "https://www.shopariandava.com",
  "logo": "https://www.shopariandava.com/[logo-image-url]",
  "description": "Nashville's premier plus-size women's boutique offering sizes 0XL–5XL in denim, dresses, tops, outerwear, accessories, and fragrances.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "203 N 11th St",
    "addressLocality": "Nashville",
    "addressRegion": "TN",
    "postalCode": "37206",
    "addressCountry": "US"
  },
  "telephone": "[INSERT PHONE]",
  "email": "[INSERT EMAIL]",
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Sunday", "Monday"],
      "opens": "12:00",
      "closes": "18:00"
    },
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Wednesday", "Thursday"],
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
  "priceRange": "$$",
  "currenciesAccepted": "USD",
  "paymentAccepted": "Credit Card, Shop Pay",
  "sameAs": [
    "[INSERT INSTAGRAM URL]",
    "[INSERT FACEBOOK URL]",
    "[INSERT TIKTOK URL IF APPLICABLE]"
  ]
}
```

### Additional Notes
- **Platform:** Shopify — use the Shopify theme editor to add schema via a custom liquid section, or a Shopify SEO app
- **Tuesday closed:** Store is closed Tuesdays — ensure GBP hours reflect this exactly
- **Fragrance line:** The zodiac-inspired fragrance offering is unique and potentially high-margin; worth building dedicated collection SEO
- **Tools recommended:** Google Search Console (verify ownership), Google Business Profile Manager, Plug In SEO or SEO Manager (Shopify apps)
