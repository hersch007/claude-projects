# Lesson 3: Projects, Dashboard, and Status in AMP

**Course:** AMP Fundamentals
**Version:** 1.0 | 2026
**Preceding Lesson:** Lesson 2 — User Roles and Responsibilities
**Following Lesson:** Lesson 4 — The Project Information Form (PIF)

---

## What You Should Understand After This Lesson

- How applications are organized as projects in AMP and what each project contains
- How the dashboard works and what each user type sees
- Every project status — what it means, what color it displays, and who needs to act
- How to read the dashboard correctly to determine the true state of any project
- What the Shot Clock is and why it matters for every review decision

---

## Overview

Once a user logs into AMP, the first thing they see is the Dashboard. This is the control center for the entire application process — the place where every active project is visible, every status is tracked, and every required action is identified.

Understanding the dashboard is not optional. It is the primary tool used by Agents to monitor their submissions, by Analysts to manage their review workload, and by Jurisdictions to track applications and meet federal deadlines.

This lesson covers three closely connected concepts: how **projects** are structured, how the **dashboard** displays them, and how **status** communicates what is happening and what needs to happen next.

> **Key Point:** The dashboard is not just a list of projects. It is a real-time decision-making tool. Every status, every color, and every column tells you something specific. Learning to read it correctly is one of the most important skills in AMP.

---

## Projects in AMP

### What Is a Project?

In AMP, every wireless facility application is organized as a **project**. A project represents a single request — from initial application submission all the way through final inspection and approval.

Every project contains:
- All submitted information entered by the Agent
- Required forms and components (PIF, NWF, TWF, NCM)
- Supporting documents uploaded during the application process
- Review history — every action taken, every comment made, every decision recorded
- Communication between Agent, Analyst, and Jurisdiction

> **Key Point:** Projects are the central unit of work in AMP. Everything — status, components, communications, fees, and approvals — is organized around individual projects.

### What a Project Tracks

A project does not just store data — it actively tracks the lifecycle of an application through defined stages:

1. **Creation** — Agent initiates the project and begins the PIF
2. **Submission** — Agent submits the completed PIF for review
3. **Review** — Analyst and/or Jurisdiction reviews the application
4. **Revision** — If corrections are needed, the project is sent back; Agent revises and resubmits
5. **Approval** — Jurisdiction approves; components unlock and the process continues
6. **Construction** — Permit issued; construction begins and is tracked
7. **Final Inspection** — On-site verification that the project was built as approved
8. **Completion** — Project is closed in AMP; all records stored in the Official Record

Each stage is reflected in the project's status on the dashboard.

> **Visual:** [INSERT IMAGE: AMP project detail view showing the project lifecycle stages with current stage highlighted]
> *Caption: Every project moves through defined stages from creation through final inspection. The current stage is always visible in the project's status.*

---

## The Dashboard

### What the Dashboard Is

The Dashboard is AMP's primary status page. It is the first screen a user sees after logging in. It provides a real-time view of every project the user has access to, showing current status, required actions, and timeline information at a glance.

> **Visual:** [INSERT IMAGE: Full AMP dashboard view with multiple projects showing different status colors]
> *Caption: The AMP Dashboard — the first screen after login. Every active project appears here with real-time status.*

### What Each User Type Sees

Not all users see the same projects on their dashboard. Access is role-based:

| User Role | What They See on the Dashboard |
|---|---|
| Agent | Only the projects they personally created and submitted |
| Analyst | All projects within the jurisdiction(s) they are assigned to |
| Client (Jurisdiction) | All projects within their jurisdiction, based on the Filter setting at the top of the screen |
| Admin | System-level view; configurable |

> **Why this matters:** An Agent cannot see other Agents' projects. Analysts and Clients see everything within their jurisdiction. This separation protects confidential application data while giving reviewers the full picture they need to manage their workload.

### How to Read a Dashboard Row

Each row on the dashboard represents one project. The columns across a row collectively tell the full story of that project's current state.

Key information displayed per row includes:
- **Project ID** — the WTS-assigned unique identifier
- **Project Type** — what kind of application this is
- **Jurisdiction** — which local government is reviewing it
- **Agent** — who submitted it
- **Component status columns** — the current status of each component (PIF, NCM, etc.) shown in color
- **Shot Clock** — federal deadline countdown showing days remaining for Jurisdiction review
- **Last Activity** — when the most recent action occurred

> **Critical Rule:** Project status is NEVER determined by looking at a single column. You must scan the ENTIRE ROW from left to right to understand what has been completed, what is still required, and who is responsible for the next step.

---

## Project Status — Complete Reference

Each project and each component within a project is assigned a status. Status communicates three things simultaneously: where the project stands, what action is required, and who needs to take that action.

### Complete Status Table

| Status | Color | Meaning | Who Acts | Action Required |
|---|---|---|---|---|
| Draft | None | Project is being created, not yet submitted | Agent | Complete and submit the project |
| Saved | Teal / Cyan | Created or updated but NOT submitted for review | Agent | Must submit to continue the workflow |
| Submitted / In Review | Blue | Project submitted and under active review | Analyst / Jurisdiction | Monitor for Jurisdiction response |
| Pending | Yellow | In progress, awaiting the next step | Monitor | Determine if action is needed |
| Invoiced | Orange | Fees generated and issued | Agent | Payment required before submission unlocks |
| Revise | Red | Reviewed and requires corrections | Agent | Revise and resubmit before project can proceed |
| AIT Revise | Red | Requires revisions under the AIT track | Agent | Corrections required before continuing |
| Awaiting Revision | Red | Additional information or corrections required | Agent | Update and resubmit |
| Approved | Green | Met all requirements for this stage | None | No further action required for this step |
| AIT Approved | Dark Blue | Approved under the Alternative Inspection Track | Analyst | Proceed to qualified Analyst sign-off |
| Completed | Green | Final steps verified and closed | None | Project is complete — no action required |

> **Visual:** [INSERT IMAGE: AMP dashboard showing projects with multiple status colors — red, green, teal, orange, and yellow visible]
> *Caption: Status colors give an instant visual signal. Red means someone must act now. Green means this step is done. Always confirm by reading the full row.*

### Understanding Status Colors

Color is your first signal — it tells you the urgency and general state of a project at a glance. But color alone is never enough. Always read the full row.

**Red — Action Required**
Red is the most urgent status. A red status means the project cannot move forward until someone takes action. Red statuses (Revise, AIT Revise, Awaiting Revision) are always assigned to the Agent — the Jurisdiction has reviewed the submission and found something that must be corrected before review can continue.

> If you see red on the dashboard, find out who is responsible and what is needed. Do not leave red projects unattended.

**Teal / Cyan — Incomplete, Not Yet Submitted**
Teal means the project or component has been saved but not yet submitted for review. The work has been started and saved, but it has not entered the review queue. A teal status requires the responsible user to go back in and submit.

> A common mistake: assuming a teal project is "in review." It is not. It is sitting saved, waiting to be submitted.

**Orange — Invoiced, Payment Required**
Orange means the invoice has been issued and payment is required before the project can proceed. After the May 2026 platform update, Agents can now fill out and save all components while payment is processing — but no component can be submitted for Jurisdiction review until payment is confirmed.

**Yellow — Pending, Monitor for Next Step**
Yellow means the project is moving through a step and is awaiting the next action in the workflow. Yellow projects are not stuck — they are in progress. Monitor them to ensure they advance on schedule and that the Shot Clock does not expire.

**Green — Approved or Complete**
Green means this step is done. A green Approved status means the Jurisdiction has reviewed and accepted this stage of the application. A green Completed status means the entire project has been verified and closed. No further action is required for green items.

**Dark Blue — AIT Approved**
Dark Blue indicates the project was approved under the Alternative Inspection Track (AIT), which routes the approval through a qualified Analyst before finalization. This is a specialized workflow for Analysts in Training.

---

## The Shot Clock

### What It Is

The Shot Clock is a federal regulatory countdown timer. Based on requirements established by federal telecommunications law, local governments have a defined amount of time to review and respond to wireless facility applications before those applications are automatically deemed approved.

The Shot Clock column on the dashboard shows how many days remain for the Jurisdiction to take action on each project.

### Why It Matters

The Shot Clock is not a suggestion — it is a federal legal requirement. If the Jurisdiction does not act before the Shot Clock expires:
- The application may be legally deemed approved by operation of federal law
- The carrier gains the right to proceed with construction
- The Jurisdiction loses its ability to review, condition, or deny the application

> **For Jurisdiction Users:** Monitoring the Shot Clock is one of your most critical responsibilities in AMP. Letting a Shot Clock expire — even on a project with legitimate issues — can have serious legal consequences.

> **For Agent Users:** Understanding the Shot Clock helps you set realistic expectations for timeline. It also gives you a legal basis to escalate if a Jurisdiction is not responding within required timeframes.

### Shot Clock Behavior

- The Shot Clock **starts** when the Agent submits a complete application
- The Shot Clock **pauses** when the Jurisdiction sends the project back for revision (Awaiting Revision status)
- The Shot Clock **restarts** when the Agent resubmits after revisions
- The length of the Shot Clock varies by project type — SWF projects have different deadlines than Traditional Tower projects

> **Visual:** [INSERT IMAGE: AMP dashboard Shot Clock column showing days remaining, with one project near expiration highlighted in red]
> *Caption: The Shot Clock countdown is visible on the dashboard for every active project. Red or near-zero counts require immediate Jurisdiction attention.*

---

## The Dashboard Reading Rule

This is one of the most important rules in AMP:

> **Never determine a project's status by looking at a single column. Always scan the entire row from left to right.**

Each column represents a different component or stage. A project can have one green column and one red column at the same time — meaning one part is approved while another requires immediate action. Reading only the green column would give you a completely false picture of the project's state.

### How to Read a Row Correctly

When you look at a project row on the dashboard, work through these questions in order:

1. **What is the overall project status?** — Check the leftmost status indicator
2. **What is the status of each component?** — Scan each component column left to right
3. **Are any components red?** — If yes, action is required immediately
4. **Are any components teal?** — If yes, something has been saved but not submitted
5. **What is the Shot Clock showing?** — Is there time pressure on this project?
6. **Who is responsible for the next step?** — Based on what you see, is it the Agent, Analyst, or Jurisdiction?

### Common Dashboard Mistakes

**Mistake 1 — Reading only one column**
A project can look approved in one column and be stuck in another. Always read the full row.

**Mistake 2 — Assuming teal means submitted**
Teal/Cyan means saved but NOT submitted. Many users assume saved equals submitted. It does not. A teal project is waiting for the user to come back and click Submit.

**Mistake 3 — Ignoring yellow projects**
Yellow (Pending) projects are easy to overlook because they do not signal an immediate problem. But a pending project that nobody monitors can miss a Shot Clock deadline or get stuck waiting for an action that no one realized was needed.

**Mistake 4 — Assuming green means everything is done**
A green indicator on one component means that component is approved. It does not mean the entire project is complete. Check every column.

**Mistake 5 — Not checking the Shot Clock**
The Shot Clock is easy to ignore — until it expires. Build a habit of checking Shot Clock values as part of every dashboard review.

> **Visual:** [INSERT IMAGE: Dashboard row with mixed statuses — one green, one red, one teal — demonstrating why the full row must be read]
> *Caption: This project has one approved component (green), one requiring action (red), and one saved but not submitted (teal). Reading only the green column would give a completely false picture.*

---

## Key Takeaways

- Every wireless facility application in AMP is organized as a **project** — the central unit of work in the system
- The **Dashboard** is the first screen after login — it provides a real-time view of all projects the user has access to
- **Agents see only their own projects**; Analysts and Clients see all projects within their jurisdiction
- There are **11 project statuses** — each with a specific color, meaning, and required action
- **Red = act now. Teal = not yet submitted. Orange = awaiting payment. Yellow = monitor. Green = approved/complete.**
- The **Shot Clock** is a federal countdown — if it expires, the application may be legally deemed approved
- **Never read status from a single column** — always scan the entire row left to right to understand the full picture

---

## Checkpoint Question

**Scenario:** A Jurisdiction reviewer logs into AMP and sees a project row on the dashboard. The PIF column shows Green (Approved). The NCM column shows Red (Awaiting Revision). The Shot Clock shows 4 days remaining. The reviewer says: *"The PIF is approved so this project is mostly fine. I'll come back to it next week."*

What is wrong with this assessment — and what should the reviewer do?

**A)** Nothing is wrong. The PIF is the most important component, so green on the PIF means the project is in good shape.

**B)** The reviewer is correct to wait — 4 days on the Shot Clock is plenty of time to address the NCM next week.

**C)** The assessment is incorrect on two counts: the NCM is red meaning action is required immediately, and with only 4 days on the Shot Clock there is no time to wait. The reviewer must address the NCM revision now to avoid a potential Shot Clock violation.

**D)** The reviewer should contact the Agent and ask them to resubmit the whole project to reset the Shot Clock.

---

**✓ Correct Answer: C** — Reading only the green PIF column and ignoring the red NCM is exactly the kind of single-column mistake the Dashboard Reading Rule is designed to prevent. The NCM is red — meaning the Agent has been asked to revise it and the project cannot proceed until they do. More urgently, the Shot Clock is at 4 days. If the Jurisdiction does not act within that window, the application risks being deemed approved by operation of federal law. The reviewer must address this project immediately — not next week.

---

*End of Lesson 3 | Next: Lesson 4 — The Project Information Form (PIF)*
