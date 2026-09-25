# WFC Month 1 Work Plan — Website + SEO Support ($99)

**Client:** Wandering Free Counseling, LLC (Erin Zarlino, LPCC)
**Source:** `WFC-SEO-AUDIT-2026-09-01.md` (baseline **47/100**)
**Service month 1:** Effective Date → day before the same date next month (see `ONBOARDING-TRACKER.md`)
**Page allowance:** up to **3 new pages total across months 1–2**, then up to 2 per month (months 3–12). A page is one standard page of up to ~800 words on an existing Squarespace layout.
**Goal for month 1:** Fix every critical on-page gap, correct the NAP mismatch, get tracking in place, and publish the Meet Erin page. Target score after month 1: **65–70/100**.

---

## Scope check (what the $99 plan covers)

| Audit item | In $99 scope? | Notes |
|---|---|---|
| Meta descriptions, title tags, H1s | ✅ On-page SEO | Works on every Squarespace plan |
| NAP fix + Google Business Profile | ✅ | Needs GBP Manager access |
| Schema (LocalBusiness / Person / Service) | ✅ *if Business plan+* | Needs Code Injection. On Personal, we do what the platform allows (contract platform note) |
| URL typo + 301 redirect | ✅ Technical SEO | Squarespace URL Mappings, all plans |
| GA4 + Search Console | ✅ | Set up under Erin's Google account |
| Image alt text | ✅ | Only images touched during included work |
| New pages (Meet Erin, etc.) | ✅ 3 total in months 1–2 | |
| Expanding perinatal (375 → 800+ words) / childhood trauma pages | ⚠️ Decide | A full rewrite is closer to a page build than an on-page tweak. Count it against the page allowance or quote it at $75 (see Decisions) |
| Blog (2–4 posts/mo in audit) | ❌ | Not in $99. Blog drafts are $50 each, or included in Growth ($149) |
| Competitor research, nearby-city strategy | ❌ | Growth tier. Keep as recommendations in the monthly report |

---

## Week 0 — Before access (now → signature)

- [ ] Send contract + checklist (`EMAIL-Erin-Contract-Welcome.md`); log it in the tracker
- [ ] Pre-write all 6 meta descriptions and 3 title fixes (below) so they're paste-ready
- [ ] Correct the audit schema before use (see **Schema corrections**)
- [ ] Draft a Meet Erin page outline from what's already on the site (3 kids, pregnancy loss story, LPCC, EMDR focus), with placeholders for the details only Erin can give

## Week 1 — Access + quick wins (Priorities 1, 2, 4)

**Priority 1: Meta descriptions (all 6 pages).** Squarespace: Page settings → SEO. Use the audit copy with these edits:
- Perinatal: change "EMDR-certified therapist" → match Erin's confirmed credential (EMDRIA Certified vs. EMDR-trained)
- Childhood trauma: keep "In-person and virtual" (confirmed: in person + video, Ohio only). Where it fits, say "virtual across Ohio"
- EMDR: "Columbus/Dublin" → "Dublin and Columbus, Ohio"

**Priority 2: Title tags**

| Page | Current | New |
|---|---|---|
| /emdr | Wandering Free Counseling, LLC | EMDR Therapy & Intensives in Dublin & Columbus, OH \| Wandering Free |
| /contactme | Healing Starts Here | Contact a Trauma Therapist in Dublin, OH \| Wandering Free Counseling |
| / | …Trauma Therapist in Columbus Ohio | Trauma & EMDR Therapist in Dublin, OH \| Wandering Free Counseling |
| /insurancesandfeees | Insurances and Fees | Insurance & Fees \| Wandering Free Counseling, Dublin OH |

**Priority 4: NAP consistency**
- [ ] Homepage title → Dublin (above). Keep "Columbus" in body copy and meta as the service area, not the address
- [ ] **GBP claim (blocker).** Checked 2026-09-25: the listing shows "Own this business?", so it's probably unclaimed. Erin claims and verifies it at business.google.com, then adds PartsofPractice@gmail.com as Manager. Verification can take days (postcard or video), so start in week 1
- [x] GBP NAP already matches: 5995 Wilcox Pl D, Dublin, OH 43016 · (614) 881-2439. Only the website title is off
- [ ] Once claimed: confirm hours (Wed/Thu/Fri), and add **Psychotherapist** as a secondary category (primary is Counselor today; that's fine to keep)
- [ ] Once claimed: Appointments link currently goes to Psychology Today. Point it to `/contactme?utm_source=gbp&utm_medium=organic` so bookings are tracked, or keep PT if Erin prefers its inbox. Set the Website link with the same UTM
- [ ] Once claimed: add the business description, services (EMDR, EMDR Intensives, Childhood Trauma, Perinatal Trauma), and photos. Reply to the 5 existing reviews in general terms only, never confirming anyone is a client
- [ ] Psychology Today ([profile](https://www.psychologytoday.com/us/therapists/erin-zarlino-dublin-oh/1377982)): confirm the address matches and the profile links to the website

**Also in week 1**
- [ ] Homepage H1: brand-only → "Trauma & EMDR Therapy in Dublin, Ohio" (keep the brand as a subheading or in the logo)
- [ ] Record a baseline before changes: screenshot the current titles and metas, and note GBP views and calls if available

## Week 2 — Tracking + technical

- [ ] GA4: add as Editor, or create the property under Erin's account and connect it in Squarespace (Settings → Developer Tools → External API Keys / Analytics)
- [ ] Search Console: add as Full user, or verify via Squarespace. Submit `sitemap.xml`
- [ ] URL fix: rename `/insurancesandfeees` → `/insurance-and-fees`, then add a URL Mapping `/insurancesandfeees -> /insurance-and-fees 301`. Update nav links and any links on the PT profile and GBP
- [ ] Fix the "Meet Erin Zarlino, LPCC" nav link (currently goes to the homepage). Point it at `/meet-erin` once published
- [ ] Alt text on all perinatal page images and the headshot ("Erin Zarlino, LPCC, trauma and EMDR therapist in Dublin, Ohio")

## Weeks 2–3 — Schema (Priorities 3 + 5)

*Only if Erin is on the Business plan or higher. If she's on Personal, log it in the tracker, note it in the report, and recommend an upgrade. Don't use workarounds.*

- [ ] Homepage: `@graph` with LocalBusiness/MedicalBusiness + Person + WebSite via Settings → Advanced → Code Injection (header), or homepage page-level injection
- [ ] Service schema on /emdr, /childhoodtrauma, /perinatal-trauma (page header code injection)
- [ ] Person schema on /meet-erin once live
- [ ] Validate each page in Google's Rich Results Test and the Schema.org validator. Save screenshots for the report

### Schema corrections (fix before using the audit JSON)

- **Remove `"medicalSpecialty": "Psychiatry"`.** Erin is an LPCC, not a psychiatrist, and this is a YMYL accuracy issue. Use `"@type": ["MedicalBusiness", "LocalBusiness"]` without it, or `"ProfessionalService"`.
- **Quick-schema `worksFor`:** `{ "@name": ... }` is invalid. Use `{ "@id": "https://www.wanderingfreecounseling.com/#organization" }` (as in section 11).
- **`sameAs`:** replace `https://www.psychologytoday.com` with `https://www.psychologytoday.com/us/therapists/erin-zarlino-dublin-oh/1377982`, and add the GBP Maps URL once claimed, plus any other directories she sends.
- **Person `hasCredential`:** add the license number, e.g. `"identifier": "E.1901023"`, keep `recognizedBy` as the Ohio CSWMFT Board, and add `"areaServed": {"@type": "State", "name": "Ohio"}` for telehealth.
- **`logo`:** `/logo.png` is a placeholder. Use the real Squarespace image URL.
- **Person:** add `"image"` (headshot URL), `"url": ".../meet-erin"`, and `"knowsAbout"` once the page exists. Add EMDRIA only if she's certified.
- **Service schema:** add `"@context"`, a `"description"`, and `"provider": { "@id": "...#organization" }`. Set `areaServed` to both Dublin and Columbus.

## Weeks 2–4 — New pages (page allowance: 3 in months 1–2)

| # | Page | Month | Why | Needs from Erin |
|---|---|---|---|---|
| 1 | **/meet-erin**: "Meet Erin Zarlino, LPCC — Trauma & EMDR Therapist in Dublin, OH" | **1** | Biggest E-E-A-T gap on a YMYL site. The nav link already exists and points to the wrong page. Include Ohio license E.1901023 and a link to her license lookup | Bio, EMDR training level, headshot, approval (license ✅) |
| 2 | **/emdr-intensives**: "EMDR Intensives in Columbus & Dublin, Ohio" (90 min / 3 hr / 6 hr, pricing, Friday scheduling, who it's for, FAQ) | **1** (if confirmed) | Audit keyword "EMDR intensives Columbus Ohio" is medium volume and low difficulty. Few local competitors offer 6-hour intensives | Confirm intensives are a top-3 growth priority; insurance vs. self-pay for intensives |
| 3 | **Chosen by Erin's top services** (month 2), e.g. *Birth Trauma Therapy in Dublin, OH*, or the perinatal rewrite | **2** | Held until Erin names her priorities, so the last slot goes to what she wants to grow | Top 2–3 services |

If Erin's priorities don't include intensives, swap page 2 for her #1 service. Drafts go to Erin for clinical accuracy. She has 5 business days to approve or send one round of combined edits.

## Week 4 — Report

- [ ] Re-score the site against the audit rubric. Expected gains: meta descriptions (+up to 20), titles (+8), NAP (+3), URL (+2), schema (+12 if Business plan), Meet Erin/E-E-A-T (+3)
- [ ] First monthly report: what we did, what it means, and what's next (month 2: page 3, perinatal expansion decision, GBP posts, and review collection)
- [ ] Update `ONBOARDING-TRACKER.md` and add a `score-history.json` entry (baseline 47, 2026-09-01)

---

## Decisions to make with Erin

1. **Page 2:** EMDR Intensives page, or her #1 growth service?
2. **Perinatal page expansion (~375 → ~800 words):** use page slot 3, or keep slot 3 for a new page and quote the rewrite at $75?
3. **Squarespace plan:** if she's on Personal, does she want to upgrade to Business for schema?
4. **Reviews:** set up a Google review request process. We give the link and wording; Erin sends it herself, following her ethics rules on soliciting testimonials. Counseling ethics codes restrict review requests from current clients, so let Erin decide.

## Not in month 1 (parked)

- Blog content: out of $99 scope. Offer at $50/post, or Growth tier
- Nearby-city pages (Westerville, New Albany): use month 3+ page allowance if she wants them
- Childhood trauma page expansion (675 → 1,000+ words): month 3+
