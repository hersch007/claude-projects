# Wireless LMS Project — Master Brief
**Company:** Wireless Tower Solutions (WTS) · wirelesstowersolutions.com  
**Product:** Application Management Platform (AMP)  
**Project:** AMP Online Training LMS + AI Chatbot  
**President:** Mel Patterson · Based in South Carolina

---

## 1. What This Project Is

WTS provides professional services and a SaaS platform (AMP) that helps **local governments** (cities, counties, tribal, federal agencies) manage the permitting and inspection of wireless towers and facilities. AMP replaces paper/email applications with a structured, fully online workflow from initial permit application through final on-site inspection.

This project builds:
1. **An LMS** (TutorLMS on WordPress) that trains AMP users across all roles
2. **An AI chatbot** (AMP Advisor / AMP Assistant) embedded on the WTS WordPress site that answers questions about AMP in real time

---

## 2. The AMP Platform

### What AMP Does
- Centralized online application management for wireless facility permits
- Role-based access: each user type sees only what they need
- Complete audit trail — all data, communication, and decisions stored as the **Official Record** (federal requirement)
- Enforces the **Shot Clock** — federal deadline countdown for jurisdiction review
- Integrates with QuickBooks (billing), 2FA providers (security), and data backup services

### Technology Stack
- WordPress (WTS website host)
- TutorLMS (LMS delivery)
- WPCode plugin (PHP snippet injection for chatbot backend)
- Insert Headers and Footers plugin (widget HTML/JS injection)
- Anthropic API — `claude-sonnet-4-6`, max 600 tokens per response
- ThreadTrac (bug/issue reporting tool — assign to Paul Dubinsky, Lead Dev)

---

## 3. User Roles

| Role | Also Called | Authority | Key Responsibility |
|------|-------------|-----------|-------------------|
| **Client** | Jurisdiction | **FINAL approval authority** | Review apps, approve/reject, issue permits |
| **Agent** | Carrier Representative | No approval authority | Submit apps, upload docs, respond to revisions |
| **Analyst** | Client Representative (WTS staff) | Supports decisions only | Review completeness, flag issues, assist Clients |
| **Analyst in Training (AIT)** | — | Requires Analyst sign-off | Same as Analyst but needs second approval |
| **Admin** | System Administrator | No approval role | Manage users, configure system, generate reports |

**Critical rule:** Only the Jurisdiction (Client) can approve. Agents submit, Analysts support — neither can approve.

---

## 4. Core Application Components

Every project starts with the PIF. Additional components are triggered based on project type.

| Component | Full Name | Required For |
|-----------|-----------|--------------|
| **PIF** | Project Information Form | ALL projects — defines type, location, scope |
| **ATF** | Add Tower Form | Any project adding a new tower site to the WTS database |
| **NWF** | New Wireless Facility | New Tower projects only — provides justification |
| **TWF** | Tower/Wireless Facility Registration | When tower data is not current in the database |
| **NCM** | New Colocation/Modification | ALL project types — structural safety data |

**Component count by project type:**
- New Tower: 3 additional components (NWF + TWF + NCM)
- Colocation/Modification (existing structure, known in DB): 2 components
- Colocation/Modification (not in DB): 3 components
- All Small Wireless Facilities (SWF): 3 components (up to 25 sites per application)

---

## 5. PIF Post-Approval Workflow (memorize this sequence)

After PIF is submitted and approved, this EXACT sequence occurs:
1. AMP defines the project type based on PIF data
2. The appropriate site and jurisdiction are confirmed
3. Required components are identified (NWF, TWF, NCM, etc.)
4. Application fees are calculated
5. An invoice is generated and sent to the Agent
6. **Agent can now begin filling out ALL required components immediately — no waiting required** ← Platform Update (May 2026)
7. Payment processing may take several days — agent works on components in parallel
8. Components can be fully completed and saved, but **CANNOT be submitted until payment is confirmed**
9. Once payment is confirmed, submission unlocks and the application continues through review

> **Platform Update — "A Faster Path to Approval" (May 26, 2026)**
> AMP was updated to allow agents to open and complete all components immediately after PIF approval,
> rather than waiting for payment to clear. This reduces total processing time significantly.
> Reference: `docs/WTS_AMP System Announcement_05262026.png`

**Critical SAVE reminder (from official announcement):**
> SAVE — SAVE — SAVE! After EVERY entry and EVERY upload, click SAVE.
> If you do not SAVE, your information will be lost and cannot be recovered.
> Do not navigate away without saving.

**If an agent cannot SUBMIT components → payment has not yet been confirmed. This is expected — they should continue saving their work.**

---

## 6. Agent Registration Process

Source: `docs/reigstration process.pdf` (email from Mel Patterson to Richard Brashear, May 26, 2026, with screenshots)

### Step-by-Step Registration Flow

**Step 1 — Launch AMP**
Navigate to the AMP On-line Application Process page → two buttons: **Login** (existing users) | **Register** (new users)

**Step 2 — Complete the Agent Registration Form**
Fields required:
- Date (auto-filled)
- Agent First Name / Last Name
- Agent's Phone Number
- Agent's Email + Confirm Agent's Email
- Agent's Company Name
- Agent's Company Mailing Address (3 address lines + City / State / Zipcode)
- **Text Capable Mobile Number** ← Required for 2-Factor Authentication (country code defaults to 1)
- View and agree to the **Agent User Agreement** (click "View" button, then submitting = acceptance)
- Click **Submit**

**Step 3 — Confirmation Screen**
After submitting, the agent receives a confirmation screen showing:
- **User ID = their email address**
- **Temporary password** (system-generated)
- Instruction to copy/save the password securely
- Notice: "You will be contacted by email when your registration has been approved by Wireless Tower Solutions"

**Step 4 — WTS Approval**
Registration is not instant — WTS reviews and approves each agent registration. Agent receives an email when approved and can then log in.

### Key Registration Notes
- An Agent can register in **multiple jurisdictions**
- The mobile number for 2FA must be **text-capable**
- User ID is always the **email address** (not a username)
- Temporary password should be saved securely — it can be cut & pasted on first login

---

## 7. Project Types

### Traditional Towers
- New Tower
- Modification
- Colocation
- Eligible Facility/Colocation (EFC) — colocates on existing structure per federal law; approval ensured if safe
- Eligible Facility/Modification (EFM) — modifies existing equipment per federal law; approval ensured if safe
- Application Update — modifies a previously approved project before construction

### Small Wireless Facilities (SWF)
- New Small Wireless Facility / System
- Small Wireless Facility / System DAS
- Small Wireless Facility / System Modification
- Small Wireless Facility / System Colocation
- Small Wireless Facility / System Application Update

**Eligible Facility note:** When selected, a panel opens showing rules the Agent must agree to (checkbox required). If not checked, Agent must select a non-EF project type. Contact local government or WTS Analyst if unsure.

---

## 8. Project Status System

### Status Color Reference

| Status | Color | Meaning | Who Acts |
|--------|-------|---------|----------|
| Draft | None | Being created, not submitted | Agent |
| Saved | Teal/Cyan | Created but NOT submitted | Agent must submit |
| Submitted / In Review | Blue | Under active review | Monitor for Jurisdiction response |
| Pending | Yellow | Awaiting next step | Monitor; determine if action needed |
| Invoiced | Orange | Fees issued; payment required | Agent must pay |
| Revise | Red | Requires corrections | Agent must revise and resubmit |
| AIT Revise | Red | AIT track revision needed | Agent must correct |
| Awaiting Revision | Red | Additional info required | Agent must update |
| Approved | Green | Requirements met for this stage | No action needed |
| AIT Approved | Dark Blue | AIT track approved | Proceeds to qualified Analyst sign-off |
| Completed | Green | Final steps verified and closed | No action needed |

**Dashboard reading rule:** NEVER determine project status from a single column. Always scan the ENTIRE ROW left to right to see who owes what at each stage.

**Common mistakes:**
- Reading only one column and assuming project is complete or stuck
- Ignoring red-status projects that need action
- Misunderstanding who is responsible for the next step

---

## 9. Key Regulatory Concepts

| Term | Definition |
|------|-----------|
| **Shot Clock** | Federal countdown — time remaining for Client review before auto-approval. Length varies by project type. |
| **Official Record** | Federal requirement — all data, communication, and decisions in AMP are permanently recorded |
| **Ordinance** | Local law defining rules and info required to complete a wireless facility application |
| **Certified Document** | Reviewed, stamped, and signed by a PE licensed in the state |
| **Performance Bond** | Insurance-like document ensuring carrier fulfills obligations; typically a removal bond |
| **RF Emissions** | Defined and regulated by FCC; local governments verify compliance only — cannot regulate the services |
| **2FA** | Two-Factor Authentication — required for all AMP logins |
| **Final Inspection** | On-site verification that as-built configuration matches approved application |

---

## 10. Content Style & Tone

- **Deep / SOP-style** — thorough step-by-step instruction aligned with real workflows
- **Strong visual guidance** — screenshots with highlights; every lesson has `[INSERT IMAGE]` placeholders with captions
- **Professional, certification-ready tone** — clear, precise, compliance-aware
- No filler content — every sentence should teach something actionable

---

## 10a. WTS Brand Assets & Color Standards

### Logo
- **File:** `assets/WTS-Oval-Logo-Blue.png`
- Oval "wts" mark with sky-blue gradient, "WIRELESS TOWER SOLUTIONS" wordmark beneath
- Use on: document cover pages, combined review docs, course headers
- Background: white only (logo has no background fill)

### Brand Colors
| Color | Hex | Use |
|-------|-----|-----|
| **WTS Cyan** (primary brand) | `#00AEEF` | H3 subheadings, table headers, callout borders, rule lines, cover page accents |
| **Navy Blue** (current docs) | `#1a4f8a` | H1/H2 major headings — keep for readability at large sizes |
| **Dark Teal** (current docs) | `#0d6b8c` | Replace with WTS Cyan `#00AEEF` on next rebuild pass |

> **Note:** The `#00AEEF` cyan is sampled from the WTS website top banner (see `lms/Wireless Tower Solutions home page header for colors.png`). Confirm exact hex with Mel before final production rebuild.

### Color Reference Files
- `lms/Wireless Tower Solutions home page header for colors.png` — website screenshot for color reference
- `assets/WTS-Oval-Logo-Blue.png` — official logo for document use

### Image Placeholder Status
All 6 Course 1 lessons contain `[INSERT IMAGE: ...]` placeholders. When real screenshots are provided:
1. Drop screenshots into `assets/` with descriptive filenames
2. Rerun the lesson build scripts (to be updated with logo + WTS cyan + real image insertions)
3. Rebuild `AMP-Fundamentals-Course1-Complete.docx`

---

## 11. Lesson Format Standard (use this exact structure for every lesson)

```
[Lesson Title]

What You Should Understand After This Lesson
• Bullet list of 3–5 learning objectives

[Main Content Sections with headings]
  - Explanation
  - Examples
  - Visual: [INSERT IMAGE: description] — Caption text
  - Why It Matters / How It Works notes

Key Takeaways
• Bullet list of 3–5 core points

Checkpoint Question
[Scenario-based or multiple choice question]
A) ...
B) ...
C) ...
D) ...
✓ Correct Answer: [X] — [brief explanation]
```

**Non-negotiables:**
- Always open with "What You Should Understand After This Lesson"
- Always close with Key Takeaways + one Checkpoint Question
- Checkpoint questions must be scenario-based or decision-focused (not trivia)
- Mark the correct answer with ✓ and include a one-line explanation

---

## 12. LMS Architecture (TutorLMS)

### Four Courses (Three Public + One Internal)
| Course | Audience | Duration |
|--------|----------|----------|
| **Course 1: AMP Fundamentals** | ALL users (prerequisite) | 60–90 min |
| **Course 2: AMP for Government / Jurisdiction Reviewers** | Clients + Analysts | TBD |
| **Course 3: AMP for Carrier / Agent Representatives** | Agents | TBD |
| **Course 4: AMP QA / Testing** (internal only) | WTS testers — NOT public | TBD |

### Learning Paths
- Government/Jurisdiction users → Course 1 + Course 2
- Agent users → Course 1 + Course 3

### Course 1: AMP Fundamentals — 6 Lessons
| # | Lesson | Status |
|---|--------|--------|
| 1 | What is AMP and Why It Exists | Complete |
| 2 | User Roles & Responsibilities | Complete |
| 3 | Projects, Dashboard & Statuses | Complete |
| 4 | Project Information Form (PIF) | Complete |
| 5 | Component Types (NWF, TWF, NCM, ATF) | Complete — `lms/Lesson-05-Component-Types-NWF-TWF-NCM.md` |
| 6 | Security, Compliance & the Official Record | **Complete** — `lms/Lesson-06-Security-Compliance-Official-Record.md` |

### Instructional Design Approach
- **SOP-Based Learning** — step-by-step aligned with real workflows
- **Decision-Based Training** — approve / revise / escalate scenarios
- **Scenario-Based Learning** — real-world edge cases and incorrect submissions
- **Checklist Integration** — validation checklists per workflow
- **Technical Validation Depth** — structural indicators, certification requirements, safety thresholds

### Assessment Strategy
- Scenario-based quizzes at end of each module
- Full workflow simulation as final assessment
- Immediate feedback with explanation on every quiz answer

---

## 13. Completed Training Content (lms/)

### AMP Introduction Training Guide — 6 Lessons Written
| Lesson | Topic | Files |
|--------|-------|-------|
| Lesson 1 | What Is AMP and Why It Exists | .md + formatted .docx |
| Lesson 2 | User Roles and Responsibilities | .md + formatted .docx |
| Lesson 3 | Projects, Dashboard, and Status | .md + formatted .docx |
| Lesson 4 | Project Information Form (PIF) | .md + formatted .docx |
| Lesson 5 | Component Types (NWF, TWF, NCM, ATF) | .md + formatted .docx |
| Lesson 6 | Security, Compliance & the Official Record | .md + formatted .docx |

Course 1 is complete (6 lessons). All 6 lessons rebuilt in deep SOP format with .md + formatted .docx.

---

## 14. Chatbot — AMP Advisor / AMP Assistant

### Architecture
- **Backend:** `chatbot/wts-amp-ai-chatbot.php` — WordPress PHP snippet via WPCode
- **Frontend:** `chatbot/wts-amp-chatbot-widget.html` — floating widget via Insert Headers and Footers

### Features
- Role selector (Agent / Client / Analyst) on open
- In-widget Glossary panel with search (35 terms embedded in JS)
- 6-message conversation history passed to API
- Rate limiting: 40 messages / IP / hour (WordPress transients)
- DB logging: `wp_wts_chat_logs` table (session, IP, role, message, response)
- Admin log viewer with CSV export at WP Admin → AMP Chat Logs
- Nonce security (WordPress `wp_verify_nonce`)
- Typing indicator with animated dots

### System Prompt Knowledge Base Priority
1. WTS Glossary (xlsx + docx) — single source of truth for all terminology
2. Platform User Guide Outline — user roles, workflows, best practices
3. AMP Online Training LMS Specs — course structure, training design
4. AMP Testing Manual — testing procedures, field guidelines
5. All other AMP documentation

### Chatbot Rules
- Never speculate or hallucinate features
- Out-of-scope → "Please contact your WTS Analyst or WTS support representative"
- Role-aware responses (ask role if unknown)
- Always cite source (e.g., "Per the official AMP Glossary...")
- Never give legal advice — direct to local ordinance or WTS Analyst
- Always include dashboard reading rule when explaining status

---

## 15. File Structure

```
Wireless-LMS-Project/
├── CLAUDE.md                          ← This file
├── assets/                            ← Screenshots and branding
│   ├── Login Screen for AMP Wireless Permit Processing System.png
│   ├── Opening Launch Screen.png
│   ├── Project Information Form (PIF) for AMP System.png
│   ├── amp-dashboard-overview.png
│   ├── amp-dashboard-scenario-example.png
│   └── amp-dashboard-status-highlight.png
├── chatbot/                           ← Chatbot code
│   ├── wts-amp-ai-chatbot.php         ← WordPress PHP backend (WPCode)
│   └── wts-amp-chatbot-widget.html    ← Floating widget HTML/CSS/JS
├── context/                           ← Reference documents
│   ├── AMP Online Training LMS Specs.docx
│   ├── AMP PROPOSAL.pptx.pdf
│   ├── AMP TESTING MANUAL.docx
│   ├── AMP User Guide Introduction_D2(1).docx
│   └── Local Government & Wireless Facilities White Paper.docx
├── docs/                              ← Core platform documentation
│   ├── Glossary and Fields Definition by Screen.docx
│   ├── Platform User Guide ROUGH Outline 06052025 Mel Comments 06072025.docx
│   └── WTS Glossary_10Oct-2025.xlsx
├── lms/                               ← Training content
│   ├── AMP-Introduction-Training-Guide 1st 4 Lessons.docx  ← Lessons 1–4 complete
│   └── Outline of AMP Documentation from a User Perspective.docx
└── outputs/                           ← Generated exports (empty)
```

---

## 16. What's Next (Prioritized)

| Priority | Task | Notes |
|----------|------|-------|
| ~~1~~ | ~~Build Lesson 5: Component Types (NWF, TWF, NCM)~~ | ✓ Complete |
| ~~1~~ | ~~Build Lesson 6: Security, Compliance & the Official Record~~ | ✓ Complete |
| ~~1~~ | ~~Rebuild Lessons 1 & 2 in deep SOP format~~ | ✓ Complete |
| 2 | Write Course 2 full content (Jurisdiction Reviewers) | User Guide Outline + Training Guide |
| 3 | Write Course 3 full content (Agent Representatives) | User Guide Outline + Glossary |
| 4 | Expand chatbot glossary (currently 35 terms; full glossary in xlsx) | WTS Glossary_10Oct-2025.xlsx |
| 5 | Write scenario-based quiz questions for each lesson | Based on completed lesson content |
| 6 | Build TutorLMS course structure in WordPress | LMS Specs Appendix E |
| 7 | Internal AMP QA/Testing course (separate, not public) | AMP TESTING MANUAL.docx |

---

## 17. Terminology Cheat Sheet

| AMP Term | Meaning |
|----------|---------|
| Client | Local jurisdiction / government (NOT the wireless carrier) |
| Agent | Wireless carrier representative (submits applications) |
| Analyst | WTS staff who supports Client review |
| AIT | Analyst in Training (needs qualified Analyst co-approval) |
| PIF | Project Information Form (always the first step) |
| NCM | New Colocation/Modification (required for ALL project types) |
| SWF | Small Wireless Facility (5G-focused, up to 25 sites per app) |
| EFC | Eligible Facility Colocation (federal fast-track approval) |
| EFM | Eligible Facility Modification (federal fast-track approval) |
| Shot Clock | Federal deadline countdown for Client review |
| Official Record | AMP database = permanent legal record of all activity |
| WTF | Wireless Tower Facility (the physical structure) |
