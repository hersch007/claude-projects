---
name: new-seo-client
description: Use this skill whenever a new SEO client is being onboarded — when the user shares a new client website URL, says "new client", "new SEO client", "add a client", or "onboard [business name]". This skill fetches the client's website, creates the standard CLIENT-BRIEF.md, a full SEO audit .md file, and two Word documents (master report + prospect report) in the correct client subfolder under clients/, and saves a memory entry for the client. Always use this skill when a new website URL is shared in the context of the SEO Clients Hub project.
---

# New SEO Client Onboarding

When a new SEO client is introduced (via URL, name, or explicit request), execute the full onboarding workflow below. Do not skip any step.

## Step 0: Ask for Agency Details

Before doing anything else, ask the user two quick questions:

1. **Agency name** — "What agency name should appear on the report?" (e.g., Start Advertising, Parts of Practice)
2. **Color scheme** — "What are the two brand colors for the report?" Accept any of:
   - Named colors: "navy and gold", "green and white"
   - Hex codes: "#1F3864 and #F5A623"
   - Or say "use defaults" to use dark navy #1F3864 (primary) + steel blue #2E74B5 (accent)

Wait for the user's answers before proceeding. Store these as:
- `AGENCY_NAME` — the agency display name (used on cover page, header/footer, contact block, file name)
- `PRIMARY_COLOR` — hex code for headings, cover page accent, table header backgrounds (default: #1F3864)
- `ACCENT_COLOR` — hex code for sub-headings, table accents (default: #2E74B5)

If the user gives color names without hex codes, map common ones yourself (e.g., "navy" → #003366, "gold" → #C9A84C, "teal" → #008080). For unusual color names, ask for the hex code.

---

## Step 1: Research the Client Website

Use WebFetch to gather data from the client's website. Fetch at minimum:
- The homepage (`/`) — extract: H1, H2s, meta title, meta description, services, location, CTAs, schema signals, E-E-A-T signals, platform clues
- The blog or news section (if exists)
- 2–3 key service or location pages (try common paths: /services/, /about/, /blog/, /contact/)

Note which pages return 404 — this is itself an important audit finding.

## Step 2: Determine Client Details

From the fetched content, extract:
- **Business name** — the actual brand name (not the domain)
- **Initials** — derive 2–4 letter initials from the business name (e.g., "GroupRB Marketing" → GRB, "Lavender Healing Collective" → LHC, "Start Again Associates" → SAA)
- **Folder name** — use the business name as the folder (e.g., `GroupRB`, `lavender-healing-collective`)
- **Today's date** — use the current date in YYYY-MM-DD format

## Step 3: Create the Client Folder

Create the folder: `clients/[FolderName]/`

## Step 4: Create CLIENT-BRIEF.md

Save as: `clients/[FolderName]/CLIENT-BRIEF.md`

Use this exact format:

```
# CLIENT: [Business Name]

**Website:** [URL]
**Niche:** [1-sentence description of business type and target audience]
**Services:** [Comma-separated list of services]
**Goals:** [Primary SEO/marketing goals based on what you observed]
**Platform:** [CMS — WordPress, Squarespace, Wix, Shopify, etc. — or "Unknown"]
**Location:** [City, State / region served]

Key pages to analyze:
- Homepage (/)
- [List 3–6 key pages found or expected]

Notes: [2–4 sentences of important observations: 404s, E-E-A-T gaps, standout strengths, content gaps, anything the auditor should know before diving deeper.]
```

Keep it concise — this is a reference card, not a report.

## Step 5: Create the Full SEO Audit (.md)

Save as: `clients/[FolderName]/[INITIALS]-SEO-AUDIT-[YYYY-MM-DD].md`

Use the 11-section format below. Be specific and actionable — include actual titles, meta description copy, schema JSON, and keyword tables. Do not use placeholder text.

---

### Audit Template

```
# SEO AUDIT REPORT

**Client:** [Business Name]
**Website:** [URL]
**Audit Date:** [YYYY-MM-DD]
**Auditor:** SEO AI Assistant
**Platform:** [CMS]

---

## 1. Overall SEO Health Score: XX / 100

**Summary:** [One sentence: current state + biggest single opportunity.]

---

## 2. Top 5 Priorities

| # | Priority | Impact | Effort |
|---|---|---|---|
| 1 | [Priority] | High/Medium/Low | High/Medium/Low |
| 2 | | | |
| 3 | | | |
| 4 | | | |
| 5 | | | |

---

## 3. Quick Wins (Do in < 1 Week)

- [ ] [Specific action with exact copy or code where applicable]
- [ ] ...

---

## 4. Medium-Term Recommendations (2–8 Weeks)

- [ ] [Actionable recommendation with enough detail to execute]
- [ ] ...

---

## 5. Long-Term / Strategic Recommendations

- [ ] [Strategic initiative]
- [ ] ...

---

## 6. Technical SEO Analysis

| Element | Status | Notes |
|---|---|---|
| Title Tags | Missing/Present/Optimized | |
| Meta Descriptions | Missing/Present/Optimized | |
| H1 Tags | Missing/Present/Optimized | |
| Heading Hierarchy (H2–H4) | Issues/Clean | |
| Schema / Structured Data | None/Partial/Present | |
| Canonical Tags | Missing/Present | |
| XML Sitemap | Missing/Present | |
| Robots.txt | Missing/Present | |
| Page Speed | Slow/Moderate/Fast | |
| Mobile Friendliness | Issues/Good | |
| HTTPS / SSL | Missing/Present | |
| Broken Links | Yes/No | |
| Image Alt Text | Missing/Partial/Complete | |

**Key Technical Issues:**
- [Issue + recommended fix]
- ...

---

## 7. On-Page SEO & Content Analysis

**Homepage:**
- Title: [Current or "Not confirmed"]
- Meta Description: [Current or "Missing/Not confirmed"]
- H1: [Current or "Missing"]
- Notes: [Observations]

**[Additional pages as available]**

**Content Quality Summary:**
- Word count: [Estimate]
- Keyword targeting: [Strong/Weak/Missing + notes]
- Internal linking: [Strong/Weak + notes]
- CTAs: [Strong/Weak + notes]

---

## 8. Local & E-E-A-T Analysis

**E-E-A-T Signals Present:**
- [x or empty checkbox for each]: Author/team bios, credentials, license numbers, professional affiliations, media mentions, client testimonials, awards, About page

**E-E-A-T Gaps:**
- [Gap + recommended fix]

**Local SEO:**
- Google Business Profile: [Claimed/Unclaimed/N/A/Unknown]
- NAP Consistency: [Notes]
- Geographic targeting in content: [Strong/Weak]
- Local citations / directories: [Notes]

---

## 9. Conversion & User Experience Issues

- **CTAs:** [Assessment]
- **Contact friction:** [Notes]
- **Mobile UX:** [Notes]
- **Page load perception:** [Notes]
- **Trust signals above the fold:** [Present/Missing]
- **Navigation clarity:** [Clear/Confusing + notes]

**Key Fixes:**
- [Fix 1]
- ...

---

## 10. Content & Keyword Strategy Recommendations

**Target Keyword Opportunities:**

| Keyword | Intent | Difficulty | Priority Page |
|---|---|---|---|
| [keyword] | Informational/Commercial | Low/Med/High | [/page] |

**Content Gap Analysis:**
- [Missing topic/page type + why it matters]

**Recommended Content Pieces:**
1. "[Title idea]" — targets [keyword], serves [audience intent]
2. ...

---

## 11. Next Steps & Action Plan

1. **Week 1 — [Owner]:** [Specific task]
2. ...

---

## Appendix

### Suggested Title Tags
| Page | Recommended Title Tag |
|---|---|
| Homepage | `[copy]` |

### Suggested Meta Descriptions
| Page | Recommended Meta Description |
|---|---|
| Homepage | `[copy]` |

### Schema Code
[Include LocalBusiness JSON-LD and any other relevant schema with actual values filled in]

### Additional Notes
[Tools recommended, platform-specific notes, anything else.]
```

---

## Step 6: Create the Master Report Word Document

Save as: `clients/[FolderName]/[INITIALS]-SEO-Report-[YYYY-MM-DD]-[AGENCY_NAME_SLUG]-MASTER.docx`

Where `AGENCY_NAME_SLUG` is the agency name with spaces removed (e.g., "Start Advertising" → `StartAdvertising`, "Parts of Practice" → `PartsofPractice`).

This is the full technical audit formatted as a professional Word document. It mirrors the .md audit content exactly but is styled for client delivery.

### How to generate it

Write a Node.js script to `clients/[FolderName]/gen-master.js`, then run it with `node`, then delete the script.

Use `docx` npm package (`npm install -g docx` if needed). Use US Letter size (12240 × 15840 DXA), 1-inch margins, Arial font.

### Cover Page

- Top: Business name in large bold text (36pt, `PRIMARY_COLOR`)
- Line 2: "SEO Audit Report" (24pt, gray #595959)
- Line 3: Business tagline or niche (if available, 14pt italic)
- Line 4: Website URL (12pt, hyperlink style)
- Spacer
- "Audit Date: [YYYY-MM-DD]" | "Platform: [CMS]" | "Prepared by: [AGENCY_NAME]"
- Page break

### Body: All 11 Sections

Reproduce every section from the .md audit in full. Use these styles:
- Section headings (e.g. "1. Overall SEO Health Score"): Heading 1, `PRIMARY_COLOR`, 16pt bold
- Sub-headings: Heading 2, `ACCENT_COLOR`, 13pt bold
- Body text: Normal, Arial 11pt, black
- Tables: Use light-tinted header row derived from `PRIMARY_COLOR` at ~15% opacity (ShadingType.CLEAR), alternating white/light gray rows (#F2F2F2) for readability, all borders CCCCCC, cell margins top/bottom 80, left/right 120
- Bullet lists: Use LevelFormat.BULLET with proper numbering config (never unicode bullets)
- Numbered lists: Use LevelFormat.DECIMAL
- Code blocks (schema JSON): Monospace Courier New 9pt, light gray background (#F5F5F5), left-indented paragraph
- Checkboxes in Quick Wins / action items: render as "☐ " prefix using TextRun (this is display text, not a form field)

### Header / Footer

- Header: "[Business Name] — SEO Audit Report | [AGENCY_NAME]" — right-aligned, 9pt gray
- Footer: Left: "Confidential — Prepared Exclusively for [Business Name]" | Right: page number — both 9pt gray
- Use tab stops (not tables) for two-column footer layout

### Appendix

Include all three appendix tables (Title Tags, Meta Descriptions) and the full schema JSON code block.

---

## Step 7: Create the Prospect Report Word Document

Save as: `clients/[FolderName]/[INITIALS]-SEO-Prospect-Report-[YYYY-MM-DD].docx`

This is a shorter (3–4 page), client-facing sales document designed to be sent to the prospect. It is NOT a technical deep-dive — it is a compelling narrative summary that makes the case for hiring [AGENCY_NAME]. Tone: professional, empathetic, results-oriented. No jargon overload.

### How to generate it

Write a Node.js script to `clients/[FolderName]/gen-prospect.js`, run it, then delete it.

### Cover Page

- Business name (large, bold, `PRIMARY_COLOR`, 36pt)
- "SEO Audit Report" (24pt, gray)
- "Prepared by [AGENCY_NAME]" (14pt)
- Month + Year (e.g. "June 2026", 12pt)
- "Confidential — Prepared Exclusively for [Business Name]" (10pt italic gray)
- Page break

### Section 1: SEO Health Score

- Heading: "Your SEO Health Score: XX / 100" (Heading 1 style, `PRIMARY_COLOR`)
- 2–3 sentence narrative paragraph: Start with what's working, then pivot to what's holding them back, end with "the good news: these are fixable." Do not be harsh — be encouraging and specific.

### Section 2: What We Found

- Heading: "What We Found" (Heading 1)
- Intro line: "Our audit identified issues across [N] key areas. Each one is currently costing you visibility."
- Table with 3 columns: **Issue Area** | **Finding** | **Impact**
  - Header row: `PRIMARY_COLOR` background, white bold text
  - 5–6 rows drawn from the audit's top findings
  - Keep findings concise — one sentence per cell
  - Impact column: plain language like "Clients may not find you" not technical jargon

### Section 3: Where You're Losing Clients Right Now

- Heading: "Where You're Losing Clients Right Now" (Heading 1)
- 3–4 sub-sections, each with a bold sub-heading (Heading 2, steel blue) and a short narrative paragraph (2–4 sentences)
- Draw from the audit's most impactful issues (e.g. Local Search, Key Specialties, First Impressions in Search Results)
- Write in second person ("you", "your business") — speak directly to the client

### Section 4: What's Working In Your Favor

- Heading: "What's Working In Your Favor" (Heading 1)
- 4–6 bullet points of genuine strengths identified in the audit
- Keep tone positive and specific — reference actual findings, not generic praise

### Section 5: The Opportunity Ahead

- Heading: "The Opportunity Ahead" (Heading 1)
- 2–3 sentence paragraph describing the market opportunity specific to this client's niche and geography
- Name 2–3 specific underserved keywords or content gaps
- End with a forward-looking, optimistic statement about what's possible

### Section 6: Recommended Next Steps

- Heading: "Recommended Next Steps" (Heading 1)
- Intro: "Based on our audit, we have identified three tiers of work:"
- Three sub-sections, each as a Heading 2 with a bullet list:
  - **Quick Wins** — 3–4 items, things achievable in the first week
  - **Medium-Term** — 3–4 items, 2–8 week initiatives
  - **Longer-Term** — 2–3 items, strategic investments

### Section 7: Let's Talk (Closing CTA)

- Heading: "Let's Talk" (Heading 1)
- 2–3 sentence paragraph: reference the audit date and website audited, invite them to discuss findings, keep it warm and non-pressured
- Then display contact block using the user's agency contact details. Ask the user for these details if not already known (name, phone, email, website). Style the contact block slightly larger (12pt) with a left border or shading box using `PRIMARY_COLOR` to make it stand out.

### Design Notes for Prospect Report

- Use the same header/footer style as the Master Report
- No code blocks, no JSON, no technical element tables — this is NOT a technical document
- Keep the whole document to 3–5 pages maximum
- Paragraphs should be short (2–4 sentences) — white space is your friend
- Every section should feel like it's building a case: "here's the problem → here's what it costs you → here's the solution → here's how to start"

---

## Step 8: Save a Memory Entry

Save a project memory entry to the project memory folder noting the new client, their URL, their folder name, and today's onboarding date.

## Step 9: Confirm to the User

Tell the user:
- All four files created (CLIENT-BRIEF.md, .md audit, Master .docx, Prospect .docx) with file paths as markdown links
- The SEO Health Score
- The top 3 priorities in brief

Do not reproduce the full audit in chat — it's saved to disk. Just give the headline summary.

---

## Scoring Guide (SEO Health Score)

Use this rubric to calibrate the score out of 100:

| Area | Max Points |
|---|---|
| Technical foundation (HTTPS, sitemap, robots, no 404s, page speed) | 20 |
| On-page optimization (titles, meta, H1s, headings, alt text) | 20 |
| Content depth and quality (word count, keyword targeting, internal linking) | 20 |
| E-E-A-T signals (bios, credentials, testimonials, About page) | 20 |
| Local SEO (GBP, NAP, citations, geographic targeting) | 10 |
| Schema / structured data | 10 |

Deduct points for each gap found. Be honest — a score of 50–65 is typical for a small business site with no SEO work done. Reserve 80+ for genuinely well-optimized sites.
