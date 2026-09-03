# Guidance Through Grief Counseling Center — SEO Audit
## September 2, 2026 | Baseline Report

**Website:** https://www.guidancethroughgriefcounseling.com  
**Auditor:** GroupRB Marketing  
**Platform:** Weebly  
**Category:** Mental Health / Grief Counseling (YMYL)

---

## 1. Overall SEO Health Score

**48 / 100 — Needs Significant Work**

This is a YMYL (Your Money or Your Life) site — Google holds therapy, mental health, and health-adjacent websites to its strictest E-E-A-T standards. Guidance Through Grief has genuine expertise (a doctorate, published staff, licensed PLLC) but is communicating almost none of it to search engines. No meta descriptions exist anywhere on the site, there is no schema markup of any kind, the homepage has no H1 tag, and the therapist profile pages — the most critical E-E-A-T pages on any therapy website — return 404 errors. The site also has no original content (blog or articles), which is a significant gap for building topical authority in the grief counseling space.

### Score Breakdown
| Factor | Score | Notes |
|---|---|---|
| Technical SEO | 44/100 | No canonicals, Weebly platform limitations, 404 on key pages |
| On-Page SEO | 40/100 | No meta descriptions, no H1 on homepage, thin service content |
| Schema Markup | 0/100 | Zero structured data anywhere |
| E-E-A-T Signals | 52/100 | Credentials exist but not expressed on-site or to Google |
| Content Depth | 35/100 | ~250 words on services page; no blog; external-only resources |
| Local SEO | 45/100 | NAP present but no Google Business Profile signals, no LocalBusiness schema |

---

## 2. Top 5 Priorities Right Now

1. **Fix the broken therapist profile pages (404)** — These are the most important E-E-A-T pages on the site for a therapy practice. Google explicitly uses therapist credentials, licenses, and training to evaluate mental health sites. If these pages are broken, that authority signal is zero.

2. **Add meta descriptions to all pages** — No page on the site has a meta description. For a therapy practice, the meta description is often the first human touchpoint in search — it communicates empathy and professionalism before someone even clicks.

3. **Add an H1 tag to the homepage** — The homepage has no H1 at all. This is the most basic on-page signal telling Google what the page is about.

4. **Implement LocalBusiness + MedicalOrganization schema** — No schema anywhere on the site. For a local therapy practice this is critical: it connects the business to Google Maps, establishes the practice type, and signals licensed professional services.

5. **Add a Google Business Profile and verify the NAP** — The address appears as "Suite 203" on the contact page and "Suite 204" on the homepage. This NAP inconsistency alone can suppress local search rankings.

---

## 3. Quick Wins (< 1 week)

### QW-1: Fix the NAP Inconsistency
**Effort:** Very Low | **Impact:** High (Local SEO)  
The suite number differs between pages: Suite 203 (contact) vs. Suite 204 (homepage). Pick one, make it consistent across the entire site and on Google Business Profile, Yelp, Psychology Today, and any other directories.

### QW-2: Add Meta Descriptions to All Pages
**Effort:** Low | **Impact:** High  
Weebly allows meta descriptions per page in the page settings. Every page needs one. For a grief counseling practice, these should lead with empathy and end with a local CTA.

**Homepage:**
> "Grief counseling in Candler and Asheville, NC. Guidance Through Grief offers individual therapy, EMDR, grief retreats, and community grief support. Call 828-357-7388."
*(~160 chars)*

**Services:**
> "Grief therapy, EMDR, grief retreats, and organizational grief support near Asheville, NC. Dr. Liz Anderson and Amy Fowler — accepting BCBS, Aetna, and United."
*(~160 chars)*

**Contact:**
> "Contact Guidance Through Grief Counseling Center in Candler, NC. Call 828-357-7388 to schedule grief therapy, EMDR, or ask about grief retreats."
*(~146 chars)*

### QW-3: Add an H1 to the Homepage
**Effort:** Very Low | **Impact:** High  
Weebly lets you set header styles. The homepage's primary heading — whatever introduces the practice — should be set to H1. Suggested: **"Grief Counseling in Asheville, NC"** or **"You Don't Have to Grieve Alone — Compassionate Grief Therapy in Western North Carolina"**

### QW-4: Fix the Page Title Tag on Homepage
**Effort:** Very Low | **Impact:** Medium  
Current title: *"Guidance Through Grief Counseling Center, PLLC - Guidance Through Grief Counseling, PLLC"*  
The name is duplicated. Suggested: **"Grief Counseling in Asheville, NC | Guidance Through Grief"** — includes the primary keyword and location.

### QW-5: Create or Restore Therapist Profile Pages
**Effort:** Low | **Impact:** Critical for E-E-A-T  
The therapist pages are returning 404. Either restore them or rebuild them. Each therapist needs:
- Full name and credentials (Dr. Liz Anderson, PhD/EdD/PsyD + license type)
- License number and state (builds Google's trust for YMYL)
- Specializations and training
- Professional photo
- Personal statement / approach to grief therapy

---

## 4. Medium-Term Recommendations (2–8 Weeks)

### MT-1: Implement LocalBusiness + MedicalOrganization Schema
For a local therapy practice this is the highest-leverage schema to add. Weebly has limited built-in schema support, but custom JSON-LD can be injected via the Header/Footer code section in Weebly Settings.

```json
{
  "@context": "https://schema.org",
  "@type": ["MedicalOrganization", "LocalBusiness"],
  "name": "Guidance Through Grief Counseling Center, PLLC",
  "url": "https://www.guidancethroughgriefcounseling.com",
  "telephone": "+1-828-357-7388",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "9 Asbury Road, Suite 203",
    "addressLocality": "Candler",
    "addressRegion": "NC",
    "postalCode": "[zip code]",
    "addressCountry": "US"
  },
  "medicalSpecialty": "MentalHealth",
  "description": "Grief counseling center in Candler, NC offering individual grief therapy, EMDR, grief retreats, and community grief support near Asheville.",
  "priceRange": "$140–$175",
  "paymentAccepted": "Insurance, Private Pay",
  "currenciesAccepted": "USD"
}
```

### MT-2: Add Person Schema to Each Therapist Page
Google explicitly uses therapist credentials to evaluate YMYL therapy sites. Once the therapist pages are rebuilt, add Person + MedicalBusiness schema:

```json
{
  "@context": "https://schema.org",
  "@type": "Physician",
  "name": "Dr. Liz Anderson",
  "jobTitle": "Grief Therapist",
  "description": "Dr. Liz Anderson holds a doctorate and specializes in grief therapy, EMDR, and bereavement counseling in Western North Carolina.",
  "worksFor": {
    "@type": "MedicalOrganization",
    "name": "Guidance Through Grief Counseling Center, PLLC"
  }
}
```

### MT-3: Build Out Service Pages with Keyword-Rich Content
Current service descriptions are 50–80 words each. Google needs 400–700 words per service page to understand topic depth, especially for YMYL content. Each service page should include:
- What it is and how it works
- Who it's for (types of loss, life stages)
- What to expect in a session
- FAQ section (which also gets FAQPage schema)
- A compassionate CTA

Priority pages to expand:
1. `/grief-therapy` — target "grief therapy Asheville NC", "grief counselor near me"
2. `/emdr-therapy` — target "EMDR therapy Asheville", "EMDR for grief and loss"
3. `/grief-retreats` — target "grief retreats North Carolina", "grief support groups Asheville"

### MT-4: Create a Google Business Profile
A Google Business Profile is essential for "near me" and map pack visibility. The practice is currently missing this (or it's unclaimed/unverified). A verified GBP lets the practice show in the local 3-pack for searches like "grief counselor Asheville NC."

### MT-5: List on Therapist Directories
Psychology Today, TherapyDen, Alma, and Zencare are the highest-authority backlink sources for therapy practices — Google uses these as trust signals for YMYL sites. Each listing also:
- Builds citations (NAP consistency)
- Drives direct referral traffic
- Strengthens domain authority

---

## 5. Long-Term / Strategic Recommendations

### LT-1: Build a Grief Counseling Blog
The Resources page links to 23 external materials — this shows curation expertise but sends users away and builds no topical authority for the site itself. A blog with original articles would:
- Rank for high-intent, long-tail grief queries
- Establish Dr. Anderson and Amy Fowler as recognized voices in the grief community
- Give Google original content to evaluate E-E-A-T

**Content ideas:**
- "What to Expect in Your First Grief Therapy Session"
- "EMDR for Grief: How It's Different from Traditional Talk Therapy"
- "5 Ways to Support a Friend Who Is Grieving"
- "When Grief Turns Into Depression: Signs to Watch For"
- "Grief Retreats vs. Traditional Therapy: Which Is Right for You?"

### LT-2: Target Asheville as the Primary City
"Candler, NC" has very low search volume. "Asheville, NC" is the dominant nearby city with much higher search demand. The site should:
- Reference Asheville prominently in title tags, H1s, and meta descriptions
- Add a "Serving the Asheville area" statement to the homepage
- Target "grief counseling Asheville NC" as the primary keyword

### LT-3: Publish Credentials and Licenses Prominently
For YMYL sites, Google wants to see:
- License type and number (LCSW, LPC, PhD, etc.) for each therapist
- Insurance panels
- Professional associations (ADEC — Association for Death Education and Counseling is ideal for grief specialists)
- Any continuing education or specialized grief training (e.g., Grief Recovery Method, complicated grief training)

### LT-4: Earn Authoritative Backlinks
- Submit to ADEC (Association for Death Education and Counseling) member directory
- Contact local hospice organizations, funeral homes, and hospitals for referral partner links
- Offer to write guest articles for local Asheville publications
- Submit to NAMI Western Carolina chapter's resource page

---

## 6. Technical SEO Analysis

| Issue | Status | Priority |
|---|---|---|
| Meta descriptions | ❌ Missing all pages | Critical |
| H1 tag on homepage | ❌ Missing | Critical |
| Schema markup | ❌ None found | Critical |
| Canonical tags | ❌ Missing all pages | High |
| Therapist pages | ❌ 404 errors | Critical |
| NAP consistency (suite 203 vs 204) | ❌ Inconsistent | High |
| Duplicate homepage title tag | ❌ Name repeated twice | High |
| HTTPS | ✅ Active | — |
| Google Business Profile | ⚠️ Unknown/unconfirmed | High |
| Page speed | ⚠️ Weebly — typically acceptable | Medium |
| Mobile-friendly | ✅ Weebly is responsive | — |
| Sitemap | ⚠️ Not confirmed | Medium |
| Blog / original content | ❌ None | High |

### Platform Note: Weebly Limitations
Weebly is a drag-and-drop builder with limited native SEO tools. Key limitations:
- Schema markup requires manual injection via Header Code section
- Canonical tags must be manually added per page
- URL structure is constrained by Weebly's routing
- Limited control over technical elements like structured data

**Recommendation:** If the practice is committed to SEO growth, consider migrating to WordPress (with Yoast/RankMath) or Squarespace within 12 months. Weebly will become a ceiling.

---

## 7. On-Page SEO Analysis

| Page | Title | Meta Desc | H1 | Schema | Word Count |
|---|---|---|---|---|---|
| / (Homepage) | ⚠️ Duplicated name | ❌ Missing | ❌ None | ❌ None | ~200 |
| /services | ✅ "Services - GTG" | ❌ Missing | ⚠️ 3 H1s (should be H2s) | ❌ None | ~250 |
| /resources | ✅ "Resources - GTG" | ❌ Missing | ✅ 1 | ❌ None | ~400 |
| /contact | ⚠️ Not detected | ❌ Missing | ❌ Not detected | ❌ None | ~100 |
| /meet-our-therapists | ❌ 404 | — | — | — | — |

---

## 8. E-E-A-T Assessment — 52 / 100

**Critical context:** Mental health and therapy sites fall under Google's "Your Money or Your Life" (YMYL) category. Google applies its strictest quality standards to these pages because low-quality results could harm users seeking mental health support. E-E-A-T is not optional for this site — it is the primary ranking determinant.

| Signal | Status | Notes |
|---|---|---|
| Therapist credentials visible on-site | ❌ Critical gap | Profile pages return 404; credentials not shown elsewhere |
| License numbers displayed | ❌ Missing | Essential for YMYL therapy sites |
| Professional associations | ❌ Missing | ADEC membership would be a strong signal |
| Education / degrees | ⚠️ Partial | Doctorate mentioned for Dr. Anderson but no specifics |
| Insurance acceptance listed | ✅ Present | BCBS, Aetna, United — builds trust |
| Published content / author bylines | ⚠️ Partial | Amy Fowler noted as op-ed contributor — not linked from site |
| Author schema | ❌ Missing | No structured data for therapist bios |
| About page | ⚠️ Unclear | Not found in crawl |
| Original thought leadership | ❌ None | No blog, no published articles on-site |
| Third-party directory listings | ⚠️ Unknown | Psychology Today, Alma, etc. — not confirmed |

---

## 9. Local SEO Analysis

| Factor | Status | Notes |
|---|---|---|
| Google Business Profile | ⚠️ Unconfirmed | Must be claimed and verified |
| NAP consistency | ❌ Inconsistent | Suite 203 vs. 204 across pages |
| LocalBusiness schema | ❌ Missing | |
| City in title/H1 | ❌ Missing | "Asheville" not used despite proximity |
| Local keyword targeting | ❌ Missing | "Grief counseling Asheville NC" not targeted |
| Therapist directory listings | ⚠️ Unknown | Psychology Today, TherapyDen, Zencare |
| Reviews/testimonials | ⚠️ Not visible | Likely restricted by ethics guidelines — use schema |

---

## 10. Keyword Strategy

### Primary Target Keywords
| Keyword | Est. Monthly Searches | Difficulty | Priority |
|---|---|---|---|
| grief counseling Asheville NC | 260 | Low | #1 — own this |
| grief therapist Asheville | 140 | Low | High |
| EMDR therapy Asheville NC | 90 | Low | High |
| grief counselor near me | 8,100 | High (local intent) | High |
| grief therapy near me | 4,400 | High (local intent) | High |
| grief support groups Asheville | 70 | Very Low | Own now |
| grief retreats North Carolina | 110 | Very Low | Own now |
| bereavement counseling Asheville | 50 | Very Low | Own now |
| EMDR grief and loss | 260 | Medium | Medium |
| grief counseling after death of spouse | 480 | Medium | Medium |

### Long-Tail Content Opportunities
- "grief counseling for parents who lost a child" — high emotional intent
- "EMDR therapy for complicated grief" — specific, rankable
- "grief retreats Western North Carolina" — geographic + service
- "how long does grief therapy take" — FAQ content
- "what to expect in grief counseling" — top-of-funnel

---

## 11. Action Plan

| Action | Effort | Impact | Timeline |
|---|---|---|---|
| Fix NAP inconsistency (suite 203 vs 204) | Very Low | High | Day 1 |
| Add H1 to homepage | Very Low | Critical | Day 1 |
| Fix/rebuild therapist profile pages | Low | Critical | Week 1 |
| Add meta descriptions to all pages | Low | Critical | Week 1 |
| Fix duplicated homepage title tag | Very Low | Medium | Week 1 |
| Claim/verify Google Business Profile | Low | High | Week 1 |
| Inject LocalBusiness schema via Weebly Header Code | Medium | Critical | Week 2 |
| Add Person/Physician schema to therapist pages | Medium | Critical | Week 2 |
| Add canonical tags | Low | High | Week 2 |
| Expand service pages to 400–600 words each | Medium | High | Weeks 3–5 |
| Add FAQPage schema to service pages | Medium | Medium | Weeks 4–6 |
| List on Psychology Today, TherapyDen, Zencare | Low | High | Weeks 2–4 |
| Submit to ADEC member directory | Low | High | Week 3 |
| Start blog with 4–6 foundational articles | High | High | Months 2–4 |
| Consider platform migration to WordPress | High | Long-term | Month 6+ |

---

### Appendix: Keyword-Rich Title Tag Recommendations

| Page | Current Title | Recommended Title |
|---|---|---|
| Homepage | Guidance Through Grief Counseling Center, PLLC - Guidance Through Grief Counseling, PLLC | Grief Counseling in Asheville, NC \| Guidance Through Grief |
| Services | Services - Guidance Through Grief Counseling Center, PLLC | Grief Therapy & EMDR in Asheville, NC \| Our Services |
| Therapists | (404) | Our Grief Therapists in Asheville \| Dr. Liz Anderson & Amy Fowler |
| Resources | Resources - Guidance Through Grief Counseling Center, PLLC | Grief Resources \| Books, Videos & Support Tools |
| Contact | (not detected) | Contact Us \| Grief Counseling in Candler & Asheville, NC |

---

*Prepared by GroupRB Marketing | richard@grouprb.com | September 2, 2026*  
*YMYL Note: All SEO recommendations for this site must be balanced with the ethical guidelines governing mental health professionals in North Carolina.*
