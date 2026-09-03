# SEO AUDIT REPORT

**Client:** Andy Studer Therapy
**Website:** https://andystuder.com
**Audit Date:** 2026-07-22
**Auditor:** SEO AI Assistant
**Platform:** WordPress (built by Parts of Practice)

---

## 1. Overall SEO Health Score: 66 / 100

**Summary:** Andy Studer Therapy has genuinely strong content and E-E-A-T fundamentals for a solo associate therapist, but is leaking local visibility and search real estate due to a missing/unclaimed Google Business Profile, an NAP inconsistency, and zero structured data — fixing those three things alone would meaningfully move the needle.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Claim/optimize Google Business Profile for both LA and Culver City locations | High | Low |
| 2 | Fix NAP inconsistency (Culver City office lists a different email than everywhere else) | High | Low |
| 3 | Add LocalBusiness + FAQPage schema markup sitewide | High | Medium |
| 4 | Write and confirm unique meta titles/descriptions on every indexed page | Medium | Low |
| 5 | Resume consistent blog cadence to close the Aug 2025–May 2026 content gap | Medium | Medium |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] Fix the Culver City office contact email on the Contact page — it currently shows `andymotoistuder@gmail.com` while every other page and the LA office use `andystudertherapy@gmail.com`. This is a direct NAP-consistency red flag for local SEO and a likely lead-loss point if that inbox isn't monitored.
- [ ] Add or claim Google Business Profile listings for both the Mid-City LA and Culver City addresses — a Google Business search for "Andy Studer Therapy" surfaced no GBP listing, only third-party directories (Psychology Today, ZocDoc, Being Seen, OpenPath). This is the single highest-leverage local SEO fix available.
- [ ] Add an embedded Google Map to the Contact page for both office locations — none is currently present.
- [ ] Add descriptive alt text to all key images (headshots, office photos) referencing "therapist for creatives Los Angeles" and similar contextual terms — WordPress media uploads frequently ship with blank or filename-based alt text by default.
- [ ] Add visible business hours (or "session availability by request") to the Contact page — currently absent, which creates friction for prospective clients.
- [ ] Set unique, keyword-rich meta titles/descriptions site-wide (see Appendix for exact copy) — several pages currently rely on WordPress/theme defaults.

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] Implement FAQPage schema on `/frequently-asked-questions-about-therapy/` — the page already has 9 well-written Q&As ("What can I expect in session?", "How much do sessions cost?", "What is my Good Faith Estimate?", etc.) that are perfect for FAQ rich results but currently ship with no schema.
- [ ] Implement LocalBusiness/ProfessionalService schema (JSON-LD) sitewide, including both office addresses, phone, license number, and hours (see Appendix for ready-to-use code).
- [ ] Build out location-specific landing content — the site currently blends LA and Culver City into general copy; a dedicated "Therapist in Culver City" section/page would capture geo-specific long-tail searches.
- [ ] Resume blog publishing on a consistent cadence (e.g., 2x/month) — there's a 9-month gap between "Multicultural Therapy: Why It Matters" (Aug 13, 2025) and "Creatives and OCD" (May 11, 2026). Consistent publishing signals freshness to Google and builds topical authority.
- [ ] Add internal links from blog posts back to the most relevant service pages (e.g., link "Creatives and OCD" → `/ocd-therapy-for-creatives-in-los-angeles/`) — this reinforces topical relevance and passes authority to money pages.
- [ ] Add a Good Faith Estimate / pricing transparency section as its own indexable content (it's currently just an FAQ answer) — pricing-intent searches are high-value and currently only get a partial answer buried in the FAQ page.
- [ ] Request and display 3–5 additional client-facing testimonials (current three are all from fellow clinicians, which is valuable for referral credibility but doesn't address a prospective client's trust concerns the same way).

---

## 5. Long-Term / Strategic Recommendations

- [ ] Build topic clusters around each specialty (EMDR, Complex PTSD, OCD for creatives, Asian American therapy) with 3–5 supporting blog posts per pillar page, all internally linked — this is the clearest path to ranking for competitive trauma-therapy terms in a saturated LA market.
- [ ] Pursue local PR / guest content angles tied to the "therapy for creatives" niche (entertainment-industry publications, LA-based creative communities, podcasts) to build authoritative backlinks — Andy's entertainment-industry background is a genuine differentiator worth leveraging for link building.
- [ ] Once caseload/license status allows, add video content (a short "meet Andy" intro video) to the homepage and About page — video meaningfully lifts on-page engagement and E-E-A-T signals for therapy sites.
- [ ] As Andy's license progresses toward full LMFT, plan a systematic update pass across all pages/schema to reflect the credential change — build this into a standing content-maintenance checklist now so it isn't missed later.
- [ ] Consider building a resource/directory hub page (expanding the existing `/resources/` page) targeting "therapy resources for creatives Los Angeles" — supports both SEO and genuine client value.

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Present | Present and keyword-relevant on pages checked (e.g., "EMDR Therapy in Los Angeles \| Heal Trauma & Anxiety"); confirm uniqueness across all 20 sitemap URLs |
| Meta Descriptions | Not confirmed | Not detected in on-page render for pages checked — verify and write explicitly rather than relying on auto-generated excerpts |
| H1 Tags | Present | One clear, relevant H1 per page checked |
| Heading Hierarchy (H2–H4) | Clean | Logical H2 structure on service pages; H3/H4 usage not confirmed but no obvious skips |
| Schema / Structured Data | None detected | No LocalBusiness, ProfessionalService, or FAQPage JSON-LD detected on homepage, Contact, or FAQ pages |
| Canonical Tags | Not confirmed | Standard for WordPress/Yoast-style setups but should be verified page-by-page |
| XML Sitemap | Present | `/sitemap.xml` live, 20 URLs, last generated 2026-07-22 |
| Robots.txt | Present | Clean — disallows `/wp-admin/` only, allows `admin-ajax.php`, correctly references sitemap |
| Page Speed | Not tested | Recommend running PageSpeed Insights / GTmetrix directly — not measurable via this audit method; WordPress sites commonly suffer from unoptimized image weight |
| Mobile Friendliness | Not tested | Recommend manual mobile-device check; no responsive issues observed in rendered content |
| HTTPS / SSL | Present | Site fully served over HTTPS |
| Broken Links | No major issues found | Initial guesses at non-sitemap URLs (e.g., `/emdr-therapy`, `/about-andy`, `/faqs`) 404'd, but those are not linked anywhere — real internal links follow correct sitemap slugs |
| Image Alt Text | Not confirmed / likely incomplete | Recommend full audit pass — WordPress default uploads often ship without descriptive alt text |

**Key Technical Issues:**
- No structured data anywhere on the site — highest-leverage technical fix given the strong existing FAQ and credential content.
- Meta descriptions could not be confirmed as present/optimized on any page — treat as missing until verified in WordPress/Yoast (or equivalent) and write explicitly.
- Page speed and Core Web Vitals should be measured directly (PageSpeed Insights) since WordPress therapy-practice sites frequently carry unoptimized hero images that hurt LCP.

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: Not directly confirmed, but consistent site-wide pattern is "[Page Topic] – Andy Studer, AMFT – EMDR & Trauma Therapist"
- Meta Description: Not confirmed — recommend writing explicitly (see Appendix)
- H1: "Therapy for creatives."
- Notes: Strong emotional hook ("You don't have to navigate burnout, self-doubt, or creative block alone") and clear specialty positioning. Homepage effectively summarizes all core services and links out to each.

**/about-andy-studer/:**
- Title: "About Andy - Andy Studer, AMFT – EMDR & Trauma Therapist"
- H1: "About Andy"
- Notes: ~2,200 words — excellent depth. Strong E-E-A-T: license #155068, supervisor name and license (#117143), CalArts BFA + Antioch University LA MA, EMDR/adolescent-family/Suzuki-pedagogy/OCD training all listed. Personal narrative (musician family background) supports the "therapy for creatives" positioning authentically.

**/emdr/:**
- Title: "EMDR Therapy in Los Angeles | Heal Trauma & Anxiety"
- H1: "EMDR Therapy"
- Notes: ~850–900 words, well-structured with clear H2s ("What Is EMDR Therapy?", "How EMDR Supports Healing..."), good keyword coverage (EMDR, trauma, anxiety, Complex PTSD, Asian American therapy cross-link).

**/frequently-asked-questions-about-therapy/:**
- Title: "FAQs for Andy Studer Therapy in Los Angeles - Andy Studer, AMFT – EMDR & Trauma Therapist"
- H1: "FAQs"
- Notes: 9 substantive Q&As covering session logistics, cost, and Good Faith Estimate — strong content, zero schema markup capturing it.

**Content Quality Summary:**
- Word count: Strong across the board — service pages run 850–1,200 words, About page ~2,200 words. Well above typical local-service-page thin-content thresholds.
- Keyword targeting: Strong and specific ("therapy for creatives Los Angeles," "EMDR therapy," "OCD therapy for creatives," "Asian American therapy") — the niche positioning is a genuine differentiator versus generic LA therapist competitors.
- Internal linking: Good — every service page checked links to About, other services, Blog, FAQs, and Contact.
- CTAs: Consistent and clear ("Schedule a Free Consultation" appears multiple times per page); consider testing more varied CTA copy per page intent.

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [x] Author/team bio (detailed About page)
- [x] Credentials (AMFT #155068, supervisor LMFT #117143)
- [x] License numbers (both Andy's and supervisor's, displayed on Contact and About pages)
- [x] Professional affiliations (Psychology Today verified, Being Seen directory)
- [ ] Media mentions
- [x] Client/peer testimonials (3, all from fellow clinicians)
- [ ] Awards
- [x] About page

**E-E-A-T Gaps:**
- No media mentions or press coverage — a strategic-tier link-building/PR play (see Section 5) would help close this.
- All three testimonials are from clinical peers (ACSW, AMFT colleagues), not clients — valuable for professional credibility but doesn't directly answer "will this therapist understand *my* situation" for a prospective client. Recommend adding anonymized/aggregate client experience language if direct client testimonials aren't permitted under confidentiality norms.
- Supervised/associate license status (common and appropriate at this career stage) should be framed proactively and positively in copy — it already is on the About page, which is good practice; keep this consistent as the credential status changes.

**Local SEO:**
- Google Business Profile: **Not found in search** — appears unclaimed or not optimized; this is the single biggest local SEO gap identified.
- NAP Consistency: **Inconsistent** — Culver City office listed with `andymotoistuder@gmail.com` on the Contact page vs. `andystudertherapy@gmail.com` used everywhere else (homepage, About, footer). Phone number (424) 855-1602 is consistent across all pages checked.
- Geographic targeting in content: Strong — "Los Angeles" and "Culver City" both appear naturally in headings and body copy across service pages.
- Local citations / directories: Good foundation — Psychology Today (3 specialty-specific listing variants), ZocDoc, OpenPath Collective, Being Seen all confirmed live and linking back to the site.

---

## 9. Conversion & User Experience Issues

- **CTAs:** Strong and consistent — "Schedule a Free Consultation" repeated appropriately throughout.
- **Contact friction:** Moderate — no embedded map, no visible hours, and the NAP email inconsistency noted above could actively misdirect inquiries to an unmonitored inbox for the Culver City location.
- **Mobile UX:** Not tested directly — recommend manual verification, especially of the contact form and navigation menu.
- **Page load perception:** Not tested — recommend PageSpeed Insights run given WordPress image-weight risk.
- **Trust signals above the fold:** Present — credential/specialty framing appears early in homepage content.
- **Navigation clarity:** Clear — logical top-nav grouping of service pages, About, Blog, FAQs, Contact, Resources.

**Key Fixes:**
- Resolve the Contact page email inconsistency immediately (see Quick Wins).
- Add embedded map + visible hours/availability language to Contact page.
- Confirm mobile contact-form usability directly on a phone.

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| therapist for creatives Los Angeles | Commercial | Medium | / (homepage) |
| EMDR therapist Los Angeles | Commercial | Medium | /emdr/ |
| OCD therapy for creatives | Commercial | Low | /ocd-therapy-for-creatives-in-los-angeles/ |
| Asian American therapist Los Angeles | Commercial | Low–Medium | /asian-american-therapy/ |
| Complex PTSD therapist Culver City | Commercial | Low | /complex-ptsd-attachment-trauma/ |
| therapist for musicians / artists LA | Commercial | Low | /therapy-for-creatives-los-angeles/ |
| Good Faith Estimate therapy California | Informational | Low | New standalone page (currently FAQ-only) |
| creative burnout therapy | Informational | Low | /therapy-for-creatives-los-angeles/ (blog support) |

**Content Gap Analysis:**
- No dedicated Culver City-specific landing content — everything currently reads as LA-general with Culver City mentioned in passing.
- Pricing/insurance/Good Faith Estimate information is buried in FAQ only — this is a common high-intent search for therapy clients and deserves its own indexable, linkable page.
- Blog has strong niche topics (fawn response, dissociation, multicultural therapy, OCD in creatives) but publishing is inconsistent — the content strategy is right, the cadence isn't.

**Recommended Content Pieces:**
1. "EMDR for Creative Block: How Trauma Therapy Unlocks Artistic Work" — targets EMDR + creatives intersection, serves informational/commercial intent
2. "What Does Therapy Cost in LA? Understanding Your Good Faith Estimate" — targets pricing-intent searches, currently underserved
3. "Finding a Therapist in Culver City vs. Mid-City LA: What to Know" — targets geo-specific long-tail, supports both office locations
4. "Perfectionism, ADHD, and the Creative Brain" — targets neurodivergence/ADHD + creatives niche, an underused specialty already listed in services but with no dedicated page or blog coverage

---

## 11. Next Steps & Action Plan

1. **Week 1 — Andy/Practice Manager:** Fix Contact page email inconsistency; claim/verify Google Business Profile for both offices.
2. **Week 1 — Parts of Practice:** Add embedded map and business hours to Contact page; audit and set unique meta titles/descriptions across all 20 sitemap URLs.
3. **Weeks 2–3 — Parts of Practice:** Implement LocalBusiness + FAQPage JSON-LD schema sitewide.
4. **Weeks 3–4 — Andy/Content:** Publish 2 new blog posts from the recommended content list; resume regular cadence going forward.
5. **Weeks 5–8 — Parts of Practice:** Build out Culver City-specific content and a standalone Good Faith Estimate/pricing page; begin topic-cluster internal linking around EMDR and Complex PTSD pillar pages.

---

## Appendix

### Suggested Title Tags

| Page | Recommended Title Tag |
|---|---|
| Homepage | `Therapy for Creatives in Los Angeles & Culver City | Andy Studer, AMFT` |
| /emdr/ | `EMDR Therapy in Los Angeles | Trauma & Anxiety Treatment | Andy Studer` |
| /ocd-therapy-for-creatives-in-los-angeles/ | `OCD Therapy for Creatives in Los Angeles | Andy Studer, AMFT` |
| /asian-american-therapy/ | `Asian American Therapy in Los Angeles | Culturally-Responsive Care` |
| /contact/ | `Contact Andy Studer Therapy | LA & Culver City Offices` |

### Suggested Meta Descriptions

| Page | Recommended Meta Description |
|---|---|
| Homepage | `Compassionate therapy for creatives, trauma, and identity in Los Angeles and Culver City. EMDR, Complex PTSD, and multicultural therapy with Andy Studer, AMFT. Schedule a free consultation.` |
| /emdr/ | `EMDR therapy in Los Angeles for trauma, anxiety, and complex PTSD. Evidence-based, trauma-informed care from Andy Studer, AMFT #155068. Free consultation available.` |
| /ocd-therapy-for-creatives-in-los-angeles/ | `Specialized OCD therapy for creative professionals in Los Angeles. Andy Studer, AMFT, helps artists and creatives manage intrusive thoughts and perfectionism. Book a free consultation.` |
| /contact/ | `Reach Andy Studer Therapy in Mid-City Los Angeles or Culver City, CA. Call (424) 855-1602 or schedule a free consultation online today.` |

### Schema Code

**LocalBusiness / ProfessionalService (recommend embedding on homepage and Contact page):**

```json
{
  "@context": "https://schema.org",
  "@type": "MedicalBusiness",
  "name": "Andy Studer Therapy",
  "image": "https://andystuder.com/wp-content/uploads/andy-studer-headshot.jpg",
  "url": "https://andystuder.com",
  "telephone": "+1-424-855-1602",
  "email": "andystudertherapy@gmail.com",
  "priceRange": "$$",
  "address": [
    {
      "@type": "PostalAddress",
      "streetAddress": "5478 Wilshire Blvd, Ste. 215",
      "addressLocality": "Los Angeles",
      "addressRegion": "CA",
      "postalCode": "90036",
      "addressCountry": "US"
    },
    {
      "@type": "PostalAddress",
      "streetAddress": "10811 Washington Blvd, Suite 280c",
      "addressLocality": "Culver City",
      "addressRegion": "CA",
      "postalCode": "90232",
      "addressCountry": "US"
    }
  ],
  "medicalSpecialty": "Psychiatric",
  "areaServed": ["Los Angeles, CA", "Culver City, CA", "California (Telehealth)"],
  "sameAs": [
    "https://www.psychologytoday.com/us/therapists/andy-studer-culver-city-ca/1559065",
    "https://beingseen.org/therapist/andystuder/"
  ]
}
```

**FAQPage (recommend embedding on /frequently-asked-questions-about-therapy/):**

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What can I expect in session?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sessions are a collaborative, compassionate space where you and Andy explore your lived experience together, tailored to your specific goals and needs."
      }
    },
    {
      "@type": "Question",
      "name": "How much do sessions cost?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Session fees vary; contact the practice directly for current rates and a Good Faith Estimate."
      }
    },
    {
      "@type": "Question",
      "name": "What is my Good Faith Estimate?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Under federal law, clients have the right to receive a Good Faith Estimate of expected charges for therapy services prior to starting care."
      }
    }
  ]
}
```

*(Note: fill remaining FAQ entries in with the actual on-page answer copy before implementation — the three above are representative placeholders drawn from question titles only.)*

### Additional Notes

- Recommended free tools for follow-up: Google Search Console (verify indexing of all 20 sitemap URLs), Google PageSpeed Insights (Core Web Vitals), Google Business Profile Manager (claim/verify listings), Screaming Frog (crawl for meta description/canonical/alt-text audit at scale).
- Platform note: site is WordPress, built by Parts of Practice — schema and meta description work can likely be implemented via an SEO plugin (Yoast/RankMath) if not already installed, minimizing custom development.
- This audit was conducted via live site fetch on 2026-07-22 and did not include Google Search Console, Google Analytics, or backlink-profile data — recommend a follow-up technical audit once account access is available for page speed, indexing status, and backlink analysis.
