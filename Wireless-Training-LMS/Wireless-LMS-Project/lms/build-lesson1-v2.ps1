# build-lesson1-v2.ps1
# Rebuilds Lesson-01-What-Is-AMP.docx from scratch with WTS brand styling.
# Preserves 4 user images extracted to lms\L1img-*.png files.

$base     = "C:\Users\richa\Documents\Claude Projects\Wireless-Training-LMS\Wireless-LMS-Project"
$logoPath = "$base\assets\WTS-Oval-Logo-Blue.png"
$outPath  = "$base\lms\Lesson-01-What-Is-AMP.docx"

$imgWTS       = "$base\lms\L1img-wts-section.png"
$imgProblem   = "$base\lms\L1img-problem-section.png"
$imgDashboard = "$base\lms\L1img-dashboard-section.png"
$imgPIF       = "$base\lms\L1img-pif-section.png"

# Colors -- BGR format for Word COM
$cCyan  = 15707648   # #00AEEF -- WTS cyan
$cNavy  = 9064218    # #1A4F8A -- WTS navy
$cWhite = 16777215   # #FFFFFF
$cBlack = 0          # #000000
$cGray  = 6710886    # #666666

Write-Host "Building Lesson-01-What-Is-AMP.docx..."

$word = New-Object -ComObject Word.Application
$word.Visible = $true
$word.WindowState = 2   # wdWindowStateMinimize

$doc = $word.Documents.Add()
$sel = $word.Selection

# ===== HELPER FUNCTIONS =====

function GoEnd   { $sel.EndKey(6) | Out-Null }
function NP      { GoEnd; $sel.TypeParagraph() }

function SetNormal {
    try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
    $sel.ParagraphFormat.Alignment       = 0
    $sel.ParagraphFormat.LeftIndent      = 0
    $sel.ParagraphFormat.RightIndent     = 0
    $sel.ParagraphFormat.SpaceBefore     = 0
    $sel.ParagraphFormat.SpaceAfter      = 6
    $sel.ParagraphFormat.FirstLineIndent = 0
    $sel.Font.Bold   = $false
    $sel.Font.Italic = $false
    $sel.Font.Size   = 11
    $sel.Font.Color  = $cBlack
}

function AddFmt($text) {
    $parts = $text -split '(\*\*[^*]+\*\*)'
    foreach ($p in $parts) {
        if ($p -match '^\*\*(.+)\*\*$') {
            $sel.Font.Bold = $true
            $sel.TypeText($Matches[1])
            $sel.Font.Bold = $false
        } elseif ($p -ne '') {
            $sel.TypeText($p)
        }
    }
}

function H1($text) {
    NP; GoEnd
    try { $sel.Style = $doc.Styles.Item("Heading 1") } catch {}
    $sel.Font.Color = $cNavy
    $sel.Font.Bold  = $true
    $sel.Font.Size  = 16
    AddFmt $text
}

function H2($text) {
    NP; GoEnd
    try { $sel.Style = $doc.Styles.Item("Heading 2") } catch {}
    $sel.Font.Color = $cNavy
    $sel.Font.Bold  = $true
    $sel.Font.Size  = 13
    AddFmt $text
}

function H3($text) {
    NP; GoEnd
    try { $sel.Style = $doc.Styles.Item("Heading 3") } catch {}
    $sel.Font.Color = $cCyan
    $sel.Font.Bold  = $true
    $sel.Font.Size  = 11
    AddFmt $text
}

function P($text) {
    NP; GoEnd; SetNormal
    AddFmt $text
}

function Bullet($text) {
    NP; GoEnd
    try { $sel.Style = $doc.Styles.Item("List Bullet") } catch {}
    $sel.Font.Color  = $cBlack
    $sel.Font.Bold   = $false
    $sel.Font.Italic = $false
    $sel.Font.Size   = 11
    AddFmt $text
}

function Numbered($n, $text) {
    NP; GoEnd
    try { $sel.Style = $doc.Styles.Item("List Number") } catch {}
    if ($n -eq 1) {
        try { $sel.Range.ListFormat.ApplyNumberDefault() } catch {}
    }
    $sel.Font.Color  = $cBlack
    $sel.Font.Bold   = $false
    $sel.Font.Italic = $false
    $sel.Font.Size   = 11
    AddFmt $text
}

function KP($text) {
    NP; GoEnd; SetNormal
    $sel.Font.Italic = $true
    $sel.Font.Color  = $cCyan
    $sel.Font.Size   = 11
    $sel.ParagraphFormat.LeftIndent  = $word.InchesToPoints(0.3)
    $sel.ParagraphFormat.RightIndent = $word.InchesToPoints(0.3)
    $sel.TypeText($text)
}

function Img($path, $widthIn, $caption) {
    NP; GoEnd; SetNormal
    $sel.ParagraphFormat.Alignment = 1
    if (Test-Path $path) {
        $shape = $sel.InlineShapes.AddPicture($path, $false, $true)
        $ratio = $shape.Height / $shape.Width
        $newW  = $word.InchesToPoints($widthIn)
        $shape.Width  = $newW
        $shape.Height = $newW * $ratio
        $wIn = [math]::Round($shape.Width / 72, 2)
        Write-Host "  IMG: $([System.IO.Path]::GetFileName($path)) @ $wIn in"
    } else {
        $fn = [System.IO.Path]::GetFileName($path)
        $sel.TypeText("[IMAGE -- file not found: $fn]")
        Write-Host "  IMG NOT FOUND: $path"
    }
    if ($caption) {
        NP; GoEnd; SetNormal
        $sel.Font.Size   = 9
        $sel.Font.Italic = $true
        $sel.Font.Color  = $cGray
        $sel.ParagraphFormat.Alignment = 1
        $sel.TypeText($caption)
    }
}

function AddTable($headers, $rows, $widths) {
    NP; GoEnd
    $nc = $headers.Count
    $nr = 1 + $rows.Count
    $tbl = $doc.Tables.Add($sel.Range, $nr, $nc)
    try { $tbl.Style = "Table Grid" } catch {}
    $tbl.AllowAutoFit = $false

    if ($widths -and $widths.Count -ge $nc) {
        for ($c = 1; $c -le $nc; $c++) {
            $tbl.Columns($c).Width = $word.InchesToPoints($widths[$c - 1])
        }
    }

    # Header row
    for ($c = 1; $c -le $nc; $c++) {
        $cell = $tbl.Cell(1, $c)
        $cell.Shading.BackgroundPatternColor = $cNavy
        $cell.Select()
        $word.Selection.Collapse(1)
        $word.Selection.Font.Color = $cWhite
        $word.Selection.Font.Bold  = $true
        $word.Selection.Font.Size  = 11
        $word.Selection.TypeText($headers[$c - 1])
    }

    # Data rows
    for ($r = 0; $r -lt $rows.Count; $r++) {
        $rowData = $rows[$r]
        $bg = if (($r % 2) -eq 0) { 15921906 } else { 16777215 }
        for ($c = 1; $c -le $nc; $c++) {
            $cell = $tbl.Cell($r + 2, $c)
            $cell.Shading.BackgroundPatternColor = $bg
            $cell.Select()
            $word.Selection.Collapse(1)
            $word.Selection.Font.Color  = $cBlack
            $word.Selection.Font.Bold   = $false
            $word.Selection.Font.Italic = $false
            $word.Selection.Font.Size   = 11
            $cellText = $rowData[$c - 1]
            $parts = $cellText -split '(\*\*[^*]+\*\*)'
            foreach ($p in $parts) {
                if ($p -match '^\*\*(.+)\*\*$') {
                    $word.Selection.Font.Bold = $true
                    $word.Selection.TypeText($Matches[1])
                    $word.Selection.Font.Bold = $false
                } elseif ($p -ne '') {
                    $word.Selection.TypeText($p)
                }
            }
        }
    }

    GoEnd; NP
}

# ===== BUILD DOCUMENT =====

Write-Host "  Inserting logo..."
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceAfter  = 6
$sel.ParagraphFormat.SpaceBefore = 10
$sel.ParagraphFormat.LeftIndent  = 0
$sel.ParagraphFormat.RightIndent = 0
$sel.Font.Size   = 11
$sel.Font.Bold   = $false
$sel.Font.Italic = $false
$sel.Font.Color  = $cBlack
if (Test-Path $logoPath) {
    $shape = $sel.InlineShapes.AddPicture($logoPath, $false, $true)
    $ratio = $shape.Height / $shape.Width
    $newW  = $word.InchesToPoints(1.8)
    $shape.Width  = $newW
    $shape.Height = $newW * $ratio
    Write-Host "  Logo inserted"
} else {
    $sel.TypeText("[WTS LOGO]")
    Write-Host "  Logo not found: $logoPath"
}

# ---- Lesson title ----
H1 "Lesson 1: What Is AMP and Why It Exists"

# ---- Metadata ----
NP; GoEnd; SetNormal
$sel.Font.Size  = 10
$sel.Font.Color = $cGray
$sel.TypeText("Course: AMP Fundamentals  |  Version: 1.0 | 2026")

NP; GoEnd; SetNormal
$sel.Font.Size  = 10
$sel.Font.Color = $cGray
$sel.TypeText("Preceding Lesson: None -- this is the first lesson in the course")

NP; GoEnd; SetNormal
$sel.Font.Size  = 10
$sel.Font.Color = $cGray
$sel.TypeText("Following Lesson: Lesson 2 -- User Roles and Responsibilities")

# =====================================================================
H2 "What You Should Understand After This Lesson"
Bullet "What AMP is, who built it, and what problem it was designed to solve"
Bullet "Who Wireless Tower Solutions (WTS) is and what role they play in the permitting process"
Bullet "The federal regulatory environment that makes AMP necessary for local governments"
Bullet "How AMP is organized -- the five core areas and what each one does"
Bullet "Why AMP is the system of record and what that means legally"
Bullet "How the five core areas connect to form a single end-to-end application workflow"

# =====================================================================
Write-Host "  Overview..."
H2 "Overview"
P "The Application Management Platform -- AMP -- is a web-based SaaS platform built by Wireless Tower Solutions (WTS) to manage the permitting and inspection of wireless towers and facilities for local governments."
P "AMP does not simply organize paperwork. It is a compliance platform: a structured system that enforces regulatory timelines, generates a permanent legal record, and guides every user through a defined workflow from the moment an application is created to the moment the permit is closed."
P "Before AMP, local governments received wireless facility applications through a combination of email, paper submissions, fax, and in-person document drops. Each method created a different problem: missing documentation, no audit trail, no standardized format, no deadline tracking, and no single source of truth. When disputes arose -- and in wireless permitting, they frequently do -- there was often no verifiable record of what was submitted, when, or who reviewed it."
P "AMP was built to solve all of that."
KP "Key Point: AMP replaces fragmented, multi-channel application processes with a single, structured, fully online workflow. Every action is logged. Every deadline is tracked. Every document is stored. The result is a process that is faster, more compliant, and fully defensible under federal law."

# =====================================================================
Write-Host "  WTS section..."
H2 "Who Is Wireless Tower Solutions (WTS)?"
H3 "The Company"
P "Wireless Tower Solutions (WTS) is the professional services company that built AMP and manages it on behalf of local governments nationwide. WTS is based in South Carolina and operates at the intersection of local government permitting and wireless carrier deployment -- providing the tools, staff, and expertise that make the process work for both sides."
P "WTS provides:"
Bullet "**The AMP platform** -- the web-based application management system every user in this course will operate"
Bullet "**Analyst staffing** -- WTS employs the Analysts who support Jurisdiction review across all client governments"
Bullet "**Permitting support** -- professional guidance for local governments managing wireless facility applications under federal telecommunications law"
Bullet "**Training** -- this course is built and maintained by WTS to ensure all platform users operate correctly and compliantly"
Img $imgWTS 4.5 "Wireless Tower Solutions -- wirelesstowersolutions.com. WTS builds AMP, staffs the Analysts, and trains the users."

H3 "WTS's Position in the Process"
P "WTS sits between local governments and wireless carriers. Understanding where WTS fits -- and where it does not -- is essential for every user."
$th1 = @("Party", "Relationship to WTS", "Role in AMP")
$tr1 = @(
    @("**Local Government (Jurisdiction)**", "WTS serves Jurisdictions as their platform provider and Analyst support", "Makes all final approval decisions"),
    @("**WTS**", "Platform provider and Analyst employer", "Builds and maintains AMP; provides Analysts to support review"),
    @("**Wireless Carrier / Agent**", "WTS provides the platform Agents use to submit applications", "Submits applications; responds to revision requests")
)
$tw1 = @(1.8, 2.5, 2.0)
AddTable $th1 $tr1 $tw1

H3 "What WTS Does Not Do"
P "This distinction matters:"
Bullet "WTS does **not** approve or deny applications -- only the Jurisdiction can do that"
Bullet "WTS does **not** represent or advocate for wireless carriers"
Bullet "WTS does **not** set or modify local ordinance requirements"
Bullet "WTS does **not** make regulatory decisions of any kind"
KP "Key Point: WTS serves local governments. WTS Analysts work on behalf of the Jurisdiction's review process -- checking completeness, flagging issues, and advising on process. WTS never approves an application. That authority belongs exclusively to the Jurisdiction."

# =====================================================================
Write-Host "  Problem section..."
H2 "The Problem AMP Solves"
H3 "What Came Before AMP"
P "Before a platform like AMP exists, wireless facility permitting at the local government level typically involves:"
Bullet "**Email submissions** -- applications arrive as email attachments with no tracking, no receipt confirmation, and no audit trail"
Bullet "**Paper applications** -- forms received by mail or in person, manually filed, vulnerable to loss or damage"
Bullet "**Manual tracking** -- staff use spreadsheets or calendar reminders to track application status and deadlines"
Bullet "**No standardized format** -- each carrier submits differently; Jurisdictions must interpret and reformat inconsistent data"
Bullet "**No automated deadline tracking** -- federal Shot Clock requirements must be tracked manually, creating legal risk when deadlines are missed"
Bullet "**No single system of record** -- when disputes arise, there is no authoritative source for what was submitted, what was reviewed, or what was decided"
P "The result is a process that is slow, inconsistent, difficult to audit, and legally vulnerable."

H3 "What AMP Provides Instead"
P "AMP replaces all of these manual processes with a single, structured system:"
Bullet "Applications are submitted online through a standardized form (the PIF) -- no email, no paper"
Bullet "Every document is uploaded directly into the project -- no attachments floating in inboxes"
Bullet "Status tracks exactly where every project stands and who needs to act next"
Bullet "The Shot Clock is tracked automatically -- the dashboard shows how many days remain for every active project"
Bullet "Every action is logged automatically -- creating the permanent Official Record required by federal law"
Bullet "All communication between Agent, Analyst, and Jurisdiction happens inside AMP -- no side conversations in email"
Img $imgProblem 5.0 "AMP replaces fragmented email and paper submissions with a single structured online workflow. Every project, every status, every deadline -- in one place."

# =====================================================================
Write-Host "  Regulatory section..."
H2 "The Regulatory Context"
P "To understand why AMP matters, you need to understand the regulatory environment it operates in."

H3 "Federal Telecommunications Law"
P "The Telecommunications Act of 1996 -- and the FCC regulations that followed -- fundamentally changed how local governments interact with wireless carriers. Key provisions relevant to AMP:"
P "**Local governments must act -- but cannot unreasonably deny.** Under federal law, local governments have the right to regulate the placement, construction, and modification of wireless facilities. However, they cannot unreasonably discriminate between carriers, cannot prohibit wireless service in their jurisdiction, and cannot deny applications based on radio frequency (RF) emissions concerns where the carrier has demonstrated FCC compliance."
P "**Decisions must be in writing and supported by a record.** Any local government decision on a wireless facility application -- approval, denial, or conditional approval -- must be supported by a written record. A verbal decision or a decision with no documentation behind it has no legal standing."
P "**The Official Record requirement.** Federal law requires local governments to maintain a complete record of all communications and decisions related to wireless facility applications. This is what AMP's Official Record fulfills automatically."

H3 "The Shot Clock"
P "Among the most consequential provisions of federal telecom regulations is the Shot Clock -- a defined period within which local governments must act on a wireless facility application:"
Bullet "**Standard applications** (New Tower, Colocation, Modification): **150 days**"
Bullet "**Small Wireless Facility (SWF) -- single site**: **60 days**"
Bullet "**Small Wireless Facility (SWF) -- batch up to 25 sites**: **90 days**"
P "If the local government does not act within the Shot Clock window, the application is **deemed approved by operation of federal law** -- the carrier gains the right to build without waiting for local approval. This is not a procedural penalty; it is a federal legal consequence."
P "AMP tracks the Shot Clock automatically for every active project. The number of days remaining appears on the dashboard for every project under active Jurisdiction review. Before AMP, this tracking was done manually -- and missed deadlines had the same legal consequences."
KP "Key Point: The Shot Clock is federal law. It cannot be waived, extended, or ignored. AMP makes it visible and trackable. Without AMP, missing a Shot Clock deadline is an easy mistake to make -- with AMP, it is a deliberate choice to ignore information on the dashboard."

H3 "What This Means for Every AMP User"
P "Every user in AMP -- Agent, Analyst, or Jurisdiction -- is operating within this federal regulatory framework. The rules in AMP are not arbitrary software requirements. They reflect federal legal requirements:"
Bullet "The Official Record exists because federal law requires it"
Bullet "The Shot Clock is tracked because federal law mandates it"
Bullet "Approval authority is exclusively held by the Jurisdiction because federal law places it there"
Bullet "Certified documents are required because health, safety, and welfare regulations demand them"

# =====================================================================
Write-Host "  What AMP Does..."
H2 "What AMP Does"
P "At its core, AMP does five things:"
Numbered 1 "**Centralizes** -- all applications, all documents, all communications, and all decisions in one place"
Numbered 2 "**Standardizes** -- every application follows the same structured format regardless of carrier, agent, or jurisdiction"
Numbered 3 "**Tracks** -- every project status, every Shot Clock deadline, every review action in real time"
Numbered 4 "**Records** -- every action is logged automatically in the permanent Official Record"
Numbered 5 "**Enforces** -- AMP enforces workflow rules so applications cannot advance until required steps are completed"

# =====================================================================
Write-Host "  Five Core Areas..."
H2 "The Five Core Areas of AMP"
P "AMP is organized into five interconnected areas. Understanding each area -- and how they connect -- is the foundation for everything else in this course."

H3 "Core Area 1: Projects"
P "In AMP, every wireless facility application is organized as a **project**. A project is the central unit of work in the system. Everything -- forms, documents, communications, status, fees, and approvals -- lives inside a project."
P "A project contains:"
Bullet "All information entered by the Agent (PIF, component forms)"
Bullet "All supporting documents uploaded during the application"
Bullet "The complete review history -- every action, every comment, every decision"
Bullet "All communications between Agent, Analyst, and Jurisdiction"
Bullet "Fee and invoice records"
P "Projects move through defined lifecycle stages from creation through final inspection and closure. The current stage is always reflected in the project's status."

H3 "Core Area 2: The Dashboard"
P "The Dashboard is the first screen every user sees after logging in. It provides a real-time view of every project the user has access to, organized in rows with status columns, Shot Clock values, and last activity timestamps."
P "The dashboard is not just a list -- it is a decision-making tool. Every color, every column, and every value tells you something specific about the state of a project and what needs to happen next."
P "Different users see different projects on the dashboard based on their role:"
Bullet "**Agents** see only their own projects"
Bullet "**Analysts** see all projects in their assigned jurisdiction(s)"
Bullet "**Clients (Jurisdictions)** see all projects in their jurisdiction"
Img $imgDashboard 5.5 "The Dashboard -- every active project, every status, every deadline. Lesson 3 covers how to read the dashboard in full detail."

H3 "Core Area 3: The Project Information Form (PIF)"
P "The Project Information Form is the mandatory starting point for every project in AMP. Nothing begins without it."
P "The PIF collects the foundational information that defines the entire project:"
Bullet "What type of application this is (New Tower, Colocation, SWF, etc.)"
Bullet "Where the project is located and which Jurisdiction reviews it"
Bullet "Who is submitting and who the carrier is"
Bullet "What the project description is"
P "The PIF's project type selection is the most critical single field in the entire platform. It determines which components are required, what fees apply, which regulatory pathway the project follows, and how the Shot Clock is calculated."
Img $imgPIF 4.5 "The PIF is the gateway to every project. The project type selected here drives every downstream workflow decision."

H3 "Core Area 4: Components"
P "After the PIF is approved, AMP activates the **components** required for the project type. Components are the structured forms and document collections that provide all the technical, legal, and engineering data needed for review."
P "AMP has four components:"
$th2 = @("Component", "Full Name", "When Required")
$tr2 = @(
    @("**PIF**", "Project Information Form", "ALL projects -- always the first step"),
    @("**ATF**", "Add Tower Form", "When adding a new tower site to the WTS database"),
    @("**NWF**", "New Wireless Facility", "New Tower projects only"),
    @("**TWF**", "Tower/Wireless Facility Registration", "When tower data is not current in the WTS database"),
    @("**NCM**", "New Colocation/Modification", "ALL project types -- no exceptions")
)
$tw2 = @(1.2, 2.5, 2.6)
AddTable $th2 $tr2 $tw2
P "Components collect the specific technical and legal data that Analysts and Jurisdictions review to make approval decisions. Lesson 5 covers each component in full detail."

H3 "Core Area 5: Workflow and Status"
P "Every project and every component in AMP has a **status** -- a color-coded indicator of where things stand and what needs to happen next. Status is what makes the dashboard readable at a glance."
P "There are eleven project statuses in AMP. Each has a specific color, meaning, and required action:"
$th3 = @("Status", "Color", "General Meaning")
$tr3 = @(
    @("Draft", "None", "Being created -- not yet submitted"),
    @("Saved", "Teal/Cyan", "Saved but not yet submitted"),
    @("Submitted / In Review", "Blue", "Under active review"),
    @("Pending", "Yellow", "Moving through a step -- monitor it"),
    @("Invoiced", "Orange", "Fees issued -- payment required"),
    @("Revise", "Red", "Corrections required immediately"),
    @("AIT Revise", "Red", "AIT track -- corrections required"),
    @("Awaiting Revision", "Red", "Additional information required"),
    @("Approved", "Green", "This step is done"),
    @("AIT Approved", "Dark Blue", "Approved under Alternative Inspection Track"),
    @("Completed", "Green", "Project closed -- all records stored")
)
$tw3 = @(1.8, 1.5, 3.0)
AddTable $th3 $tr3 $tw3
P "Lesson 3 covers the full status system, what each status means, and how to read the dashboard correctly."

# =====================================================================
Write-Host "  System of Record..."
H2 "AMP as the System of Record"

H3 'What "System of Record" Means'
P "When AMP is described as the **system of record**, it means that AMP is the authoritative, legally recognized source for all information about every wireless facility application. Not email. Not shared drives. Not paper files. AMP."
P "The Official Record in AMP is:"
Bullet "**Automatic** -- created continuously as users take actions"
Bullet "**Complete** -- every field, every document, every communication, every decision"
Bullet "**Permanent** -- nothing can be deleted or overwritten once submitted"
Bullet "**Attributed** -- every entry is linked to the specific authenticated user who made it"
Bullet "**Timestamped** -- every action has a recorded date and time"

H3 "Why It Matters"
P "For local governments, the Official Record is the legal foundation for every approval or denial decision. If a carrier challenges a Jurisdiction's decision, the Official Record in AMP is the evidence that decision was made correctly and for documented reasons."
P "For Agents and carriers, the Official Record proves that applications were submitted on time, completely, and exactly as submitted -- protecting them if a Jurisdiction claims otherwise."
P "For WTS and Analysts, the Official Record documents their advisory role -- showing that they reviewed for completeness and flagged issues appropriately."
KP "Key Point: All communication about an application must happen inside AMP. Phone calls, emails, and conversations outside the platform create information that is not part of the Official Record -- and therefore does not legally exist as part of the application process."

# =====================================================================
Write-Host "  How Areas Connect..."
H2 "How the Five Core Areas Connect"
P "These five areas are not independent -- they are one system:"
Numbered 1 "The **Agent** creates a project and completes the **PIF**"
Numbered 2 "The **PIF** defines the project type, jurisdiction, and scope"
Numbered 3 "AMP activates the required **components** based on the PIF"
Numbered 4 "The **Agent** completes and submits the components"
Numbered 5 "The project appears on the **dashboard** with its current **status**"
Numbered 6 "The **Analyst** reviews the application and the **Jurisdiction** makes decisions"
Numbered 7 "**Status** updates in real time as actions are taken"
Numbered 8 "The **Shot Clock** counts down -- visible on the dashboard -- until the Jurisdiction acts"
Numbered 9 "Every action writes to the **Official Record** automatically"
Numbered 10 "When the project is approved, closed, and final inspection complete -- **status becomes Completed**"

# =====================================================================
Write-Host "  Why AMP Matters..."
H2 "Why AMP Matters -- By User Type"

H3 "For Jurisdiction Users (Clients)"
P "AMP gives local government staff the tools to manage a complex, regulated workload with confidence:"
Bullet "Every application arrives in a standardized format -- no more interpreting incomplete or inconsistent submissions"
Bullet "Required documents are clearly defined and checked at submission -- reviewers work from complete packages"
Bullet "The Shot Clock is tracked automatically -- deadline violations are visible before they happen"
Bullet "The Official Record documents every decision -- providing legal protection if applications are challenged"
Bullet "Applications are visible across the jurisdiction -- no single reviewer is the only person who knows a project exists"

H3 "For Agent Users (Carrier Representatives)"
P "AMP provides Agents with a clear, consistent path to approval:"
Bullet "The structured PIF ensures nothing is forgotten at the time of submission"
Bullet "Status updates are visible in real time -- Agents always know where their application stands"
Bullet "The Official Record proves submissions were made on time and correctly -- protecting Agents if a Jurisdiction fails to act"
Bullet "The Shot Clock is visible to Agents -- giving them a legal basis to escalate if a Jurisdiction misses its deadline"
Bullet "All revision requests are formal and documented -- Agents know exactly what needs to be corrected and can prove they responded"

H3 "For Analyst Users (WTS Staff)"
P "AMP allows WTS Analysts to support multiple jurisdictions efficiently:"
Bullet "Dashboard view covers all projects across assigned jurisdictions -- nothing falls through the cracks"
Bullet "Role-based access means Analysts can review without being able to make final approval decisions -- preserving proper authority"
Bullet "All communications are inside the platform -- Analysts advisory role is documented in the Official Record"
Bullet "Completeness review is supported by the structured format -- required fields and documents are visible and checkable"

# =====================================================================
Write-Host "  Key Takeaways..."
H2 "Key Takeaways"
Bullet "AMP is a compliance platform built by WTS to manage wireless facility permitting for local governments -- end to end from initial application through final inspection"
Bullet "Before AMP, permitting relied on email, paper, and manual tracking -- creating audit gaps, compliance risk, and federal deadline exposure"
Bullet "Federal telecommunications law requires local governments to act within Shot Clock deadlines, maintain an Official Record, and support all decisions with documentation -- AMP fulfills all three automatically"
Bullet "AMP is organized into five core areas: **Projects, Dashboard, PIF, Components, and Status** -- all five work together as one system"
Bullet "**AMP is the system of record** -- all application activity must happen inside AMP; communications outside the platform are not part of the Official Record"
Bullet "The Shot Clock is federal law -- AMP makes it visible and trackable; missing it is a serious legal consequence"

# =====================================================================
Write-Host "  Checkpoint Question..."
H2 "Checkpoint Question"
P "**Scenario:** A new Jurisdiction staff member is reviewing her first wireless facility application. She receives an email from the Agent with the structural analysis attached and a note explaining the project. She replies to the email asking a few clarifying questions and the Agent responds by email with the answers. She approves the project via email and sends the approval letter by mail."
P "What is the problem with this workflow -- and what should have happened instead?"
P "**A)** Nothing is wrong. Email is an acceptable channel for application review as long as the Jurisdiction keeps copies. AMP is optional when both parties agree to use email."
P "**B)** The only issue is the paper approval letter. Everything else -- email submissions, email questions and answers -- is acceptable as long as the email is saved somewhere."
P "**C)** This entire workflow bypasses AMP. The application should have been submitted through AMP by the Agent. All review communications, questions, and responses should have occurred inside AMP. The approval decision should have been made and recorded in AMP. None of this activity exists in the Official Record, which means the Jurisdiction's approval has no documented basis under federal law."
P "**D)** The workflow is fine as long as the Jurisdiction manually enters the email correspondence into AMP afterward."

NP; GoEnd; SetNormal
$sel.Font.Color = $cCyan
$sel.Font.Bold  = $true
$sel.TypeText("Correct Answer: C")
$sel.Font.Bold  = $false
$sel.Font.Color = $cBlack
$sel.TypeText(" -- AMP is not optional. It is the system of record. When an Agent submits outside AMP and a Jurisdiction reviews outside AMP, everything that happens -- every question, every answer, every document, every decision -- is invisible to the Official Record. Under federal telecommunications law, the Jurisdiction's approval decision must be supported by a documented record. An email chain and a paper letter are not the Official Record. If this application were ever challenged, the Jurisdiction would have no defensible basis for its decision. Everything should have been done inside AMP from the start.")

P "End of Lesson 1  |  Next: Lesson 2 -- User Roles and Responsibilities"

# =====================================================================
# Footer
Write-Host "  Setting footer..."
try {
    $ftr = $doc.Sections.Item(1).Footers.Item(1)
    $ftr.Range.Select()
    $word.Selection.Collapse(1)
    $word.Selection.Font.Color = $cCyan
    $word.Selection.Font.Size  = 9
    $word.Selection.Font.Bold  = $false
    $word.Selection.ParagraphFormat.Alignment = 1
    $word.Selection.TypeText("AMP Fundamentals -- Lesson 1: What Is AMP and Why It Exists")
} catch { Write-Host "  Footer FAILED: $_" }

# =====================================================================
# Save
Write-Host "  Saving..."
if (Test-Path $outPath) { Remove-Item $outPath -Force }
$doc.SaveAs([ref]$outPath, [ref]16)
$doc.Close($false)
$word.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
[GC]::Collect()

if (Test-Path $outPath) {
    $kb = [math]::Round((Get-Item $outPath).Length / 1KB, 1)
    Write-Host "Done. $kb KB -- $outPath"
} else {
    Write-Host "ERROR: output file not found"
}
