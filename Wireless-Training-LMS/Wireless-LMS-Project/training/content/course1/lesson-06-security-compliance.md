# Lesson 6: Security, Compliance, and the Official Record

**Course:** AMP Fundamentals
**Version:** 1.0 | 2026
**Preceding Lesson:** Lesson 5 â€” Component Types (NWF, TWF, NCM, ATF)
**Following Lesson:** Course 2 â€” AMP for Government / Jurisdiction Reviewers

---

## What You Should Understand After This Lesson

- What the Official Record is, what it contains, and why it is a federal legal requirement
- Why Two-Factor Authentication is mandatory for all AMP logins â€” and how it works
- How role-based access control protects application data and enforces separation of duties
- What "certified documents" means and what makes a document legally valid in AMP
- How AMP enforces federal compliance requirements throughout the wireless facility permitting process

---

## Overview

AMP is not just a workflow tool â€” it is a compliance platform. Every feature in AMP, from user login to document upload to final approval, is built around three interconnected principles: **security**, **regulatory compliance**, and **permanent record-keeping**.

These are not background features. They are the legal foundation that makes every decision made in AMP defensible, auditable, and enforceable under federal telecommunications law.

Understanding how AMP handles security and compliance is not optional for any user type. Agents must understand what documents must be certified and why. Analysts must understand how the audit trail works and what the Official Record protects. Jurisdiction users must understand their legal obligations around the Shot Clock and what happens when those obligations are not met.

> **Key Point:** Local governments manage wireless facility applications under federal telecommunications law. AMP is the mechanism that ensures every decision made at the local level is documented, defensible, and compliant. The rules that may feel rigid in AMP exist to protect the Jurisdiction, the carrier, and the integrity of the regulatory process.

---

## The Official Record

### What It Is

The Official Record is AMP's permanent, tamper-evident log of everything that occurs within a project. It is not a feature â€” it is a federal requirement.

Under federal telecommunications law, local governments must maintain a complete and accessible record of all communications and decisions made regarding wireless facility applications. AMP fulfills this requirement automatically. Every action taken in AMP â€” every submission, every review comment, every revision request, every approval, every document uploaded â€” is written to the Official Record the moment it occurs.

> **Visual:** [INSERT IMAGE: AMP project detail showing the Official Record / activity log tab with chronological entries visible]
> *Caption: The Official Record tab in a project shows every action, every communication, and every document in chronological order â€” from project creation through final approval.*

### What the Official Record Contains

The Official Record stores every category of data generated throughout the project lifecycle:

- **All submitted data** â€” every field entered in the PIF and all components
- **All uploaded documents** â€” every PDF, engineering report, site plan, and certification submitted
- **All communications** â€” every comment, revision request, and response exchanged between Agent, Analyst, and Jurisdiction
- **All decisions** â€” every approval, rejection, revision request, and status change, with timestamps and user attribution
- **All user actions** â€” every login, edit, save, and submission, attributed to the specific authenticated user who took the action
- **All fee and invoice records** â€” every invoice generated and every payment confirmed

> **Why this matters:** Because every action is logged with user attribution and a timestamp, AMP can answer with certainty who did what, when, and in what order. This is critical in disputes, legal challenges, and audits.

### Why It Is a Federal Requirement

Federal telecommunications law â€” specifically the Telecommunications Act of 1996 and subsequent FCC regulations â€” requires that local governments provide a written record supporting any decision on a wireless facility application. A Jurisdiction that cannot produce documented evidence of its review process has no legal basis to defend its decisions in court or before a federal agency.

AMP satisfies this requirement by building the Official Record automatically and continuously throughout every project. No special action is required to create it â€” the Official Record exists by default for every project from the moment it is created.

> **Key Point:** The Official Record is what gives the Jurisdiction the legal standing to approve, condition, or deny a wireless facility application. Without it, their decisions are legally indefensible.

### How the Official Record Protects All Parties

| Party | How the Official Record Protects Them |
|---|---|
| **Jurisdiction** | Provides the documented basis for every decision â€” legal defense against carrier challenges and federal agency review |
| **Agent / Carrier** | Creates a verifiable record that applications were submitted on time and exactly as submitted |
| **WTS / Analyst** | Documents all advisory actions and communications â€” protects WTS staff from liability disputes |
| **The Public** | Ensures the regulatory process was followed correctly and can be reviewed if needed |

### The Official Record Is Permanent

Once data enters the Official Record, it cannot be deleted or overwritten. Revisions are handled through a formal revision cycle â€” the original submission remains on record, and the revised version is added as a new entry with its own timestamp and user attribution. Nothing is erased.

> **This is why accuracy matters from the beginning.** Every error in a submission becomes a permanent part of the record. Corrections are possible â€” but they are visible, traceable, and part of the record. There is no undo button in AMP.

---

## Security in AMP

### Two-Factor Authentication (2FA)

Two-Factor Authentication is mandatory for all AMP logins â€” no exceptions, for every user type, every session, every time.

#### What 2FA Is

Two-Factor Authentication adds a second verification layer on top of a username and password. After entering their credentials, users receive a one-time verification code sent to their registered text-capable mobile number. They must enter this code to complete login.

This means that even if a user's password is compromised, an attacker cannot access AMP without also having physical possession of the registered mobile device.

#### How 2FA Works in AMP

1. User navigates to the AMP login page
2. User enters their **User ID (email address)** and password
3. AMP sends a one-time code to the user's registered **text-capable mobile number**
4. User enters the code to complete authentication
5. Session begins â€” tied to the authenticated user account

> **For Agents:** Your text-capable mobile number is collected at registration and is the number used for all 2FA codes. If your mobile number changes, contact WTS to update your account. You will not be able to log in without access to the registered number.

> **For Jurisdiction and Analyst Users:** Contact your WTS account administrator to update your 2FA mobile number. Never share your verification codes with anyone.

> **Security Alert:** If you receive a 2FA code you did not request, your account credentials may be compromised. Contact WTS support immediately.

> **Visual:** [INSERT IMAGE: AMP login screen showing the 2FA code entry step after initial credential entry]
> *Caption: After entering email and password, AMP sends a one-time code to the registered mobile number. Both steps are required to access the system.*

#### Why 2FA Is Required

AMP contains legally sensitive application data, confidential carrier information, certified engineering documents, and the Official Record of local government decisions. This data must be protected against unauthorized access.

2FA also satisfies baseline security requirements under federal frameworks governing how government-facing platforms must handle user authentication. This is not a configurable option â€” it is a platform requirement.

---

### Role-Based Access Control

AMP enforces strict role-based access. What a user can see, do, and submit in AMP depends entirely on their assigned role. This is not just a convenience feature â€” it is a security control that is enforced at the system level.

#### What Each Role Can Access

| Role | Projects Visible | Can Submit | Can Approve | Can Administer |
|---|---|---|---|---|
| **Agent** | Only their own submitted projects | Yes | No | No |
| **Analyst** | All projects in assigned jurisdiction(s) | No | No â€” advisory only | No |
| **Client (Jurisdiction)** | All projects in their jurisdiction | No | **Yes â€” exclusive authority** | No |
| **AIT (Analyst in Training)** | Same as Analyst | No | No â€” requires qualified Analyst co-sign | No |
| **Admin** | System-level; configurable | No | No | Yes |

> **Security Principle:** No user can see data they are not authorized to access. Agents cannot view other Agents' applications â€” even if those applications involve the same tower or same carrier. Analysts and Clients see everything within their assigned jurisdiction and nothing outside it.

#### Why Separation of Duties Matters

Role-based access control prevents:
- Agents from viewing competitor application data
- Unauthorized users from accessing confidential carrier information
- Non-approving roles from taking approval actions
- Administrative users from approving applications (no role can both administer and approve)

This separation of duties is both a security requirement and a regulatory design principle. Final approval authority in AMP is exclusively reserved for the Jurisdiction (Client role) â€” and the system enforces this technically, not just by policy. No workaround exists.

> **Visual:** [INSERT IMAGE: AMP role access diagram showing what each role can see and do, with arrows and permission indicators]
> *Caption: Role-based access is enforced at the system level. Agents see only their own projects. Analysts and Clients see all projects in their jurisdiction. Only the Client role can approve.*

---

### The Audit Trail

Every action in AMP creates an audit trail entry. This is the mechanism that makes the Official Record reliable and legally defensible.

Each audit trail entry records:
- **Who** was logged in when the action occurred (authenticated user identity)
- **What** action was taken (submit, save, approve, comment, upload, revision, etc.)
- **When** the action occurred (date and time stamp)
- **What changed** â€” the state of the record before and after the action

The audit trail is what transforms a record of decisions into a legally verifiable chain of custody. Without it, data could be claimed, altered, or disputed. With it, every entry can be independently verified and attributed.

---

## Compliance Requirements in AMP

### Certified Documents

AMP requires that specific documents be certified â€” meaning reviewed, stamped, and signed by a Professional Engineer (PE) licensed in the state where the project is located. Uploading uncertified versions of these documents will not satisfy review requirements and will result in a revision request.

**Documents that must be PE-certified:**

| Document | Where It Is Submitted | Why Certification Is Required |
|---|---|---|
| Certified Structural Analysis | NCM Section 4 | Confirms the tower can safely support the proposed equipment under applicable ANSI/TIA standards |
| Certified Site Plan | NCM Section 3 | Confirms location, dimensions, setbacks, and layout meet applicable code requirements |
| Soils Study Report | ATF / TWF | Confirms soil conditions support the tower structure |

> **Critical Rule:** PE certification means the document must bear the stamp and signature (wet or digital) of an engineer holding an **active PE license in the state where the project is located.** A PE licensed in another state does not satisfy this requirement, regardless of that engineer's qualifications or reputation.

> **Visual:** [INSERT IMAGE: Example of a certified structural analysis cover page showing PE stamp, signature, and license information]
> *Caption: A certified document must display the PE's stamp, signature, and license information for the project state. Unsigned or unstamped documents will be rejected.*

---

### FCC RF Emissions Compliance

All wireless facility applications must demonstrate compliance with FCC radio frequency (RF) emissions standards. This compliance is documented in the **FCC Compliance Document** submitted in the NCM's Legal Information section (Section 5).

#### The Boundary Between Verification and Regulation

| Role | Responsibility |
|---|---|
| **FCC** | Establishes and regulates RF emissions standards nationally |
| **Carrier / Agent** | Certifies that the proposed facility complies with FCC standards via the FCC Compliance Document |
| **Jurisdiction** | Verifies that the submitted FCC Compliance Document confirms compliance â€” does NOT set or modify standards |

#### What Jurisdictions Cannot Do

A Jurisdiction cannot:
- Use RF emissions concerns as the basis to deny an application if the submitted FCC Compliance Document demonstrates federal compliance
- Impose local RF emissions standards stricter than federal standards
- Require studies or certifications beyond what federal law allows

> **Key Point:** Local governments verify FCC compliance â€” they do not regulate it. Attempting to impose local RF standards or deny an application based on RF concerns that federal standards already address may expose the Jurisdiction to a preemption challenge under federal telecommunications law.

---

### Performance Bonds

A Performance Bond is a financial instrument â€” similar to insurance â€” that guarantees the carrier will fulfill its obligations under the permit. In the wireless facility context, this is typically structured as a **removal bond**: it ensures that if the wireless facility is abandoned or decommissioned, the carrier has the financial obligation to remove the structure.

Requirements in AMP:
- The bond value must equal the amount specified in the applicable **Ordinance**
- The bond must remain valid **for as long as the site is active**
- The bond document is submitted in the NCM Legal Information section (Section 5)
- An expired or insufficiently valued Performance Bond is grounds for a revision request

> **Why this matters:** Without a valid removal bond, a local government could be left responsible for the cost of removing an abandoned tower structure after the carrier ceases operations. The Performance Bond transfers that financial risk back to the carrier where it belongs.

---

### Certificate of Insurance

All applicants must provide a current Certificate of Insurance naming specific parties as **additional named insureds**. A generic insurance certificate that shows the carrier has insurance is not sufficient â€” the certificate must specifically name:

- The Jurisdiction (the local government entity)
- Its officers
- Its employees
- Its agents
- Its consultants

A certificate that does not name the Jurisdiction and these specific parties will not satisfy the review requirement and will trigger a revision request.

> **Insurance Rule:** Insurance expiration dates must be actively maintained throughout the life of the project â€” not just at time of submission. An expired certificate can halt a final inspection or block permit issuance. Agents are responsible for proactively notifying the Jurisdiction when insurance is renewed and uploading the updated certificate in AMP.

---

### Federal Shot Clock Compliance

The Shot Clock is a compliance requirement under federal law â€” not just a performance metric. (Lesson 3 introduced the Shot Clock as a dashboard feature; this section addresses its legal compliance dimensions.)

#### Federal Time Limits by Project Type

| Project Type | Shot Clock Duration |
|---|---|
| Standard projects (New Tower, Colocation, Modification) | 150 days |
| Single Small Wireless Facility (SWF) | 60 days |
| Batch SWF application (up to 25 sites) | 90 days |

These timeframes are established by FCC regulation and are not adjustable at the local level. No ordinance can extend them.

#### What Happens When the Shot Clock Expires

If a Jurisdiction fails to act before the Shot Clock expires:

1. The application is **deemed approved by operation of federal law**
2. The carrier gains the **right to proceed with construction** without formal local approval
3. The Jurisdiction **loses the ability to review, condition, or deny** the application
4. The carrier may seek a **writ of mandamus** from a federal court to compel issuance of the permit

> **For Jurisdiction Users:** The Shot Clock is a federal compliance requirement. Failure to act within the Shot Clock window is not a missed deadline â€” it is a federal law violation with serious legal consequences. AMP displays Shot Clock status on the dashboard for every active project. Checking Shot Clock values should be part of every dashboard review session.

> **For Agent Users:** Understanding the Shot Clock gives you a legal basis to escalate if a Jurisdiction is not responding within required timeframes. AMP's Shot Clock display provides the documentation needed to demonstrate when federal deadlines are at risk.

---

## Data Integrity

### Why Changes Require a Formal Revision Cycle

Because the Official Record is permanent, corrections to submitted application data are not made by editing the original record. Instead, the project must go through a formal revision cycle:

1. The Jurisdiction or Analyst sends the project back (status changes to **Revise** or **Awaiting Revision** â€” red on the dashboard)
2. The Agent makes the required corrections
3. The Agent resubmits the corrected application
4. **Both the original submission and the corrected resubmission** become part of the Official Record

This process preserves the complete history of the application â€” what was originally submitted, what was found to be incorrect, what was corrected, and when each action occurred. This history is legally significant and cannot be bypassed.

> **Practical Implication:** If a PIF was submitted with the wrong project type, it cannot simply be edited. It must go through a formal revision cycle â€” consuming time, resetting the Shot Clock in some cases, and creating additional review burden. The cost of getting it right the first time is always less than the cost of a revision cycle.

### The SAVE Requirement

AMP does not auto-save. Data that is entered but not saved before navigating away is permanently lost. This is not a temporary limitation â€” it is an intentional design choice that maintains the integrity of the Official Record. AMP records what is explicitly saved by the user, not what is temporarily displayed in a form field.

> **SAVE â€” SAVE â€” SAVE.** After every entry. After every document upload. Before navigating to any other screen. This applies to every user, every form, every session. Data that is not saved cannot be recovered.

### What Happens When Data Is Lost

If a user navigates away from a form without saving, all unsaved data is gone. AMP cannot recover it. The user must re-enter everything from the beginning.

Real-world consequences:
- Delay in application submission
- Potential errors in re-entered data (the re-entered version may differ from the original intent)
- Possible revision cycles if submitted data reflects re-entry errors
- Lost time and added frustration for all parties involved

The single best practice in AMP â€” applicable to every user type, every form, every session â€” is to **click Save after every single action before moving on.**

---

## Key Takeaways

- The **Official Record** is a federal requirement â€” AMP automatically and permanently documents every action, decision, and document for every project
- The **Official Record cannot be deleted or overwritten** â€” corrections go through formal revision cycles, and both the original and corrected versions remain on file
- **Two-Factor Authentication is mandatory** for all AMP logins â€” every user must have a text-capable mobile number registered to their account
- **Role-based access control** ensures each user sees only the data they are authorized to access â€” and only the Jurisdiction (Client role) can approve applications
- **Certified documents** must be stamped and signed by a PE licensed in the state where the project is located â€” the engineering firm's reputation does not substitute for this legal requirement
- **Local governments verify FCC RF compliance â€” they do not regulate it** â€” imposing local RF standards beyond what federal law allows may expose the Jurisdiction to legal challenge
- **The Shot Clock is a federal compliance requirement** â€” allowing it to expire results in deemed approval, loss of review authority, and potential federal court action
- **AMP does not auto-save** â€” click Save after every entry and every upload without exception

---

*End of Lesson 6 | AMP Fundamentals Course Complete | Next: Course 2 â€” AMP for Government / Jurisdiction Reviewers*
