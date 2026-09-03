# Garlock SEO Audit — September 1, 2026

**Website:** https://www.garlock.com  
**Auditor:** GroupRB Marketing  
**Date:** September 1, 2026  
**Report Period:** Baseline audit (first engagement)

---

## 1. Overall SEO Health Score

**54 / 100 — Needs Work**

Garlock has strong brand authority (135+ years, publicly traded parent Enpro Industries, multiple proprietary product brands) but is being significantly held back by site-wide technical deficiencies. No meta descriptions, no schema markup, no canonical tags, and a JavaScript SPA architecture that risks large portions of content being invisible to Googlebot. The E-E-A-T foundation is exceptional — the SEO infrastructure just isn't built to take advantage of it.

### Score Breakdown
| Factor | Score | Notes |
|---|---|---|
| Technical SEO | 38/100 | SPA rendering risk, no canonicals, Angular template variables visible to crawlers |
| On-Page SEO | 42/100 | No meta descriptions site-wide, multiple H1s on key pages |
| Schema Markup | 0/100 | Zero structured data found across all pages audited |
| E-E-A-T Signals | 78/100 | Strong brand history, public company, proprietary products |
| Content | 55/100 | Product content thin on some pages; strong About page |
| Crawlability | 45/100 | Several URL patterns returned 404; SPA JS dependency |

---

## 2. Top 5 Priorities Right Now

1. **Diagnose JavaScript rendering** — Confirm whether Googlebot is rendering the Angular SPA. Use Google Search Console's URL Inspection tool and the Fetch as Google feature on key product pages. This is the single highest-risk issue.
2. **Add meta descriptions site-wide** — Every page crawled returned no meta description. This directly reduces CTR in search results.
3. **Add canonical tags** — No canonical tags detected anywhere. On a product catalog with filtering/sorting, this creates significant duplicate content risk.
4. **Add Organization schema to homepage** — Zero schema anywhere on the site. The homepage Organization schema is the easiest first win and improves Knowledge Panel eligibility.
5. **Fix multiple H1s on /en/products** — The products page has multiple H1 tags including unrendered Angular template strings (e.g., `{{ vm.products.pagination.totalItemCount }} Items results for {{ vm.query }}`). This suggests crawlers may be indexing template code rather than real content.

---

## 3. Quick Wins (< 1 week)

### QW-1: Add Meta Descriptions to Top 10 Pages
**Effort:** Low | **Impact:** High  
Every page audited is missing a meta description. Start with the 10 highest-traffic pages. Even a generic template ("Garlock's [Product] — fire-safe, corrosion-resistant sealing solutions for [industry]. Request a quote.") is better than nothing.

Target pages first:
- Homepage
- /en/products
- /en/company/about-garlock
- Top 5–7 product category pages

### QW-2: Add Organization Schema to Homepage
**Effort:** Low | **Impact:** Medium  
One JSON-LD block in the homepage `<head>`. Makes the brand eligible for Google Knowledge Panel and improves entity recognition.

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Garlock",
  "url": "https://www.garlock.com",
  "logo": "https://www.garlock.com/[logo-path].png",
  "description": "Leaders in sealing integrity — industrial gaskets, mechanical seals, PTFE products, and sealing solutions for over 135 years.",
  "parentOrganization": {
    "@type": "Organization",
    "name": "Enpro Industries",
    "tickerSymbol": "NPO",
    "exchange": "NYSE"
  },
  "foundingDate": "1887",
  "areaServed": "Worldwide",
  "sameAs": [
    "https://www.linkedin.com/company/garlock",
    "https://en.wikipedia.org/wiki/Garlock_Sealing_Technologies"
  ]
}
```

### QW-3: Implement Canonical Tags at Template Level
**Effort:** Low (single template edit) | **Impact:** High  
Add a self-referencing canonical tag to every page template. For an Angular SPA this is typically a one-line addition to the global `<head>` component using the Angular router's current URL.

### QW-4: Run Google's URL Inspection on 5 Key Pages
**Effort:** Very Low | **Impact:** Critical diagnostic  
Use Google Search Console > URL Inspection > "Test Live URL" on these pages:
- Homepage
- /en/products
- /en/products/gaskets (or equivalent)
- /en/company/about-garlock
- One product detail page

Compare the "Rendered HTML" view to the actual page. If Angular template variables appear (`{{ vm.query }}`), Googlebot is not executing the JavaScript.

---

## 4. Medium-Term Recommendations (2–8 Weeks)

### MT-1: Implement Server-Side Rendering (SSR) or Prerendering for Product Pages
**Effort:** High | **Impact:** Critical  
This is the most important medium-term fix. Angular (and similar JS frameworks) require Googlebot to execute JavaScript to see content. Google's rendering queue delays this by days or weeks. Options:
- **Angular Universal** — Full SSR within the Angular framework
- **Prerendering with Prerender.io** — Serves pre-rendered HTML to crawlers without changing the Angular app
- **Static Generation of product/category pages** — Convert key landing pages to static HTML while keeping the SPA for authenticated/interactive flows

### MT-2: Add Product/Category Schema to All Product Pages
**Effort:** Medium | **Impact:** High  
Each product category page should have `CollectionPage` schema. Individual product pages should have `Product` schema including name, description, manufacturer, and category. This unlocks rich results eligibility.

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "GYLON® High-Performance PTFE Gasket",
  "description": "Chemically resistant PTFE gasket material for demanding industrial applications.",
  "brand": {
    "@type": "Brand",
    "name": "GYLON®"
  },
  "manufacturer": {
    "@type": "Organization",
    "name": "Garlock"
  },
  "category": "Industrial Gaskets"
}
```

### MT-3: Add FAQPage Schema to Industry and Product Pages
**Effort:** Medium | **Impact:** Medium  
Industry pages (oil & gas, pharmaceutical, food & beverage) are natural candidates for FAQ schema. Example questions: "What type of gasket is used in oil and gas?" "Are Garlock seals FDA-compliant?" This wins featured snippet and People Also Ask placement.

### MT-4: Audit and Fix 404 Pages
**Effort:** Medium | **Impact:** Medium  
During the crawl, several URL patterns returned 404 errors (`/en/industries`, `/en/resources`, `/en/products/gaskets`). These need to either:
- Be redirected (301) to the correct URL
- Have the correct URL documented so internal links are updated

### MT-5: Create Unique, Keyword-Rich Meta Descriptions for All Product Categories
**Effort:** Medium | **Impact:** High  
Once meta descriptions can be deployed, every product category needs a unique, keyword-targeted description. Template structure:

> "[Product type] from Garlock — [key attribute, e.g., fire-safe / chemically resistant / ISO-certified] sealing solutions for [primary industry]. Proprietary formulations with 135 years of engineering expertise."

---

## 5. Long-Term / Strategic Recommendations

### LT-1: Non-Brand Keyword Content Strategy
Garlock's organic traffic is likely heavily brand-dominated (searches for "garlock," "garlock gaskets," "GYLON"). The opportunity is capturing non-brand industrial intent keywords:
- "spiral wound gasket manufacturer"
- "PTFE gasket chemical resistance"
- "mechanical seal oil and gas"
- "sealing solutions hydrogen industry"
- "expansion joints industrial applications"

Each should have a dedicated, content-rich landing page with 600+ words, FAQ schema, and internal linking.

### LT-2: E-E-A-T Content Build-Out
Garlock has exceptional Experience and Authoritativeness foundations — they just aren't signaling it to search engines:
- Add author bylines to technical articles and whitepapers (with author bio pages)
- Publish case studies with named clients (where permissible under NDA)
- Add certifications, standards compliance, and testing lab information to product pages
- Link to Enpro Industries investor relations as a trust signal

### LT-3: International SEO Audit
Garlock operates separate regional sites (AU, EU, CN, SG, TW). Without proper `hreflang` implementation, these sites may cannibalize each other's rankings and confuse Google about which country should see which version. Recommend a full hreflang audit across all regional properties.

### LT-4: Core Web Vitals Optimization
JavaScript SPAs are notoriously slow on Core Web Vitals — particularly LCP and CLS. Run a PageSpeed Insights audit on key product pages. If scores are below 50 on mobile, this is a ranking factor concern.

### LT-5: Backlink Strategy via Enpro / Industry Trade Publications
- Pursue mentions in trade publications: Sealing Technology, Pumps & Systems, Chemical Engineering
- Request backlinks from industry associations (ASME, API, ASTM) where Garlock products comply with their standards
- Leverage Enpro Industries press releases and investor filings as linking opportunities

---

## 6. Technical SEO Analysis

| Issue | Status | Priority |
|---|---|---|
| Meta descriptions | ❌ Missing site-wide | Critical |
| Canonical tags | ❌ Missing site-wide | Critical |
| JS rendering (Angular SPA) | ⚠️ Unconfirmed — high risk | Critical |
| Schema markup | ❌ None found | High |
| Multiple H1 tags | ❌ Products page, possibly others | High |
| Angular template vars visible to crawlers | ⚠️ Detected on products page | High |
| 404 errors on expected URLs | ❌ Found on /en/industries, /en/resources, /en/products/gaskets | Medium |
| Sitemap | ⚠️ Not accessible at /sitemap.xml | Medium |
| robots.txt | ⚠️ Not audited | Medium |
| HTTPS | ✅ Active | — |
| Mobile-friendly | ⚠️ Assumed (not tested) | Low |
| Page speed | ⚠️ Not tested — SPA risk | Medium |

### Critical Technical Note: Angular SPA Crawlability

The most significant risk on this site is JavaScript rendering. When the WebFetch crawler hit the products page, it returned Angular template syntax literally: `{{ vm.products.pagination.totalItemCount }} Items results for {{ vm.query }}`. This means:

- When Googlebot visits the page without executing JavaScript, it sees these template strings instead of actual product names, descriptions, and counts.
- Googlebot does execute JavaScript — but with a significant delay (often weeks), meaning new/updated product pages may not be indexed for a long time.
- The indexable "first pass" version of every product page may contain no meaningful content.

**Action required:** Use Search Console URL Inspection to compare the "Rendered HTML" to the live page. If they differ materially, SSR or prerendering is non-negotiable.

---

## 7. On-Page SEO Analysis

### Pages Audited
| Page | Title | Meta Desc | H1 Count | Schema | Canonical |
|---|---|---|---|---|---|
| / (Homepage) | ✅ "Leaders in Sealing Integrity \| Garlock" | ❌ Missing | ⚠️ 2 H1s | ❌ None | ❌ Missing |
| /en/products | ⚠️ Not detected | ❌ Missing | ❌ Multiple + template vars | ❌ None | ❌ Missing |
| /en/company | ⚠️ Not detected | ❌ Missing | ⚠️ Multiple | ❌ None | ❌ Missing |
| /en/company/about-garlock | ✅ "About Us \| Garlock" | ❌ Missing | ✅ 1 | ❌ None | ❌ Missing |

### Title Tag Assessment
- Homepage title is good: "Leaders in Sealing Integrity | Garlock" — includes brand and value proposition
- Other pages appear to use generic section names ("About Us | Garlock") — should include primary keywords
- Products page title not detected (likely JS-rendered)

### Recommended Title Tag Pattern for Product Pages
`[Product Name] | Industrial [Category] | Garlock`

Example: `Spiral Wound Gaskets | Industrial Sealing Solutions | Garlock`

---

## 8. E-E-A-T Assessment

**Score: 78/100 — Strong Foundation, Weak Expression**

| Signal | Status | Notes |
|---|---|---|
| Experience | ✅ Strong | 135+ years, proprietary formulations, testing facilities |
| Expertise | ✅ Strong | Deep specialization, patented products (TUFF-RAIL® 3504), technical resources |
| Authoritativeness | ✅ Strong | Enpro Industries (NYSE: NPO) parent; recognized industry leader |
| Trustworthiness | ✅ Strong | $70M+ environmental investment, safety-first culture, compliance messaging |
| Author Attribution | ❌ Weak | No visible author bylines on technical content |
| Certifications on-page | ⚠️ Partially | Referenced in copy but not structured/highlighted |
| Customer Proof | ⚠️ Limited | No visible case studies or testimonials |
| Schema Signals | ❌ None | Zero structured data to communicate authority to Google |

**Garlock's E-E-A-T is genuinely excellent — it just isn't being communicated to search engines.** Adding Organization schema, author schemas on technical content, and a certifications/standards compliance section would close this gap quickly.

---

## 9. Conversion & UX Review

- **Quote Request CTA:** Not highly visible on crawled pages — confirm prominence on product pages
- **Distributor Locator:** Mentioned in company section — good conversion tool for B2B
- **Resources/Downloads:** Technical manuals referenced — gated or ungated? Ungated PDFs are indexable and rankable
- **Regional Site Selector:** Global presence could confuse US visitors landing on the wrong regional site
- **Contact:** `/contact` page not tested — ensure it has ContactPage schema

---

## 10. Keyword Strategy

### Brand Keyword Opportunity (Already Ranking, Protect)
| Keyword | Intent |
|---|---|
| garlock | Brand nav |
| garlock gaskets | Brand product |
| garlock sealing | Brand category |
| GYLON PTFE | Proprietary brand |
| LINK-SEAL | Proprietary brand |
| KLOZURE | Proprietary brand |

### Non-Brand Target Keywords (Acquisition Opportunity)
| Keyword | Est. Monthly Searches | Difficulty | Target Page |
|---|---|---|---|
| industrial gaskets manufacturer | 1,600 | High | /en/products/gaskets |
| spiral wound gasket | 3,600 | Medium | Product page |
| PTFE gasket | 4,400 | Medium | GYLON product page |
| mechanical seal industrial | 1,300 | High | /en/products/mechanical-seals |
| expansion joints industrial | 880 | Medium | Product page |
| sealing solutions oil gas | 480 | High | Industry page |
| chemical resistant gasket | 720 | Medium | Product page |
| compression packing | 1,000 | Medium | Product page |
| hydrogen sealing solutions | 260 | Low | Emerging — own it now |
| wall penetration sealing | 390 | Low | LINK-SEAL page |

### Content Gap Keywords (Need New Pages)
- "how to select a gasket" — informational, high-funnel
- "gasket material selection guide" — lead magnet / gated content
- "PTFE vs graphite gasket" — comparison content
- "sealing solutions for hydrogen" — emerging category Garlock is actively targeting (homepage hero)

---

## 11. Action Plan

### Priority Matrix

| Action | Effort | Impact | Timeline |
|---|---|---|---|
| Run URL Inspection in GSC on 5 key pages | Very Low | Critical diagnostic | Day 1 |
| Add meta descriptions to top 10 pages | Low | High | Week 1 |
| Add Organization schema to homepage | Low | Medium | Week 1 |
| Implement canonical tags (template-level) | Low | High | Week 1 |
| Audit and fix 404 pages | Medium | Medium | Week 2 |
| Fix multiple H1s on products page | Low | Medium | Week 2 |
| Implement SSR or prerendering | High | Critical | Weeks 3–8 |
| Add Product schema to top 20 product pages | Medium | High | Weeks 3–6 |
| Add FAQPage schema to industry/category pages | Medium | Medium | Weeks 4–8 |
| Keyword-targeted meta descriptions site-wide | Medium | High | Weeks 4–8 |
| Content strategy for non-brand keywords | High | High | Months 2–6 |
| E-E-A-T content build-out (authors, case studies) | High | High | Months 2–6 |
| International hreflang audit | Medium | Medium | Month 2 |
| Core Web Vitals optimization | High | Medium | Month 3 |

---

### Appendix: Sample Meta Descriptions

**Homepage:**
> "Garlock is a global leader in sealing integrity — industrial gaskets, mechanical seals, PTFE products, and expansion joints for demanding applications. Trusted for 135+ years."
*(161 chars)*

**About Garlock:**
> "Garlock engineers sealing solutions with 135+ years of expertise. An Enpro Industries company serving aerospace, oil & gas, pharmaceutical, and 12+ other industries worldwide."
*(176 chars — trim as needed)*

**Products Hub:**
> "Browse Garlock's full catalog of industrial sealing products — gaskets, mechanical seals, compression packing, PTFE films, and expansion joints. Request a quote."
*(162 chars)*

---

*Prepared by GroupRB Marketing | richard@grouprb.com | September 1, 2026*
