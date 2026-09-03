# SEO AUDIT REPORT

**Client:** Lavender Healing Collective
**Website:** https://www.lavenderhealingcollective.com
**Audit Date:** 2026-05-26
**Auditor:** Claude (SEO Strategist)
**Data Sources:** Live page crawl (6 key pages), Ubersuggest Site Audit, Ubersuggest Backlinks Overview

---

## 1. Overall SEO Health Score: 36 / 100

**Summary:** The site has a genuinely powerful niche and has earned some high-authority natural backlinks, but it is functionally invisible in search — only 22 monthly organic visits, 28 ranking keywords, 17 of 26 pages missing H1 tags, critically slow mobile performance, and a Domain Authority of just 12. The infrastructure needs foundational work before content investment will pay off.

> Note: Ubersuggest's own on-page score of 76 reflects only technical pass/fail checks (SSL, sitemap, no broken links). It does not account for traffic, keyword rankings, content depth, E-E-A-T, or authority — which is where this site's real challenges lie.

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | Fix H1 tags — 17 of 26 pages have none | High | Low |
| 2 | Resolve mobile page speed (8.52s load time) | High | Medium |
| 3 | Fix duplicate title tags and meta descriptions (2 each) | High | Low |
| 4 | Identify and unblock the 1 page hidden from search engines | High | Low |
| 5 | Build out therapist credential bios — biggest E-E-A-T gap | High | Medium |

---

## 3. Quick Wins — Under 1 Week

- [ ] **Add H1 tags to all 17 pages missing them** — In Squarespace, edit each page and ensure the first heading block is set to H1, not H2 or a styled paragraph
- [ ] **Fix 2 duplicate title tags** — identify which pages share a title and write unique versions (see Appendix)
- [ ] **Fix 2 duplicate meta descriptions** — same issue; write unique descriptions per page (see Appendix)
- [ ] **Identify the 1 blocked page** — Go to Squarespace Pages > Not Linked / Page Settings and check if any page has "Hide from search engines" enabled; turn it off unless intentional
- [ ] **Fix 2 poorly formatted URLs** — check for URLs with underscores, capitals, or unnecessary parameters; Squarespace allows URL slug editing per page
- [ ] **Expand the 1 title tag that is too short** — likely a page with just a brand name; add service + location keywords
- [ ] **Replace Gmail address** with branded email (hello@lavenderhealingcollective.com via Google Workspace)

---

## 4. Medium-Term Recommendations — 2–8 Weeks

- [ ] **Build full therapist credential bios** for all 7 clinicians: license number, modalities, populations served, education, languages spoken — this is the single biggest E-E-A-T lever available
- [ ] **Add Organization / MedicalBusiness schema** to homepage (see Appendix for code)
- [ ] **Add FAQPage schema** to /faq (see Appendix for code)
- [ ] **Expand thin content on 3 flagged low-word-count pages** — each needs a minimum of 400–600 words of original, keyword-relevant copy
- [ ] **Add geo-targeting language site-wide** — currently only "California" is mentioned; add city references: Los Angeles, Bay Area, San Diego, Sacramento
- [ ] **Strengthen CTAs across all service pages** — replace "Contact us" with "Book Your Free 15-Minute Consult" linked to a booking form or Calendly
- [ ] **Add a testimonials section** — anonymized client quotes on /therapy; practitioner testimonials on /training and /consultation
- [ ] **Replicate the university backlink strategy** — the Claremont colleges linked to this site as a Title IX / emotional resource. Identify 10–15 similar university wellness, diversity, and counseling centres in California and submit for inclusion

---

## 5. Long-Term / Strategic Recommendations

- [ ] **Launch a blog** — target 2 posts/month on condition- and identity-based keywords (see Section 10 for topics); Squarespace has a built-in blog
- [ ] **Build Psychology Today profiles** for all 7 therapists with keyword-rich bios and links back to the site — this builds domain authority and local citation signals
- [ ] **Pursue therapy directory listings** — Therapy Den (already linking), Inclusive Therapists, Open Path Collective, Melanin & Mental Health; each listing = a citation and potential backlink
- [ ] **Apply for LGBTQ+ and BIPoC business directories** — targeted, relevant links that reinforce niche authority
- [ ] **Create a Google Business Profile** — if any registered California address exists, a fully optimised GBP unlocks local pack rankings
- [ ] **Develop training page into a lead generation hub** — 12 trainings are listed with almost no copy; each needs a full description, target audience, format, and pricing tier

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Partial — issues | Present on all pages (passes Ubersuggest check) but 2 duplicate, 1 too short, none keyword-optimised on service pages |
| Meta Descriptions | Partial — issues | 2 duplicate, 2 missing entirely |
| H1 Tags | Critical | **17 of 26 pages have no H1** |
| Heading Hierarchy | Issues | FAQ page uses H4 for questions instead of H2/H3 |
| Schema / Structured Data | None | No Organisation, MedicalBusiness, FAQPage, or Person schema found anywhere |
| XML Sitemap | Present | Passes Ubersuggest check |
| Robots.txt | Present | But 1 page is blocked from indexing — needs investigation |
| SSL / HTTPS | Present | Passes Ubersuggest check |
| Broken Links | None | Passes Ubersuggest check |
| Canonical Tags | Not assessed | Monitor for duplicate content issues |
| Image Alt Text | Likely missing | Not confirmed but common Squarespace issue |
| URL Structure | Issues | 2 pages with poorly formatted URLs |
| Redirects | Monitor | 7 redirected pages — verify all are 301 permanent, not 302 temporary |

**Desktop Site Speed (Ubersuggest / real visitor data, last 28 days):**
- Load Time: **2.97s** — Needs Improvement (target: < 2.5s)
- Interactivity: **260.50ms** — Needs Improvement (target: < 200ms)
- Visual Stability: **0.12** — Needs Improvement (target: ≤ 0.1)

**Mobile Site Speed — CRITICAL:**
- Load Time: **8.52s** — POOR (target: < 2.5s)
- Interactivity: **3,879.58ms** — POOR (target: < 200ms)
- Visual Stability: **0.12** — Needs Improvement

> Mobile speed is the most urgent technical fix. An 8.52s mobile load time will cause significant drop-off from organic visitors and suppresses Google rankings. On Squarespace, the primary levers are: compressing and resizing images, reducing the number of custom fonts, and removing unused third-party scripts.

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: Not confirmed / likely generic Squarespace default
- Meta Description: "QTBIPoC therapists providing trauma-focused, social-justice informed, LGBTQIA+ affirmative therapy to residents of California" — present but not conversion-optimised
- H1: "Therapy, Training, & Consultation by and for LGBTQIA+ BIPoC" — present but no location keyword
- H2s: Our Vision / Meet Our Collective / Our Services / Sign up for our newsletter
- Notes: Good mission clarity; missing schema, location signals, testimonials, and keyword depth

**Therapy (/therapy):**
- Title: Likely generic
- Meta Description: Missing
- H1: "Therapy" — dangerously thin; wastes prime keyword real estate
- Notes: Good values copy but service-specific keywords, modalities, and conditions are absent; CTA links to contact form only

**Training (/training):**
- Title: Likely generic
- Meta Description: Missing
- H1: "Training" — too generic
- Notes: 12 training topics listed with minimal description; no pricing; no outcomes or testimonials; thin content flagged by Ubersuggest

**Consultation (/consultation):**
- Title: Likely generic
- Meta Description: Missing
- H1: "Consultation" — too generic
- Notes: EMDRIA credential is a strong trust signal but buried; CTA directs to Gmail, not a booking flow

**About Us (/about-us):**
- Title: Not confirmed
- Meta Description: Missing
- H1: "About Our Collective" — acceptable
- Notes: Strong mission voice but zero individual therapist credentials; no license numbers, certifications, or educational backgrounds listed

**FAQ (/faq):**
- Title: Not confirmed
- Meta Description: Missing
- H1: "Frequently Asked Questions" — acceptable
- Notes: 6 good questions but uses H4 tags (not H2/H3), no FAQPage schema, no intro copy, not eligible for Google rich snippets

**Content Quality Summary:**
- Word count: Thin across all service pages; 3 pages formally flagged by Ubersuggest
- Keyword targeting: Weak — values-led copy dominates; condition/modality/location keywords largely absent
- Internal linking: Navigation-only; no contextual cross-links between service pages
- CTAs: Passive and generic site-wide; no urgency, no direct booking

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals — Current State:**

| Signal | Present? | Notes |
|---|---|---|
| Therapist bios with credentials | Partial | Names listed but no license numbers, education, or modality detail |
| License numbers | No | Not visible on any page |
| Professional certifications | Partial | EMDRIA Approved Consultant mentioned on /consultation only |
| Professional affiliations | No | Not listed anywhere |
| Media mentions / press | No | Metro Silicon Valley linked to Tiombe Wallace but no "As Seen In" section |
| Client testimonials / reviews | No | Absent site-wide |
| Founder story | Partial | /about-us has a note from the founder but no credentials |
| Awards / recognition | No | Not listed |

**E-E-A-T Assessment:** This is a YMYL (Your Money or Your Life) site — Google holds therapy and health sites to the highest E-E-A-T standards. The collective has genuine expertise and lived experience, but almost none of it is documented in a way Google can evaluate. This is the highest-leverage improvement available.

**Local SEO:**
- Google Business Profile: Not confirmed / likely unclaimed
- Geographic targeting: Only "California" mentioned — no city-level targeting anywhere
- Local citations / directories: Therapy Den (linking), pathwaystoindependence.org (linking) — very limited
- NAP Consistency: Cannot confirm; Gmail address makes this harder to track

---

## 9. Conversion & User Experience Issues

- **CTAs:** "Contact us for a free consultation" appears site-wide but lacks specificity, urgency, and a direct booking path. No Calendly or intake form embedded.
- **Contact friction:** All inquiries funnel to a Gmail address — adds friction and reduces perceived professionalism
- **Mobile UX:** 8.52s load time means most mobile visitors will leave before the page is usable
- **Trust signals above the fold:** Minimal — no credentials, no reviews, no "as seen in" on the homepage
- **Navigation:** 14+ pages in navigation creates cognitive load; key service pages compete for attention

**Key Fixes:**
- Embed a booking widget (Calendly or SimplePractice) on /therapy and /contact-us
- Add at least one social proof element (review, quote, credential) to the homepage hero section
- Consolidate or simplify the navigation to surface the 3 core services clearly

---

## 10. Content & Keyword Strategy Recommendations

**Current State:** 28 ranking keywords, 22 monthly organic visits — effectively zero search presence.

**Target Keyword Opportunities:**

| Keyword | Intent | Priority Page |
|---|---|---|
| LGBTQIA+ therapist California | Commercial | /therapy |
| BIPoC therapist telehealth | Commercial | /therapy |
| trauma-informed therapy California | Commercial | /therapy |
| EMDR therapist California | Commercial | /consultation |
| EMDR consultation EMDRIA | Commercial | /consultation |
| trauma-informed training for organizations | Commercial | /training |
| DEI mental health training | Commercial | /training |
| therapy for Black women California | Commercial | /therapy |
| LGBTQ affirming therapist Los Angeles | Commercial | /therapy |
| what is trauma-informed therapy | Informational | Blog |
| EMDR for racial trauma | Informational | Blog |
| how to find a BIPoC therapist | Informational | Blog |
| sliding scale therapy California | Informational | /faq or /therapy |

**Content Gap Analysis:**
- No blog / resource section exists — the site cannot rank for informational queries at all
- No condition-specific landing pages (e.g., depression, anxiety, trauma, PTSD) — these are high-volume search terms
- No service-area pages for major California cities

**Recommended First 6 Blog Posts:**
1. *"What Is Trauma-Informed Therapy? A Guide for LGBTQIA+ BIPoC Communities"* — targets high-volume informational query, positions expertise
2. *"How to Find a BIPoC Therapist in California (And What to Look For)"* — captures high-intent searchers in discovery phase
3. *"EMDR for Racial Trauma: What It Is and How It Helps"* — builds authority around EMDR offering
4. *"What Is the Difference Between a LMFT and AMFT?"* — repurpose existing FAQ answer into full post
5. *"Sliding Scale Therapy in California: What It Is and How to Ask For It"* — captures cost-conscious searchers
6. *"What to Expect in Your First Therapy Session as an LGBTQIA+ Person of Color"* — reduces barrier to booking; deeply niche-aligned

---

## 11. Next Steps & Action Plan

1. **This week — identify the blocked page** and re-enable indexing if appropriate
2. **This week — add H1 tags to all 17 pages missing them** (Squarespace: edit each page's first heading block)
3. **This week — fix duplicate title tags and meta descriptions** (see Appendix for rewrites)
4. **This week — compress and resize all images** to address mobile speed; use Squarespace's built-in image editor or compress before upload
5. **Week 2 — write unique meta descriptions for all key pages** (see Appendix)
6. **Week 2–3 — build therapist bio pages** with full credentials for all 7 clinicians
7. **Week 3–4 — add FAQPage schema and Organization schema** (see Appendix)
8. **Week 4 — expand thin content pages** to 400+ words with keyword-relevant copy
9. **Week 4–6 — replace Gmail with branded email** and set up a booking widget on /therapy and /contact-us
10. **Month 2 — launch blog** with first 2 posts; target 2 per month ongoing
11. **Ongoing — submit to 5 therapy/LGBTQ+ directories per month** to build backlink profile

---

## Appendix

### Backlink Profile Summary (Ubersuggest)

| Metric | Value | Assessment |
|---|---|---|
| Domain Authority | 12 | Low — industry average for therapy sites is 15–30 |
| Referring Domains | 19 | Very low |
| Total Backlinks | 27 | Very low |
| Nofollow Backlinks | 14 (52%) | High proportion of nofollow links |
| Spam Score (top links) | Mostly 1–2% | Clean — no penalty risk |

**Strongest Backlinks (leverage these for outreach templates):**

| Source | DA | Target Page | Notes |
|---|---|---|---|
| hmc.edu (Harvey Mudd) | 73 | /contact-us | Title IX resource listing |
| online.cmc.edu (Claremont McKenna) | 61 | /contact-us | Emotional resources listing |
| claremontmckenna.edu | 57 | /contact-us | Title IX resource listing |
| metrosiliconvalley.com | 56 | /tiombe-wallace | Media feature |
| therapyden.com | 51 | /gloria-de-la-mora | Therapist directory |

> The university links (DA 57–73) are this site's best assets. They came from being listed as a Title IX emotional support resource. **Replicate this:** identify 15–20 similar university wellness, diversity, and student support pages in California and submit for inclusion. This is the fastest path to raising Domain Authority.

**Low-Quality Links to Monitor:**
- backlinks-checker.com (DA 18, Spam 7%) — automated/spam; monitor but no action needed yet
- shortenurls.eu (DA 15, Spam 7%) — same; not harmful at current volume

### Suggested Title Tags

| Page | Recommended Title Tag |
|---|---|
| Homepage | `Trauma-Informed Therapy for LGBTQIA+ BIPoC \| Lavender Healing Collective` |
| /therapy | `LGBTQIA+ BIPoC Therapists in California (Telehealth) \| Lavender Healing Collective` |
| /training | `Trauma-Informed & DEI Training for Organizations \| Lavender Healing Collective` |
| /consultation | `EMDR Consultation & Clinical Supervision \| Lavender Healing Collective` |
| /about-us | `About Our Collective \| QTBIPoC Therapists in California` |
| /faq | `FAQs About Therapy for LGBTQIA+ BIPoC \| Lavender Healing Collective` |

### Suggested Meta Descriptions

| Page | Recommended Meta Description |
|---|---|
| Homepage | `Trauma-informed, culturally responsive therapy for LGBTQIA+ BIPoC communities across California. Individual, couples, and family therapy via telehealth. Book a free 15-min consult.` |
| /therapy | `Connect with QTBIPoC therapists offering trauma-informed, LGBTQIA+ affirmative therapy across California via telehealth. Sliding scale available. Book a free 15-minute consultation.` |
| /training | `Evidence-based DEI and trauma-informed trainings for organizations, agencies, and educational institutions. Delivered worldwide. Contact us to schedule your training.` |
| /consultation | `EMDR consultation with an EMDRIA Approved Consultant and clinical supervision for associates — grounded in anti-oppressive, intersectional frameworks. Based in California.` |
| /about-us | `Meet the QTBIPoC licensed therapists behind Lavender Healing Collective — trauma-informed, culturally responsive, and committed to mental health justice.` |
| /faq | `Answers to common questions about therapy rates, telehealth, insurance, and working with our LGBTQIA+ BIPoC therapist collective in California.` |

### Schema Code — FAQPage (/faq)

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Do you offer virtual/telehealth sessions?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. All sessions are conducted via telehealth for California residents."
      }
    },
    {
      "@type": "Question",
      "name": "Do you take insurance?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "We do not bill insurance directly but can provide superbills for out-of-network reimbursement. Sliding scale rates are available."
      }
    },
    {
      "@type": "Question",
      "name": "What are your rates?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Rates vary by clinician. We offer a sliding scale. Contact us for current fee information."
      }
    },
    {
      "@type": "Question",
      "name": "What is the difference between an AMFT and LMFT?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "A Licensed Marriage and Family Therapist (LMFT) is fully licensed in California. An Associate Marriage and Family Therapist (AMFT) is working toward full licensure under the supervision of a licensed clinician. Both are trained professionals providing quality care."
      }
    }
  ]
}
```

### Schema Code — MedicalBusiness (Homepage)

```json
{
  "@context": "https://schema.org",
  "@type": "MedicalBusiness",
  "name": "Lavender Healing Collective",
  "url": "https://www.lavenderhealingcollective.com",
  "description": "Trauma-informed, culturally responsive therapy for LGBTQIA+ BIPoC communities across California via telehealth.",
  "areaServed": {
    "@type": "State",
    "name": "California"
  },
  "serviceType": [
    "Psychotherapy",
    "EMDR Therapy",
    "Clinical Supervision",
    "DEI Training",
    "Trauma-Informed Therapy"
  ],
  "availableChannel": {
    "@type": "ServiceChannel",
    "serviceType": "Telehealth"
  },
  "knowsAbout": [
    "Trauma-informed therapy",
    "LGBTQIA+ affirming therapy",
    "BIPoC mental health",
    "EMDR",
    "Intersectional feminist therapy"
  ]
}
```

### Mobile Speed — Quick Fixes for Squarespace

1. **Compress all images before uploading** — use Squoosh (free) or TinyPNG; target < 200KB per image
2. **Limit custom fonts** — each custom font adds a render-blocking request; use 1–2 max
3. **Remove unused third-party scripts** — check Settings > Advanced > Code Injection for any old embeds
4. **Use Squarespace's built-in image format settings** — enable WebP if available in your template
5. **Avoid video backgrounds on mobile** — if any hero sections use video, disable on mobile via Squarespace's mobile editor
