# Lesson 4: The Project Information Form (PIF)

**Course:** AMP Fundamentals
**Version:** 1.0 | 2026
**Preceding Lesson:** Lesson 3 — Projects, Dashboard, and Status
**Following Lesson:** Lesson 5 — Component Types (NWF, TWF, NCM, ATF)

---

## What You Should Understand After This Lesson

- What the PIF is and why it is the starting point for every project in AMP
- What information the PIF collects — and why each section matters
- How the PIF controls the entire application workflow from submission through approval
- The complete post-approval sequence and what happens at each step
- Why accuracy in the PIF is critical — and what goes wrong when it isn't

---

## Overview

Every project in AMP begins with the Project Information Form — the PIF. Before any review can happen, before any components are activated, before any fees are calculated, the PIF must be completed and approved.

The PIF is not simply an intake form. It is the decision point that defines everything that follows. The information entered into the PIF tells AMP what type of project this is, where it is located, who is responsible for it, and what regulatory pathway it must follow. Every component, every review, every fee, and every approval step that comes afterward is a direct result of what was entered in the PIF.

> **Key Point:** Get the PIF right, and the rest of the process flows efficiently. Get it wrong, and every step that follows is affected — wrong components, wrong fees, wrong reviewer, and avoidable delays.

---

## What the PIF Collects

The PIF is organized into five core areas. Each area collects a distinct category of information.

### Project Identification

This section establishes the basic identity of the project in the AMP system and in the WTS database.

- **Project Carrier Name** — the wireless carrier associated with the application
- **Applicant's Project Name** — the name the Agent assigns to this project
- **Applicant's Project Number** — the internal project number used by the Agent's organization
- **WTS Project Number** — a system-generated six-digit number assigned by AMP; this is the project's unique identifier in the WTS database and is consistent across all users who have access to the project
- **Project Description** — a brief description of what is being proposed

> **Note:** The WTS Project Number is assigned by the system — the Agent does not enter this. It becomes the official reference number for all communication and recordkeeping on this project.

### Agent and Carrier Information

This section identifies the Agent submitting the application and the carrier they represent.

- Applicant's Project Manager (name, email, phone)
- Agent's Company Name
- Applicant's Agent (name)
- Agent ID
- Agent's Phone Number and Email
- Agent's Company Address (street, city, state, zip code)

> **Why it matters:** This information establishes the legal identity of the party submitting the application. It is the basis for all communication, notifications, and invoice delivery throughout the project lifecycle.

### Support Structure Information

This section identifies the company and contact responsible for the physical tower structure.

- Support Structure Company's Project Manager (name, email, phone)

> **Why it matters:** The tower owner or structure manager is a separate party from the wireless carrier in many cases. This contact is critical for coordination during structural review, inspection scheduling, and access to the site.

### Project Type Selection

**This is the most critical decision in the entire PIF.**

The Project Type field tells AMP exactly what kind of application this is. AMP uses this selection to determine:
- Which components are required
- What fees apply
- Which regulatory pathway the project follows
- How the Shot Clock is calculated

There are two broad categories of project type:

#### Traditional Towers

| Project Type | Description |
|---|---|
| New Tower | A brand-new tower structure where no existing facility or structure currently exists |
| Modification | An existing site being updated, reconfigured, or physically altered |
| Colocation | New antennas placed on an existing wireless tower |
| Eligible Facility / Colocation (EFC) | Colocation on an existing structure that meets federal law requirements — ensured approval if the structure remains safe |
| Eligible Facility / Modification (EFM) | Modification of equipment on an existing structure that meets federal requirements — ensured approval if the structure remains safe |
| Application Update | Modifies a previously approved project before actual construction begins |

#### Small Wireless Facilities (SWF)

| Project Type | Description |
|---|---|
| New Small Wireless Facility / System | A new SWF installation meeting federal definitions of a small site |
| Small Wireless Facility / System DAS | A Distributed Antenna System configuration |
| Small Wireless Facility / System Modification | Modification of an existing SWF |
| Small Wireless Facility / System Colocation | Colocation on an existing SWF structure |
| Small Wireless Facility / System Application Update | Update to a previously approved SWF application |

> **SWF Note:** Small Wireless Facilities are specifically designed to support 5G technologies. Up to 25 sites can be included on a single SWF application. All SWF project types require three components.

#### Eligible Facility Types — Special Guidance

Many Colocation or Modification projects may qualify as an Eligible Facility application. If you are unsure whether your project qualifies, select the Eligible Facility option and verify the requirements.

When you select an Eligible Facility project type, a panel opens displaying the federal rules that must be met. You must check the **"Click to Accept"** checkbox to confirm your project qualifies. If you cannot check this box, you must select a standard (non-Eligible Facility) project type instead.

> **Critical:** Selecting the correct Project Type is the single most important action in the PIF. An incorrect project type will trigger the wrong components, generate incorrect fees, and potentially delay or invalidate the entire application. If you have any doubt, contact your local government or your WTS Analyst before submitting.

> **Visual:** [INSERT IMAGE: PIF form showing the Project Type dropdown with Traditional Tower options visible]
> *Caption: The Project Type dropdown is the most critical field in the PIF. It determines every component, fee, and workflow step that follows.*

### Tower Owner / Site Information

This section identifies the physical location of the tower and connects the project to the correct site in the WTS database.

- **Tower Owner Site ID or Unique Name** — a unique identifier used by the Agent to distinguish different tower sites. Note: these names are not regulated or reviewed by the municipality; the same site may be referenced by different names by different agents.

> **If the tower site does not yet exist in the WTS database:** The Agent must complete the Add Tower Form (ATF) to register the new site before or alongside the PIF submission.

---

## How the PIF Controls the Workflow

Once the PIF is submitted, it does not just create a record — it actively drives the entire application process. Here is what the PIF controls:

**Which components are required**
The project type selected in the PIF determines exactly which components AMP activates. A New Tower project triggers NWF, TWF, and NCM. A simple Colocation on a known structure triggers only the NCM. The PIF makes this determination automatically.

**What fees apply**
Application fees are calculated based on the project type and jurisdiction defined in the PIF. The invoice sent to the Agent is generated directly from PIF data.

**Which reviewer is assigned**
The jurisdiction entered in the PIF determines which Client (local government) is responsible for reviewing the application. A wrong jurisdiction means the wrong government entity receives and reviews the application.

**How the Shot Clock is calculated**
Federal time limits (Shot Clock) for review vary by project type. The PIF's project type selection determines which Shot Clock applies and when it begins counting.

**What the Official Record contains**
All PIF data becomes part of the permanent Official Record in AMP. Once submitted, this data cannot simply be changed — revisions require a formal revision cycle.

> **Visual:** [INSERT IMAGE: PIF workflow diagram showing PIF → Invoice → Components → Review → Approval]
> *Caption: The PIF is the gateway. Every downstream action — components, fees, review, approval — flows from what was entered here.*

---

## PIF Post-Approval Sequence

After the PIF is submitted and approved, the following sequence occurs in this exact order:

1. **AMP defines the project type** based on PIF data
2. **The appropriate site and jurisdiction are confirmed**
3. **Required components are identified** (NWF, TWF, NCM, etc.)
4. **Application fees are calculated**
5. **An invoice is generated and sent to the Agent**
6. **Agent begins filling out all required components immediately** — no waiting required *(Platform Update, May 2026)*
7. **Payment processing occurs** — may take several days depending on payment method
8. **Submission unlocks once payment is confirmed** — components cannot be submitted for review until payment clears
9. **Agent submits completed components** — Analyst and Jurisdiction review begins

> **Platform Update — "A Faster Path to Approval" (May 2026)**
> AMP was updated so agents can open and begin completing all required components as soon as the PIF is approved and the invoice is issued — without waiting for payment to clear. This eliminates processing delays during payment. However, no component can be submitted for Jurisdiction review until payment is confirmed.

> **If an Agent says their PIF was approved but they cannot SUBMIT their components:** The invoice has not been paid yet. This is expected and correct. They should continue saving their work and submit once payment confirms.

> **SAVE Reminder:** After EVERY entry and EVERY upload, click SAVE. Do not depend on auto-save. Information that is not saved cannot be recovered.

> **Visual:** [INSERT IMAGE: AMP screen showing invoice status Orange/Invoiced with components visible but submit button locked]
> *Caption: After PIF approval, components are visible and fillable. The Submit button remains locked until payment is confirmed.*

---

## Why Accuracy Matters — Common PIF Errors

Because the PIF drives everything that follows, errors here have cascading consequences throughout the application. These are the most common PIF mistakes and their impact:

**Wrong Project Type**
This is the most damaging PIF error. Selecting "New Tower" for a Colocation project activates NWF and TWF components that don't belong, generates incorrect fees, and places the application on the wrong regulatory pathway. The project may need to be restarted entirely.

**Wrong Jurisdiction**
If the wrong local government is selected, the application is routed to the wrong reviewer. This creates delays, confusion, and may violate federal Shot Clock timing requirements.

**Incorrect or Missing Project Manager Contact**
All system notifications — including invoice delivery, revision requests, and approval notices — are sent to the contacts entered in the PIF. Missing or incorrect contact information means critical communications go to the wrong person or nowhere at all.

**Selecting Standard Type When Eligible Facility Applies (or Vice Versa)**
Filing a standard Colocation when the project actually qualifies as an Eligible Facility means losing the federal streamlined approval guarantee. Filing as Eligible Facility when the project doesn't qualify means the application will fail the eligibility check during review.

**Not Using the ATF When the Tower Is New**
If the tower site does not exist in the WTS database and the Agent does not complete the ATF, the PIF cannot be properly linked to a registered tower site. This creates a gap in the Official Record and will trigger a revision.

> **Visual:** [INSERT IMAGE: Example of a PIF with the Project Type field highlighted in red indicating an incorrect selection]
> *Caption: An incorrect project type in the PIF triggers wrong components and fees. This single field controls more of the workflow than any other in AMP.*

---

## Key Takeaways

- The PIF is the mandatory starting point for every project in AMP — no project begins without it
- The Project Type field is the most critical decision in the PIF — it controls components, fees, reviewer assignment, and Shot Clock calculation
- The WTS Project Number is system-generated and becomes the official identifier for the project across all users
- After PIF approval, agents can begin filling out all components immediately — but cannot submit until payment is confirmed
- Wrong information in the PIF creates cascading errors throughout the entire application process
- Always confirm the correct project type before submitting — contact your WTS Analyst if unsure
- All PIF data becomes part of the permanent Official Record and requires a formal revision cycle to change

---

## Checkpoint Question

**Scenario:** An Agent submits a PIF selecting "Colocation" as the project type. After approval, they notice that their project actually qualifies as an Eligible Facility Colocation (EFC) under federal law. The Agent asks: *"Can I just keep going with the Colocation type? It's basically the same thing."*

What is the correct response?

**A)** Yes — Colocation and Eligible Facility Colocation are processed the same way in AMP. The project type doesn't matter once the PIF is approved.

**B)** No — the project type must be corrected. An EFC project follows a different regulatory pathway and carries a federal guarantee of approval that a standard Colocation does not. Proceeding with the wrong type could expose the jurisdiction to a legal challenge.

**C)** It depends on the jurisdiction. Some jurisdictions accept either project type for the same application.

**D)** The Agent should just add the Eligible Facility documentation to the NCM and it will be treated as an EFC automatically.

---

**✓ Correct Answer: B** — Project type is not interchangeable in AMP. An Eligible Facility Colocation follows a federally protected pathway with a guaranteed approval outcome (if the structure remains safe), which a standard Colocation does not. Filing under the wrong type removes this protection, can trigger the wrong review process, and could expose the jurisdiction to legal challenge from the carrier. The PIF must be corrected through a formal revision before the application proceeds.

---

*End of Lesson 4 | Next: Lesson 5 — Component Types (NWF, TWF, NCM, ATF)*
