# combine-course1.ps1
# Combines all 6 AMP Fundamentals lessons into one document with a cover page.
# Output: Introduction-to-AMP-and-WTS.docx

$base     = "C:\Users\richa\Documents\Claude Projects\Wireless-Training-LMS\Wireless-LMS-Project"
$logoPath = "$base\assets\WTS-Oval-Logo-Blue.png"
$outPath  = "$base\lms\Introduction-to-AMP-and-WTS.docx"

$lessons = @(
    "$base\lms\Lesson-01-What-Is-AMP.docx",
    "$base\lms\Lesson-02-User-Roles-Responsibilities.docx",
    "$base\lms\Lesson-03-Projects-Dashboard-Status.docx",
    "$base\lms\Lesson-04-Project-Information-Form-PIF.docx",
    "$base\lms\Lesson-05-Component-Types-NWF-TWF-NCM.docx",
    "$base\lms\Lesson-06-Security-Compliance-Official-Record.docx"
)

$cCyan  = 15707648   # #00AEEF -- WTS cyan
$cNavy  = 9064218    # #1A4F8A -- WTS navy
$cBlack = 0
$cGray  = 6710886    # #666666

Write-Host "Combining Course 1 into Introduction-to-AMP-and-WTS.docx..."

# Verify all source files exist
$missing = $lessons | Where-Object { -not (Test-Path $_) }
if ($missing.Count -gt 0) {
    Write-Host "ERROR: Missing files:"
    $missing | ForEach-Object { Write-Host "  $_" }
    exit 1
}
Write-Host "  All 6 lesson files found."

$word = New-Object -ComObject Word.Application
$word.Visible = $true
$word.WindowState = 2   # minimized but visible so Selection works

$doc = $word.Documents.Add()
$sel = $word.Selection

# =====================================================================
# COVER PAGE
# =====================================================================

# --- Logo (large, centered) ---
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 54
$sel.ParagraphFormat.SpaceAfter  = 0
$sel.ParagraphFormat.LeftIndent  = 0
$sel.ParagraphFormat.RightIndent = 0
$sel.Font.Size   = 11
$sel.Font.Bold   = $false
$sel.Font.Italic = $false
$sel.Font.Color  = $cBlack

if (Test-Path $logoPath) {
    $shape = $sel.InlineShapes.AddPicture($logoPath, $false, $true)
    $ratio = $shape.Height / $shape.Width
    $newW  = $word.InchesToPoints(3.0)
    $shape.Width  = $newW
    $shape.Height = $newW * $ratio
    Write-Host "  Cover logo inserted"
} else {
    $sel.TypeText("[WTS LOGO]")
}

# --- Cyan rule line under logo ---
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 18
$sel.ParagraphFormat.SpaceAfter  = 0
$sel.Font.Size = 4
$sel.Font.Color = $cCyan
try {
    $sel.ParagraphFormat.Borders.Item(3).LineStyle = 1   # wdLineStyleSingle, bottom border
    $sel.ParagraphFormat.Borders.Item(3).LineWidth = 9   # wdLineWidth075pt
    $sel.ParagraphFormat.Borders.Item(3).Color    = $cCyan
} catch {}
$sel.TypeText(" ")   # space so the paragraph has content and renders the border

# --- Main title ---
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 30
$sel.ParagraphFormat.SpaceAfter  = 0
$sel.Font.Color = $cNavy
$sel.Font.Bold  = $true
$sel.Font.Size  = 30
$sel.TypeText("Introduction to AMP and WTS")

# --- Subtitle ---
$sel.TypeParagraph()
$sel.ParagraphFormat.SpaceBefore = 10
$sel.Font.Color = $cCyan
$sel.Font.Bold  = $false
$sel.Font.Size  = 15
$sel.TypeText("AMP Fundamentals Course 1  --  Complete Reference Guide")

# --- Course description ---
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 36
$sel.ParagraphFormat.SpaceAfter  = 0
$sel.ParagraphFormat.LeftIndent  = $word.InchesToPoints(0.75)
$sel.ParagraphFormat.RightIndent = $word.InchesToPoints(0.75)
$sel.Font.Color  = $cGray
$sel.Font.Italic = $true
$sel.Font.Bold   = $false
$sel.Font.Size   = 11
$sel.TypeText("This guide covers all six lessons of the AMP Fundamentals course: the platform overview, user roles and responsibilities, dashboard and project management, the Project Information Form, component types, and security and compliance. It is the complete reference for all AMP platform users.")

# --- Lesson list ---
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 30
$sel.ParagraphFormat.SpaceAfter  = 0
$sel.ParagraphFormat.LeftIndent  = 0
$sel.ParagraphFormat.RightIndent = 0
$sel.Font.Color  = $cNavy
$sel.Font.Italic = $false
$sel.Font.Bold   = $true
$sel.Font.Size   = 11
$sel.TypeText("Contents")

$lessonTitles = @(
    "Lesson 1: What Is AMP and Why It Exists",
    "Lesson 2: User Roles and Responsibilities",
    "Lesson 3: Projects, Dashboard, and Status",
    "Lesson 4: The Project Information Form (PIF)",
    "Lesson 5: Component Types (NWF, TWF, NCM, ATF)",
    "Lesson 6: Security, Compliance, and the Official Record"
)
foreach ($lt in $lessonTitles) {
    $sel.TypeParagraph()
    $sel.ParagraphFormat.Alignment   = 1
    $sel.ParagraphFormat.SpaceBefore = 4
    $sel.Font.Color  = $cBlack
    $sel.Font.Bold   = $false
    $sel.Font.Italic = $false
    $sel.Font.Size   = 11
    $sel.TypeText($lt)
}

# --- Footer line ---
$sel.TypeParagraph()
try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
$sel.ParagraphFormat.Alignment   = 1
$sel.ParagraphFormat.SpaceBefore = 48
$sel.Font.Color  = $cNavy
$sel.Font.Bold   = $false
$sel.Font.Italic = $false
$sel.Font.Size   = 11
$sel.TypeText("Wireless Tower Solutions  |  wirelesstowersolutions.com  |  2026")

# --- Page break after cover ---
$sel.EndKey(6) | Out-Null
$sel.InsertBreak(7)   # wdPageBreak = 7
Write-Host "  Cover page done."

# =====================================================================
# INSERT EACH LESSON
# =====================================================================

for ($i = 0; $i -lt $lessons.Count; $i++) {
    $lessonPath = $lessons[$i]
    $leaf = Split-Path $lessonPath -Leaf
    Write-Host "  Inserting $leaf..."

    $sel.EndKey(6) | Out-Null
    $sel.InsertFile($lessonPath, "", $false, $false, $false)

    # Page break after every lesson except the last
    if ($i -lt ($lessons.Count - 1)) {
        $sel.EndKey(6) | Out-Null
        $sel.InsertBreak(7)
    }
}

# =====================================================================
# FOOTER ON ALL SECTIONS
# =====================================================================

Write-Host "  Setting footers..."
try {
    for ($s = 1; $s -le $doc.Sections.Count; $s++) {
        $ftr = $doc.Sections.Item($s).Footers.Item(1)
        # Link to previous unless it's the cover page section
        if ($s -gt 1) {
            try { $ftr.LinkToPrevious = $false } catch {}
        }
        # Clear and set footer text
        $ftr.Range.Select()
        $word.Selection.Collapse(1)
        $word.Selection.Font.Color = $cCyan
        $word.Selection.Font.Size  = 9
        $word.Selection.Font.Bold  = $false
        $word.Selection.ParagraphFormat.Alignment = 1
        $word.Selection.TypeText("Introduction to AMP and WTS  |  AMP Fundamentals Course 1  |  Wireless Tower Solutions")
    }
} catch { Write-Host "  Footer pass FAILED: $_" }

# =====================================================================
# SAVE
# =====================================================================

Write-Host "  Saving..."
if (Test-Path $outPath) { Remove-Item $outPath -Force }
$doc.SaveAs([ref]$outPath, [ref]16)
$doc.Close($false)

$word.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
[GC]::Collect()

if (Test-Path $outPath) {
    $kb = [math]::Round((Get-Item $outPath).Length / 1KB, 1)
    Write-Host ""
    Write-Host "Done. $kb KB"
    Write-Host "Saved: $outPath"
} else {
    Write-Host "ERROR: output file not found"
}
