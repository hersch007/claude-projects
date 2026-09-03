# Lesson 2: User Roles and Responsibilities

**Course:** AMP Fundamentals
**Version:** 1.0 | 2026
**Preceding Lesson:** Lesson 1 — What Is AMP and Why It Exists
**Following Lesson:** Lesson 3 — Projects, Dashboard, and Status

---

## What You Should Understand After This Lesson

- All five user roles in AMP and what each role is responsible for
- What each role can see, submit, and approve — and what they absolutely cannot do
- Why final approval authority belongs exclusively to the Jurisdiction and cannot be delegated
- How roles interact with each other across the application workflow
- The most common role-related mistakes — and exactly why they cause problems

---

## Overview

AMP serves multiple stakeholders — and each one has a different relationship to the application process. Local government reviewers, carrier representatives, WTS professional staff, staff in training, and system administrators all use the same platform, but they see different data, take different actions, and carry different levels of authority.

This is not accidental. Role-based access control is a core design principle of AMP. It protects confidential application data, enforces proper regulatory authority, creates separation of duties, and ensures the Official Record accurately reflects who did what and when.

Understanding the roles is not just background knowledge. Every action you take in AMP — or fail to take — has consequences that ripple through the workflow for every other user. Misunderstanding a role leads to misrouted applications, missed deadlines, incorrect assumptions about authority, and revision cycles that could have been avoided.

> **Key Point:** There are five user roles in AMP: Client (Jurisdiction), Agent, Analyst, Analyst in Training (AIT), and Admin. Each has a defined scope of authority. Only one role — the Client (Jurisdiction) — can approve an application. This is a non-negotiable legal and regulatory fact, not a system setting.

---

## Role Summary

| Role | Also Called | What They Do | Approval Authority |
|---|---|---|---|
| **Client** | Jurisdiction | Review applications, approve/deny/condition, track deadlines | **FINAL — exclusive authority** |
| **Agent** | Carrier Representative | Create projects, submit applications, upload documents, respond to revisions | None |
| **Analyst** | Client Representative | Review for completeness, flag issues, advise Clients | Advisory only — no final authority |
| **AIT** | Analyst in Training | Same as Analyst | None independently — requires qualified Analyst co-sign |
| **Admin** | System Administrator | Manage users, configure system, generate reports | None — not part of approval process |

> **Critical Rule:** Only the Jurisdiction (Client role) can approve. Agents submit. Analysts support. Neither can approve. The system enforces this technically — it is not overridable.

---

## Role 1: Client (Jurisdiction)

### Who They Are

The Client is the local government entity responsible for reviewing and making decisions on wireless facility applications. This can be:
- A city or municipality
- A county government
- A tribal government
- A federal agency with local siting jurisdiction

Individual Client users in AMP are typically staff assigned to the planning, zoning, or permitting department who have been given access to the system.

### What the Jurisdiction Does

The Jurisdiction is the final decision-maker in the AMP workflow. Their responsibilities include:

- **Reviewing submitted applications** — evaluating whether all required information and documents have been provided
- **Evaluating regulatory compliance** — confirming the application meets the requirements of the local ordinance and applicable federal law
- **Requesting revisions** — sending the project back to the Agent when corrections or additional information are needed (changes status to Awaiting Revision — red on the dashboard)
- **Approving applications** — issuing the formal approval that allows the project to proceed
- **Issuing conditional approvals** — approving with specific conditions the carrier must satisfy
- **Monitoring the Shot Clock** — ensuring every active project is acted on before the federal deadline expires
- **Conducting or scheduling final inspections** — verifying the built project matches the approved application

### What the Jurisdiction Cannot Do

- Approve an application without a documented basis in the Official Record
- Impose local RF emissions standards (can only verify FCC compliance)
- Deny an application on the basis of RF emissions concerns where the carrier demonstrates FCC compliance
- Unreasonably discriminate between competing carriers
- Prohibit wireless service in their jurisdiction
- Ignore the Shot Clock — inaction after the Shot Clock expires results in deemed approval by federal law

### What the Jurisdiction Sees on the Dashboard

Client users see **all projects within their jurisdiction**, filtered by the jurisdiction they are assigned to in AMP. They can view every active project, every status column, every Shot Clock value, and every component for every application in their review queue.

> **Key Point:** The Jurisdiction holds FINAL approval authority. All approval decisions are recorded in the Official Record. Every approval, conditional approval, and denial carries legal weight and must be supported by the documented review record in AMP.

> **Visual:** [INSERT IMAGE: AMP dashboard from Jurisdiction user perspective showing all projects with status columns and Shot Clock values]
> *Caption: The Jurisdiction sees all applications in their review queue. Shot Clock values for every active project are visible on every row.*

---

## Role 2: Agent (Carrier Representative)

### Who They Are

The Agent is the person — typically employed by or contracted to a wireless carrier — who creates and submits wireless facility applications on the carrier's behalf in AMP.

Agents register directly in AMP. The registration process (covered in detail in the Agent-specific course) requires:
- Name and contact information
- Company name and address
- A **text-capable mobile number** for Two-Factor Authentication (2FA)
- Agreement to the Agent User Agreement
- WTS approval before the account is activated

An Agent can be registered in **multiple jurisdictions** — they are not limited to a single local government. This is common for Agents who work with carriers that operate across multiple markets.

### What the Agent Does

The Agent is the person who builds the application and keeps it moving through the review process:

- **Creates new projects** — initiates the project in AMP and begins the PIF
- **Completes the PIF** — enters all required project identification, agent and carrier information, project type, and site information
- **Completes all required components** — NWF, TWF, NCM, ATF as triggered by the PIF
- **Uploads all required documents** — structural analyses, site plans, FCC compliance documents, insurance certificates, performance bonds
- **Responds to revision requests** — when the Jurisdiction or Analyst sends a project back for corrections (red status), the Agent is the one who must make the corrections and resubmit
- **Saves work continuously** — AMP does not auto-save; Agents must save after every entry and every upload
- **Monitors project status** — tracks where their applications stand on the dashboard

### What the Agent Cannot Do

- Approve any application — not their own, not anyone else's
- See other Agents' projects — even if those projects involve the same tower or same carrier
- Skip the revision cycle — when a project is sent back, it must be corrected and resubmitted formally
- Submit components before payment is confirmed — after PIF approval, the invoice must be paid before submission unlocks (though components can be filled out and saved in advance since the May 2026 platform update)

### What the Agent Sees on the Dashboard

Agents see **only the projects they personally created and submitted**. This is a deliberate data protection control — one Agent cannot view another Agent's confidential application data, even within the same carrier organization.

> **Visual:** [INSERT IMAGE: AMP Agent dashboard view showing only the Agent's own projects]
> *Caption: Agents see only their own projects. Other Agents' applications — even at the same company or on the same tower — are not visible.*

---

## Role 3: Analyst (WTS Staff)

### Who They Are

The Analyst is a Wireless Tower Solutions (WTS) professional staff member who supports the Jurisdiction's review process. Analysts are not Jurisdiction employees — they are WTS staff assigned to assist specific Jurisdictions.

Analysts are the bridge between the Agent's submission and the Jurisdiction's review. They add a professional layer of completeness review before the Jurisdiction makes its formal decision.

### What the Analyst Does

- **Reviews applications for completeness** — verifying that all required fields are filled in, all required documents are uploaded, and all technical data meets review standards
- **Flags issues** — identifying problems in the application before the Jurisdiction spends time reviewing incomplete or incorrect submissions
- **Assists Clients** — advising the Jurisdiction on technical questions, regulatory context, and application requirements
- **Monitors workload** — tracking all active projects in their assigned jurisdiction(s) to ensure nothing is missed
- **Communicates with Agents** — requesting clarification or flagging issues through the AMP communication system (all communication inside AMP becomes part of the Official Record)
- **Supports Shot Clock tracking** — ensuring projects are moving and deadlines are visible

### What the Analyst Cannot Do

- Make a final approval, denial, or conditional approval decision — that authority belongs exclusively to the Jurisdiction
- Submit applications on behalf of an Agent
- Change or override data submitted by an Agent
- Act as the Jurisdiction — even if the Analyst believes an application should be approved, the Jurisdiction must make that decision

### What the Analyst Sees on the Dashboard

Analysts see **all projects within the jurisdiction(s) they are assigned to**. Unlike Agents, Analysts have broad visibility — they can see every application from every Agent across the entire jurisdiction.

> **Key Point:** The Analyst's role is advisory. The most common mistake involving Analysts is assuming that Analyst review or Analyst comment equals approval. It does not. Only the Jurisdiction can approve.

---

## Role 4: Analyst in Training (AIT)

### Who They Are

The Analyst in Training (AIT) is a WTS staff member who is learning the Analyst role. Their responsibilities and system access are the same as a qualified Analyst — but all of their actions require a co-sign from a qualified Analyst before they are finalized.

### How the AIT Workflow Differs

When an AIT takes an action that would normally be an Analyst decision:
- The result is flagged as **AIT Approved** (dark blue status) rather than standard Analyst approval
- The action routes to a qualified Analyst for verification and sign-off
- Only after the qualified Analyst signs off does the action proceed normally

This applies to any revision or review action an AIT takes. The AIT cannot finalize any review decision independently.

### AIT Revision Status

When an AIT determines that a project requires corrections:
- The project receives **AIT Revise** status (red) rather than standard Revise status
- The Agent must still make the corrections and resubmit
- The workflow for the Agent is identical — the status color and name are different, but the required action (revise and resubmit) is the same

> **Visual:** [INSERT IMAGE: AMP dashboard showing AIT Approved (dark blue) and AIT Revise (red) status indicators on project rows]
> *Caption: AIT status indicators appear when an Analyst in Training has taken an action. Dark blue (AIT Approved) routes to a qualified Analyst for co-sign. Red (AIT Revise) requires Agent correction just like standard Revise.*

---

## Role 5: Admin (System Administrator)

### Who They Are

The Admin is the system administrator responsible for the technical operation and configuration of AMP. Admins do not participate in the application review or approval process in any way.

### What the Admin Does

- **Manages user accounts** — creates, modifies, and deactivates user accounts; assigns roles and jurisdiction access
- **Configures the system** — sets up jurisdiction settings, fee schedules, and system parameters
- **Generates reports** — produces system-level reports on application volume, status distribution, and user activity
- **Supports platform functionality** — handles technical issues, password resets, and access problems

### What the Admin Cannot Do

This is the most important thing to understand about the Admin role:

- **Cannot approve applications** — the Admin role has no part in the application approval workflow
- **Cannot review applications** — Admins do not assess the technical or regulatory merits of any application
- **Cannot act as any other role** — having Admin access does not grant Analyst, Jurisdiction, or Agent capabilities

> **Critical Rule:** Separation of duties is a design requirement in AMP. The person who administers the system cannot be the same person who approves applications. This protects the integrity of the Official Record and ensures that administrative access cannot be used to manipulate approval decisions.

---

## How Roles Interact in the Workflow

The application process is a coordinated handoff between roles. Understanding the sequence helps every user anticipate what comes next and what they are responsible for.

### The Standard Workflow

1. **Agent** creates the project and completes the PIF
2. **Agent** submits the PIF for Analyst and Jurisdiction review
3. **Analyst** reviews the PIF for completeness
4. **Jurisdiction** reviews the PIF and approves (or sends back for revision)
5. **AMP** generates the invoice and activates required components
6. **Agent** completes all required components and supporting documents
7. **Agent** submits completed components once payment is confirmed
8. **Analyst** reviews components for completeness
9. **Jurisdiction** reviews and makes the final approval decision
10. **Jurisdiction** issues permit and schedules final inspection
11. **Jurisdiction** conducts final inspection and closes the project

### The Revision Cycle

When the Jurisdiction finds problems in a submission:

1. **Jurisdiction** sends the project back (status becomes Revise / Awaiting Revision — red)
2. **Agent** reviews the revision request in AMP
3. **Agent** makes required corrections
4. **Agent** resubmits the corrected application
5. **Analyst** reviews the resubmission
6. **Jurisdiction** evaluates the revised submission and makes its decision

The Shot Clock **pauses** while the project is in Awaiting Revision status (the Agent has the project). It **restarts** when the Agent resubmits.

> **Visual:** [INSERT IMAGE: AMP workflow diagram showing the full sequence — Agent submits → Analyst reviews → Jurisdiction decides → revision cycle if needed → final approval]
> *Caption: The standard AMP workflow and revision cycle. Each arrow represents a handoff between roles. The Official Record captures every step.*

---

## Approval Authority — The Non-Negotiable Rule

This is the single most important rule in AMP regarding roles:

> **Only the Jurisdiction (Client role) can approve a wireless facility application. Agents submit. Analysts support. Neither can approve. This is enforced technically by AMP — it is not a setting that can be changed.**

| Role | Can Approve? | Why |
|---|---|---|
| Client (Jurisdiction) | **Yes** | Final regulatory authority under federal and local law |
| Agent | No | Applicant — no authority to approve their own application |
| Analyst | No | Advisory role — supports review but does not hold decision authority |
| AIT | No | Same as Analyst — requires qualified co-sign for all actions |
| Admin | No | Administrative role — no part in application review |

### Why This Rule Matters

Federal telecommunications law places the approval authority with the local government. This is not just policy — it is a legal requirement. If a wireless facility application is approved by anyone other than the designated local government authority, the approval has no legal standing. The carrier could not rely on it. The Jurisdiction could not enforce it.

AMP enforces this rule at the system level. No role except Client can take the action that approves an application. This cannot be worked around, delegated, or bypassed.

---

## Common Role-Related Mistakes

### Mistake 1 — Assuming Analyst Review Equals Approval

An Analyst may review an application thoroughly, flag no issues, and say everything looks complete. This does not mean the application is approved. Approval only happens when the Jurisdiction takes the formal approval action in AMP. Until that happens, the project is not approved — regardless of what the Analyst says.

### Mistake 2 — An Agent Contacting the Jurisdiction Outside AMP

When an Agent has questions or concerns, the proper channel is through AMP. Phone calls and emails outside the system are not part of the Official Record. If the Jurisdiction provides guidance by phone or email and it conflicts with the formal revision request in AMP, the AMP record is what matters legally. All communication should happen in the system.

### Mistake 3 — Assuming the Jurisdiction Can Just Approve an Application Without Reviewing It

The Official Record requirement means the Jurisdiction must document the basis for every decision. Approving an application without actually reviewing it is not just a bad practice — it creates a record that shows an approval without any documented review basis, which may be indefensible if the decision is later challenged.

### Mistake 4 — An Agent Expecting the Analyst to Solve Problems in the Application

The Analyst reviews for completeness and flags issues — they do not fix problems. If a structural analysis is uncertified, the Analyst will flag it. But the Agent must obtain the corrected document and resubmit. The Analyst cannot upload corrected documents on the Agent's behalf or modify the application content.

### Mistake 5 — Treating the Admin as a Point of Escalation for Approval Issues

If an Agent believes an application is being unfairly delayed or denied, the escalation path is through the Jurisdiction or WTS — not through the Admin. The Admin has no authority over approval decisions and cannot intervene in the review process.

---

## Key Takeaways

- AMP has five user roles: **Client (Jurisdiction), Agent, Analyst, AIT, and Admin** — each with a clearly defined scope of authority
- **Only the Jurisdiction (Client) can approve** — this is a legal requirement enforced at the system level, not a preference or policy
- **Agents see only their own projects**; Analysts and Clients see all projects in their assigned jurisdiction
- The **Analyst role is advisory** — Analysts review for completeness and support Clients but cannot make final decisions
- The **AIT role mirrors Analyst** responsibilities but every action requires co-sign from a qualified Analyst before finalization
- The **Admin role is purely administrative** — Admins have no part in application review or approval
- **All role interactions must happen inside AMP** — communications, revision requests, and decisions outside the platform are not part of the Official Record
- The **Shot Clock pauses** when a project is in Agent hands for revision and **restarts** when the Agent resubmits

---

## Checkpoint Question

**Scenario:** A Jurisdiction reviewer is very busy and trusts her WTS Analyst completely. She tells the Analyst: *"I've reviewed your notes — everything looks good to me. Go ahead and approve the project in AMP. I'll back you up if anyone asks."*

What is the problem with this arrangement — and what must happen instead?

**A)** This is fine. The Analyst's approval is equivalent to the Jurisdiction's approval as long as the Jurisdiction verbally agrees beforehand. AMP records who clicks, but the verbal agreement gives it the same legal weight.

**B)** The arrangement is acceptable as a temporary measure when the Jurisdiction reviewer is busy, as long as she documents the verbal approval by email afterward.

**C)** This arrangement is not permissible. The Analyst role has no approval authority in AMP — the system will not allow an Analyst to take the approval action, and even if it could, the Analyst's approval would have no legal standing. The Jurisdiction reviewer must personally take the approval action in AMP. Her backing the decision verbally or after the fact does not transfer her legal authority to the Analyst.

**D)** The Analyst can approve in AMP as long as the Jurisdiction copies the approval to the Official Record manually after the fact.

---

**✓ Correct Answer: C** — Approval authority in AMP belongs exclusively to the Jurisdiction (Client role). This is a legal requirement, not a convenience setting. The Analyst does not have the system permission to take the approval action, and even if they did through some workaround, the resulting approval would not carry the legal authority of the local government. The Jurisdiction reviewer must personally log into AMP and take the formal approval action. Her being busy does not transfer her regulatory authority to another role. If workload is an issue, the solution is for the Jurisdiction to ensure adequate staffing — not to ask an Analyst to act with authority they do not have.

---

*End of Lesson 2 | Next: Lesson 3 — Projects, Dashboard, and Status*
