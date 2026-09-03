# WFC SEO Audit — September 1, 2026

**Client:** Wandering Free Counseling, LLC  
**URL:** https://www.wanderingfreecounseling.com  
**Therapist:** Erin Zarlino, LPCC  
**Agency:** Parts of Practice  
**Auditor:** Richard (GroupRB Marketing / Parts of Practice)  
**Date:** 2026-09-01  
**SEO Health Score: 47 / 100 — Critical**

---

## 1. Overall SEO Health Score

**47 / 100 — Critical**

The site has functional pages with some keyword-optimized headings but is missing meta descriptions on all 6 pages, has zero schema markup, two critical title tag problems, thin content on two service pages, and a NAP inconsistency between "Columbus Ohio" (homepage title) and the actual Dublin, OH address. As a YMYL therapy site, weak E-E-A-T signals compound the ranking challenge.

Score deductions:
- No meta descriptions on any page: −20
- No schema markup on any page: −12
- 2 bad/missing title tags (EMDR page, Contact page): −8
- Thin content on 2 pages (perinatal ~375 words, childhood trauma ~675 words): −5
- NAP inconsistency (Columbus vs. Dublin): −3
- No E-E-A-T author/bio page: −3
- URL typo (/insurancesandfeees): −2

---

## 2. Top 5 Priorities Right Now

1. **Write meta descriptions for all 6 pages** — zero exist; this is a direct click-through driver
2. **Fix the EMDR page title tag** — currently just "Wandering Free Counseling, LLC" with no keywords
3. **Add LocalBusiness + MentalHealthBusiness + Person schema to homepage** — critical for maps, local pack, and trust
4. **Resolve Columbus vs. Dublin NAP inconsistency** — update homepage title to Dublin; verify Google Business Profile
5. **Add Product/Service schema to each service page** — no structured data exists site-wide

---

## 3. Quick Wins (< 1 Week)

- [ ] Write meta descriptions for all 6 pages (see recommended copy below)
- [ ] Fix EMDR page title: change from "Wandering Free Counseling, LLC" → "EMDR Therapy + Intensives Columbus Ohio | Wandering Free Counseling"
- [ ] Fix Contact page title: change from "Healing Starts Here" → "Contact Wandering Free Counseling | Dublin, OH Trauma Therapist"
- [ ] Update homepage meta title to say "Dublin" not "Columbus": "Wandering Free Counseling, LLC | Trauma Therapist in Dublin Ohio"
- [ ] Fix Insurance page URL typo: redirect /insurancesandfeees → /insurance-and-fees (301 redirect)
- [ ] Add alt text to any images missing it (especially perinatal page images)

### Recommended Meta Descriptions

**Homepage (/):**  
EMDR therapy and trauma counseling in Dublin, Ohio. Erin Zarlino, LPCC specializes in childhood trauma, perinatal trauma, and EMDR intensives. Accepting Medical Mutual, Aetna, UHC and more.

**EMDR (/emdr):**  
EMDR therapy and intensive sessions in Columbus/Dublin, Ohio. Treat trauma, anxiety, depression, and PTSD faster with 90-minute, 3-hour, or 6-hour EMDR intensives. Insurance accepted.

**Childhood Trauma (/childhoodtrauma):**  
Therapy for childhood wounds and complex trauma in Columbus, Ohio. Erin Zarlino, LPCC uses EMDR to help adults heal from abuse, neglect, and early trauma. In-person and virtual.

**Perinatal Trauma (/perinatal-trauma):**  
Perinatal trauma therapy in Dublin, Ohio. Specializing in birth trauma, pregnancy loss, NICU experiences, and postpartum anxiety and depression. EMDR-certified therapist.

**Insurance & Fees (/insurancesandfeees):**  
Wandering Free Counseling accepts Medical Mutual, Aetna, UHC, Tri-Care East, and EAPs. Session rates from $125–$165. Good Faith Estimates available.

**Contact (/contactme):**  
Contact Erin Zarlino, LPCC at Wandering Free Counseling in Dublin, Ohio. Call or text 614-881-2439. Office located at 5995 Wilcox Place Suite D, Dublin OH 43016.

---

## 4. Medium-Term Recommendations (2–8 Weeks)

### Schema Markup (Priority: High)

Add to homepage:
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": ["MedicalBusiness", "LocalBusiness"],
      "name": "Wandering Free Counseling, LLC",
      "url": "https://www.wanderingfreecounseling.com",
      "telephone": "+16148812439",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "5995 Wilcox Place Suite D",
        "addressLocality": "Dublin",
        "addressRegion": "OH",
        "postalCode": "43016",
        "addressCountry": "US"
      },
      "openingHours": ["We 08:30-17:00", "Th 10:00-18:00", "Fr 08:30-16:00"],
      "priceRange": "$125–$1200",
      "medicalSpecialty": "Psychiatry",
      "description": "EMDR therapy and trauma counseling in Dublin, Ohio. Specializing in childhood trauma, perinatal trauma, and EMDR intensives."
    },
    {
      "@type": "Person",
      "name": "Erin Zarlino",
      "jobTitle": "Licensed Professional Clinical Counselor",
      "hasCredential": "LPCC",
      "worksFor": { "@name": "Wandering Free Counseling, LLC" },
      "url": "https://www.wanderingfreecounseling.com"
    }
  ]
}
```

Add to each service page:
```json
{
  "@type": "Service",
  "name": "[Service Name]",
  "provider": { "@type": "LocalBusiness", "name": "Wandering Free Counseling, LLC" },
  "areaServed": { "@type": "City", "name": "Columbus" }
}
```

### Content Expansion

- **Perinatal Trauma page (~375 words → 800+):** Add sections on: specific conditions treated (NICU trauma, pregnancy loss, birth trauma), what sessions look like, what clients can expect, a FAQ section
- **Childhood Trauma page (~675 words → 1,000+):** Expand the FAQ section, add a "Signs you may benefit from treatment" section, add a closing CTA

### Create a Dedicated "Meet Erin" Page

Currently the "Meet Erin Zarlino, LPCC" nav link goes to the homepage — a missed E-E-A-T opportunity. Create `/meet-erin` with:
- Full bio with LPCC credential featured prominently
- Training and EMDR certification (EMDRIA-certified)
- Personal statement (she already shares she has 3 kids and experienced pregnancy loss — this is powerful)
- Professional headshot with proper alt text
- Person schema

---

## 5. Long-Term / Strategic Recommendations

### Blog / Content Marketing
The site has no blog. For a trauma therapist, content is how you rank for high-intent search terms:
- "EMDR therapy vs traditional therapy"
- "EMDR intensives Columbus Ohio"
- "birth trauma therapy Dublin Ohio"
- "how to heal from childhood trauma"
- "what is perinatal trauma"

Recommend 2–4 articles per month, each 800–1,200 words, targeting one keyword cluster per post.

### Google Business Profile
- Verify the GBP category: "Mental Health Clinic" or "Counselor" or "Psychotherapist"
- Ensure Dublin OH address matches website — not Columbus
- Add hours, services, photos, and collect reviews (5+ Google reviews builds local pack presence)

### Local SEO — Subdomain / Nearby Cities
Once established in Dublin/Columbus, consider location pages targeting:
- "EMDR therapy Westerville Ohio"
- "Trauma therapist New Albany Ohio"
- "EMDR intensives Dublin Ohio" (more specific)

### Psychology Today Profile Optimization
- The PT profile exists (backlink) — ensure it links back to the website
- Ensure the specialty tags match site content: EMDR, Trauma, Perinatal, Childhood Trauma

---

## 6. Technical SEO

| Item | Status | Notes |
|------|--------|-------|
| Title tags | ⚠️ Partial | 4 of 6 good; EMDR and Contact need fixing |
| Meta descriptions | ✗ Missing | 0 of 6 pages have meta descriptions |
| H1 tags | ✓ Good | All pages have H1; mostly keyword-rich |
| H2/H3 structure | ✓ Good | EMDR page especially well-structured |
| Schema markup | ✗ Missing | 0 of 6 pages |
| Image alt text | ⚠️ Partial | Some present, some missing |
| URL structure | ⚠️ Issue | /insurancesandfeees has typo |
| NAP consistency | ✗ Issue | Columbus in title vs Dublin in address |
| Canonical tags | Unknown | Not detected |
| Mobile | Likely OK | Menu toggle indicates responsive |
| Site speed | Not tested | — |
| SSL | ✓ | HTTPS confirmed |
| Blog | ✗ None | No content marketing |

---

## 7. Page-by-Page Audit

### Homepage (/)
- **Title:** Wandering Free Counseling, LLC | Trauma Therapist in Columbus Ohio (64 chars) ✓
- **H1:** Wandering Free Counseling, LLC (brand name only — not keyword-rich) ⚠️
- **Meta Description:** MISSING ✗
- **Schema:** NONE ✗
- **Issues:** H1 should include a keyword; "Columbus Ohio" in title but address is Dublin OH

### EMDR Therapy (/emdr)
- **Title:** Wandering Free Counseling, LLC (brand only — NO keywords) ✗ Critical
- **H1:** EMDR therapy + EMDR intensives for men and women in Columbus, Ohio ✓ Excellent
- **Meta Description:** MISSING ✗
- **Schema:** NONE ✗
- **Word Count:** ~2,200 ✓ Strong
- **Notes:** Best content on the site; title tag is the only critical issue

### Childhood Trauma (/childhoodtrauma)
- **Title:** Therapy For Childhood Wounds Columbus, OH ✓
- **H1:** Therapy For Childhood Wounds Columbus, OH ✓
- **Meta Description:** MISSING ✗
- **Schema:** NONE ✗
- **Word Count:** ~675 ⚠️ Could be deeper
- **Contact info visible** on this page ✓

### Perinatal Trauma (/perinatal-trauma)
- **Title:** Perinatal Trauma Therapy Columbus, OH ✓
- **H1:** Perinatal Trauma Therapy Columbus, OH ✓
- **Meta Description:** MISSING ✗
- **Schema:** NONE ✗
- **Word Count:** ~375 ✗ Thin
- **Notes:** Page has personal credibility ("I have 3 kids, I have experienced a pregnancy loss") — expand this

### Insurance & Fees (/insurancesandfeees)
- **Title:** Insurances and Fees ⚠️ (no location, no brand)
- **H1:** Insurances and Fees ⚠️
- **Meta Description:** MISSING ✗
- **Schema:** NONE ✗
- **URL:** /insurancesandfeees — typo (3 e's) ✗
- **Content Quality:** Good — complete fee schedule, insurance list, Good Faith Estimate ✓

### Contact (/contactme)
- **Title:** "Healing Starts Here" ✗ Critical — meaningless to search engines
- **H1:** Contact me. ✓
- **Meta Description:** MISSING ✗
- **Schema:** NONE ✗
- **Content:** Complete NAP, hours, office directions ✓

---

## 8. Keyword Opportunities

| Keyword | Monthly Vol. Est. | Difficulty | Priority |
|---------|------------------|------------|----------|
| EMDR therapy Columbus Ohio | High | Medium | High |
| EMDR intensives Columbus Ohio | Medium | Low | High |
| trauma therapist Dublin Ohio | Medium | Low | High |
| childhood trauma therapy Columbus | Medium | Low | High |
| perinatal trauma therapy Ohio | Low | Low | Medium |
| birth trauma therapy Columbus | Low | Low | Medium |
| EMDR therapy Dublin Ohio | Medium | Low | High |
| LPCC therapist Columbus | Low | Low | Medium |

---

## 9. E-E-A-T Assessment (YMYL)

Mental health is a YMYL (Your Money or Your Life) category. Google applies extra scrutiny.

| Signal | Status | Action |
|--------|--------|--------|
| Therapist credential (LPCC) | ⚠️ Mentioned but not featured | Add to schema + H1 of About page |
| EMDRIA certification | ⚠️ Referenced in FAQ | Surface on homepage and About page |
| Author bio page | ✗ Missing | Create /meet-erin with full credentials |
| Professional photo | ✓ Present | Ensure alt text includes name + credential |
| Office address | ✓ Visible on multiple pages | Add to schema |
| Personal experience (loss, 3 kids) | ✓ Mentioned on perinatal page | Expand — powerful trust signal |
| Psychology Today listing | ✓ Exists | Ensure it links to site |
| Google Business Profile | Unknown | Verify + optimize |
| Reviews | Unknown | Start collecting |

---

## 10. Competitor / Market Context

- Dublin OH is a competitive Columbus suburb for therapists
- EMDR intensives is a differentiator — very few therapists offer 6-hour intensives
- Accepting insurance is a significant competitive advantage in this market
- "Not all who wander are lost" brand voice is memorable — lean into it

---

## 11. Recommended Schema (Full Homepage Block)

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": ["MedicalBusiness", "LocalBusiness"],
      "@id": "https://www.wanderingfreecounseling.com/#organization",
      "name": "Wandering Free Counseling, LLC",
      "url": "https://www.wanderingfreecounseling.com",
      "logo": "https://www.wanderingfreecounseling.com/logo.png",
      "telephone": "+16148812439",
      "faxNumber": "+16148039745",
      "email": "info@wanderingfreecounseling.sprucecare.com",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "5995 Wilcox Place Suite D",
        "addressLocality": "Dublin",
        "addressRegion": "OH",
        "postalCode": "43016",
        "addressCountry": "US"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 40.0992,
        "longitude": -83.1141
      },
      "openingHoursSpecification": [
        { "@type": "OpeningHoursSpecification", "dayOfWeek": "Wednesday", "opens": "08:30", "closes": "17:00" },
        { "@type": "OpeningHoursSpecification", "dayOfWeek": "Thursday", "opens": "10:00", "closes": "18:00" },
        { "@type": "OpeningHoursSpecification", "dayOfWeek": "Friday", "opens": "08:30", "closes": "16:00" }
      ],
      "priceRange": "$125–$1200",
      "medicalSpecialty": "Psychiatry",
      "knowsAbout": ["EMDR Therapy", "Trauma Therapy", "Perinatal Trauma", "Childhood Trauma", "PTSD"],
      "description": "EMDR therapy and trauma counseling in Dublin, Ohio. Specializing in childhood trauma, perinatal trauma, and EMDR intensives. Erin Zarlino, LPCC.",
      "areaServed": [
        { "@type": "City", "name": "Dublin", "addressRegion": "OH" },
        { "@type": "City", "name": "Columbus", "addressRegion": "OH" }
      ],
      "sameAs": [
        "https://www.instagram.com/wanderingfreecounseling",
        "https://www.psychologytoday.com"
      ]
    },
    {
      "@type": "Person",
      "@id": "https://www.wanderingfreecounseling.com/#erin-zarlino",
      "name": "Erin Zarlino",
      "jobTitle": "Licensed Professional Clinical Counselor",
      "hasCredential": {
        "@type": "EducationalOccupationalCredential",
        "credentialCategory": "LPCC",
        "recognizedBy": { "@type": "Organization", "name": "Ohio Counselor, Social Worker and Marriage and Family Therapist Board" }
      },
      "worksFor": { "@id": "https://www.wanderingfreecounseling.com/#organization" },
      "url": "https://www.wanderingfreecounseling.com"
    },
    {
      "@type": "WebSite",
      "@id": "https://www.wanderingfreecounseling.com/#website",
      "url": "https://www.wanderingfreecounseling.com",
      "name": "Wandering Free Counseling, LLC",
      "publisher": { "@id": "https://www.wanderingfreecounseling.com/#organization" }
    }
  ]
}
```
