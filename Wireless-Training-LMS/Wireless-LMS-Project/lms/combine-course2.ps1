# combine-course2.ps1
# Combines all 7 Course 2 lessons into one document with a cover page.
# Output: Introduction-to-AMP-Jurisdiction-Reviewers.docx

$base     = "C:\Users\richa\Documents\Claude Projects\Wireless-Training-LMS\Wireless-LMS-Project"
$logoPath = "$base\assets\WTS-Oval-Logo-Blue.png"
$outPath  = "$base\lms\AMP-for-Jurisdiction-Reviewers-Complete.docx"

$lessons = @(
    "$base\lms\Course2-Lesson-01-Authority-and-Responsibility.docx",
    "$base\lms\Course2-Lesson-02-Dashboard-Workload.docx",
    "$base\lms\Course2-Lesson-03-Reviewing-the-PIF.docx",
    "$base\lms\Course2-Lesson-04-Reviewing-Components.docx",
    "$base\lms\Course2-Lesson-05-Shot-Clock.docx",
    "$base\lms\Course2-Lesson-06-Making-Decisions.docx",
    "$base\lms\Course2-Lesson-07-Communication-Inspection-Closure.docx"
)

$cCyan  = 15707648
$cNavy  = 9064218
$cBlack = 0
$cGray  = 6710886

$missing = $lessons | Where-Object { -not (Test-Path $_) }
if ($missing.Count -gt 0) {
    Write-Host "ERROR: Missing files:"; $missing | ForEach-Object { Write-Host "  $_" }; exit 1
}
Write-Host "All 7 lesson files found."

$word = New-Object -ComObject Word.Application
$word.Visible = $true
$word.WindowState = 2

$doc = $word.Documents.Add()
$sel = $word.Selection

# ---- Cover page ----
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 54
$sel.ParagraphFormat.SpaceAfter  = 0
$sel.ParagraphFormat.LeftIndent  = 0
$sel.ParagraphFormat.RightIndent = 0
$sel.Font.Size = 11; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Color = $cBlack

if (Test-Path $logoPath) {
    $shape = $sel.InlineShapes.AddPicture($logoPath, $false, $true)
    $ratio = $shape.Height / $shape.Width
    $newW  = $word.InchesToPoints(3.0)
    $shape.Width = $newW; $shape.Height = $newW * $ratio
    Write-Host "  Cover logo inserted"
}

# Cyan rule
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment = 1; $sel.ParagraphFormat.SpaceBefore = 18; $sel.ParagraphFormat.SpaceAfter = 0
$sel.Font.Size = 4; $sel.Font.Color = $cCyan
try {
    $sel.ParagraphFormat.Borders.Item(3).LineStyle = 1
    $sel.ParagraphFormat.Borders.Item(3).LineWidth = 9
    $sel.ParagraphFormat.Borders.Item(3).Color = $cCyan
} catch {}
$sel.TypeText(" ")

# Title
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment = 1; $sel.ParagraphFormat.SpaceBefore = 30; $sel.ParagraphFormat.SpaceAfter = 0
$sel.Font.Color = $cNavy; $sel.Font.Bold = $true; $sel.Font.Size = 28
$sel.TypeText("AMP for Government / Jurisdiction Reviewers")

# Subtitle
$sel.TypeParagraph()
$sel.ParagraphFormat.SpaceBefore = 10
$sel.Font.Color = $cCyan; $sel.Font.Bold = $false; $sel.Font.Size = 15
$sel.TypeText("Course 2  --  Complete Reference Guide")

# Description
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 36; $sel.ParagraphFormat.SpaceAfter = 0
$sel.ParagraphFormat.LeftIndent  = $word.InchesToPoints(0.75)
$sel.ParagraphFormat.RightIndent = $word.InchesToPoints(0.75)
$sel.Font.Color = $cGray; $sel.Font.Italic = $true; $sel.Font.Bold = $false; $sel.Font.Size = 11
$sel.TypeText("This guide covers the full Jurisdiction reviewer workflow in AMP: legal authority and responsibility, dashboard management and prioritization, PIF and component review, Shot Clock management, decision-making standards and prohibited grounds, and communication, inspection, and project closure. Prerequisite: AMP Fundamentals (Course 1).")

# Contents
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 30; $sel.ParagraphFormat.SpaceAfter = 0
$sel.ParagraphFormat.LeftIndent  = 0; $sel.ParagraphFormat.RightIndent = 0
$sel.Font.Color = $cNavy; $sel.Font.Italic = $false; $sel.Font.Bold = $true; $sel.Font.Size = 11
$sel.TypeText("Contents")

$lessonTitles = @(
    "Lesson 1: Your Authority and Responsibility as a Jurisdiction Reviewer",
    "Lesson 2: Reading the Dashboard -- Workload Management and Priority",
    "Lesson 3: Reviewing the Project Information Form (PIF)",
    "Lesson 4: Reviewing Technical Components",
    "Lesson 5: The Shot Clock -- Federal Deadline Management",
    "Lesson 6: Making Decisions -- Approvals, Revisions, and Denials",
    "Lesson 7: Communication, Final Inspection, and Project Closure"
)
foreach ($lt in $lessonTitles) {
    $sel.TypeParagraph()
    $sel.ParagraphFormat.Alignment = 1; $sel.ParagraphFormat.SpaceBefore = 4
    $sel.Font.Color = $cBlack; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Size = 11
    $sel.TypeText($lt)
}

# Branding footer line
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 48; $sel.ParagraphFormat.SpaceAfter = 0
$sel.ParagraphFormat.LeftIndent  = 0; $sel.ParagraphFormat.RightIndent = 0
$sel.Font.Color = $cNavy; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Size = 11
$sel.TypeText("Wireless Tower Solutions  |  wirelesstowersolutions.com  |  2026")

# Page break
$sel.EndKey(6) | Out-Null
$sel.InsertBreak(7)
Write-Host "  Cover page done."

# ---- Insert lessons ----
for ($i = 0; $i -lt $lessons.Count; $i++) {
    $leaf = Split-Path $lessons[$i] -Leaf
    Write-Host "  Inserting $leaf..."
    $sel.EndKey(6) | Out-Null
    $sel.InsertFile($lessons[$i], "", $false, $false, $false)
    if ($i -lt ($lessons.Count - 1)) {
        $sel.EndKey(6) | Out-Null
        $sel.InsertBreak(7)
    }
}

# ---- Footer ----
Write-Host "  Setting footers..."
try {
    for ($s = 1; $s -le $doc.Sections.Count; $s++) {
        $ftr = $doc.Sections.Item($s).Footers.Item(1)
        try { if ($s -gt 1) { $ftr.LinkToPrevious = $false } } catch {}
        $ftr.Range.Select()
        $word.Selection.Collapse(1)
        $word.Selection.Font.Color = $cCyan; $word.Selection.Font.Size = 9; $word.Selection.Font.Bold = $false
        $word.Selection.ParagraphFormat.Alignment = 1
        $word.Selection.TypeText("AMP for Government / Jurisdiction Reviewers  |  Course 2  |  Wireless Tower Solutions")
    }
} catch { Write-Host "  Footer FAILED: $_" }

# ---- Save ----
Write-Host "  Saving..."
if (Test-Path $outPath) { Remove-Item $outPath -Force }
$doc.SaveAs([ref]$outPath, [ref]16)
$doc.Close($false)
$word.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
[GC]::Collect()

if (Test-Path $outPath) {
    $kb = [math]::Round((Get-Item $outPath).Length / 1KB, 1)
    Write-Host "`nDone. $kb KB -- $outPath"
} else { Write-Host "ERROR: output file not found" }
