# build-course2.ps1
# Builds all 7 Course 2 lessons as formatted .docx files with WTS brand styling.
# Run from the lms\ directory or use full paths as configured below.

$base     = "C:\Users\richa\Documents\Claude Projects\Wireless-Training-LMS\Wireless-LMS-Project"
$logoPath = "$base\assets\WTS-Oval-Logo-Blue.png"
$lmsDir   = "$base\lms"

$cCyan  = 15707648   # #00AEEF -- WTS cyan
$cNavy  = 9064218    # #1A4F8A -- WTS navy
$cWhite = 16777215
$cBlack = 0
$cGray  = 6710886    # #666666

$lessons = @(
    @{ md = "$lmsDir\Course2-Lesson-01-Authority-and-Responsibility.md";     out = "$lmsDir\Course2-Lesson-01-Authority-and-Responsibility.docx" },
    @{ md = "$lmsDir\Course2-Lesson-02-Dashboard-Workload.md";               out = "$lmsDir\Course2-Lesson-02-Dashboard-Workload.docx" },
    @{ md = "$lmsDir\Course2-Lesson-03-Reviewing-the-PIF.md";                out = "$lmsDir\Course2-Lesson-03-Reviewing-the-PIF.docx" },
    @{ md = "$lmsDir\Course2-Lesson-04-Reviewing-Components.md";             out = "$lmsDir\Course2-Lesson-04-Reviewing-Components.docx" },
    @{ md = "$lmsDir\Course2-Lesson-05-Shot-Clock.md";                       out = "$lmsDir\Course2-Lesson-05-Shot-Clock.docx" },
    @{ md = "$lmsDir\Course2-Lesson-06-Making-Decisions.md";                 out = "$lmsDir\Course2-Lesson-06-Making-Decisions.docx" },
    @{ md = "$lmsDir\Course2-Lesson-07-Communication-Inspection-Closure.md"; out = "$lmsDir\Course2-Lesson-07-Communication-Inspection-Closure.docx" }
)

# ===== WORD DOCUMENT BUILDER =====

function Build-Lesson($mdPath, $outPath, $word) {
    $leaf = Split-Path $outPath -Leaf
    Write-Host "`n== Building $leaf =="

    $lines = [System.IO.File]::ReadAllLines($mdPath, [System.Text.Encoding]::UTF8)

    $doc = $word.Documents.Add()
    $sel = $word.Selection

    # ---- Logo ----
    try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
    $sel.ParagraphFormat.Alignment   = 1
    $sel.ParagraphFormat.SpaceAfter  = 6
    $sel.ParagraphFormat.SpaceBefore = 10
    $sel.ParagraphFormat.LeftIndent  = 0
    $sel.ParagraphFormat.RightIndent = 0
    $sel.Font.Size = 11; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Color = $cBlack
    if (Test-Path $logoPath) {
        $shape = $sel.InlineShapes.AddPicture($logoPath, $false, $true)
        $ratio = $shape.Height / $shape.Width
        $newW  = $word.InchesToPoints(1.8)
        $shape.Width = $newW; $shape.Height = $newW * $ratio
    }

    # ---- Parse and render markdown ----
    $inBulletList  = $false
    $inNumberList  = $false
    $inTable       = $false
    $tableHeaders  = @()
    $tableRows     = @()
    $listNumCounter = 0
    $skipBlank     = $false

    foreach ($rawLine in $lines) {
        $line = $rawLine.TrimEnd()

        # ---- Table accumulation ----
        if ($line -match '^\|') {
            if (-not $inTable) {
                $inTable      = $true
                $tableHeaders = @()
                $tableRows    = @()
            }
            # Skip separator rows (|---|---|)
            if ($line -match '^\|\s*[-:]+\s*\|') { continue }
            # Parse cells
            $cells = ($line -split '\|' | Where-Object { $_ -ne '' }) | ForEach-Object { $_.Trim() }
            if ($tableHeaders.Count -eq 0) {
                $tableHeaders = $cells
            } else {
                $tableRows += ,@($cells)
            }
            continue
        } else {
            if ($inTable) {
                # Flush table
                Render-Table $doc $sel $word $tableHeaders $tableRows
                $inTable = $false; $tableHeaders = @(); $tableRows = @()
            }
        }

        # ---- Skip HR lines ----
        if ($line -match '^---+$') { continue }

        # ---- Blank line ----
        if ($line -eq '') {
            $inBulletList = $false; $inNumberList = $false; $listNumCounter = 0
            continue
        }

        # ---- Headings ----
        if ($line -match '^# (.+)$') {
            $txt = $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Heading 1") } catch {}
            $sel.Font.Color = $cNavy; $sel.Font.Bold = $true; $sel.Font.Size = 16
            Render-Inline $sel $txt
            continue
        }
        if ($line -match '^## (.+)$') {
            $txt = $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Heading 2") } catch {}
            $sel.Font.Color = $cNavy; $sel.Font.Bold = $true; $sel.Font.Size = 13
            Render-Inline $sel $txt
            continue
        }
        if ($line -match '^### (.+)$') {
            $txt = $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Heading 3") } catch {}
            $sel.Font.Color = $cCyan; $sel.Font.Bold = $true; $sel.Font.Size = 11
            Render-Inline $sel $txt
            continue
        }

        # ---- Key Point callout (> **Key Point:**) ----
        if ($line -match '^>\s*\*\*Key Point') {
            $txt = $line -replace '^>\s*', ''
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
            $sel.ParagraphFormat.Alignment       = 0
            $sel.ParagraphFormat.LeftIndent      = $word.InchesToPoints(0.3)
            $sel.ParagraphFormat.RightIndent     = $word.InchesToPoints(0.3)
            $sel.ParagraphFormat.SpaceBefore     = 0
            $sel.ParagraphFormat.SpaceAfter      = 6
            $sel.ParagraphFormat.FirstLineIndent = 0
            $sel.Font.Italic = $true; $sel.Font.Color = $cCyan; $sel.Font.Size = 11
            $sel.Font.Bold = $false
            Render-Inline $sel $txt
            continue
        }

        # ---- Visual placeholder (> *Caption...) ----
        if ($line -match '^>\s*\*Caption') {
            $txt = ($line -replace '^>\s*\*', '') -replace '\*$', ''
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
            $sel.ParagraphFormat.Alignment = 1
            $sel.ParagraphFormat.LeftIndent = 0; $sel.ParagraphFormat.RightIndent = 0
            $sel.ParagraphFormat.SpaceBefore = 0; $sel.ParagraphFormat.SpaceAfter = 6
            $sel.ParagraphFormat.FirstLineIndent = 0
            $sel.Font.Italic = $true; $sel.Font.Size = 9; $sel.Font.Color = $cGray; $sel.Font.Bold = $false
            Render-Inline $sel $txt
            continue
        }

        # ---- Visual placeholder (> *Caption) or blockquote image placeholder ----
        if ($line -match '^>\s*\*\*Visual') {
            $txt = $line -replace '^>\s*', ''
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
            $sel.ParagraphFormat.Alignment = 1
            $sel.ParagraphFormat.LeftIndent = 0; $sel.ParagraphFormat.RightIndent = 0
            $sel.ParagraphFormat.SpaceBefore = 4; $sel.ParagraphFormat.SpaceAfter = 4
            $sel.ParagraphFormat.FirstLineIndent = 0
            $sel.Font.Italic = $true; $sel.Font.Size = 10; $sel.Font.Color = $cGray; $sel.Font.Bold = $false
            Render-Inline $sel $txt
            continue
        }

        # ---- Generic blockquote (> text not caught above) ----
        if ($line -match '^>\s*(.+)$') {
            $txt = $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
            $sel.ParagraphFormat.Alignment       = 0
            $sel.ParagraphFormat.LeftIndent      = $word.InchesToPoints(0.3)
            $sel.ParagraphFormat.RightIndent     = $word.InchesToPoints(0.3)
            $sel.ParagraphFormat.SpaceBefore     = 0
            $sel.ParagraphFormat.SpaceAfter      = 6
            $sel.ParagraphFormat.FirstLineIndent = 0
            $sel.Font.Italic = $true; $sel.Font.Color = $cGray; $sel.Font.Size = 10; $sel.Font.Bold = $false
            Render-Inline $sel $txt
            continue
        }

        # ---- Bullet list ----
        if ($line -match '^- (.+)$') {
            $txt = $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("List Bullet") } catch {}
            $sel.Font.Color = $cBlack; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Size = 11
            Render-Inline $sel $txt
            $inBulletList = $true; $inNumberList = $false
            continue
        }

        # ---- Checklist item (- [ ] text) ----
        if ($line -match '^- \[.\] (.+)$') {
            $txt = "[ ] " + $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("List Bullet") } catch {}
            $sel.Font.Color = $cBlack; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Size = 11
            Render-Inline $sel $txt
            continue
        }

        # ---- Numbered list ----
        if ($line -match '^\d+\. (.+)$') {
            $txt = $Matches[1]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("List Number") } catch {}
            if (-not $inNumberList) {
                try { $sel.Range.ListFormat.ApplyNumberDefault() } catch {}
                $inNumberList = $true; $listNumCounter = 1
            } else { $listNumCounter++ }
            $sel.Font.Color = $cBlack; $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Size = 11
            Render-Inline $sel $txt
            $inBulletList = $false
            continue
        }

        # ---- Metadata line (bold label: value) ----
        if ($line -match '^\*\*(.+?):\*\*\s*(.*)$' -and $line -notmatch '^#') {
            $label = $Matches[1]; $value = $Matches[2]
            $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
            try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
            $sel.ParagraphFormat.Alignment = 0; $sel.ParagraphFormat.LeftIndent = 0; $sel.ParagraphFormat.RightIndent = 0
            $sel.ParagraphFormat.SpaceBefore = 0; $sel.ParagraphFormat.SpaceAfter = 4; $sel.ParagraphFormat.FirstLineIndent = 0
            $sel.Font.Size = 10; $sel.Font.Color = $cGray; $sel.Font.Italic = $false
            $sel.Font.Bold = $true; $sel.TypeText($label + ": ")
            $sel.Font.Bold = $false; $sel.TypeText($value)
            $inBulletList = $false; $inNumberList = $false
            continue
        }

        # ---- Standard paragraph ----
        $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
        try { $sel.Style = $doc.Styles.Item("Normal") } catch {}
        $sel.ParagraphFormat.Alignment       = 0
        $sel.ParagraphFormat.LeftIndent      = 0
        $sel.ParagraphFormat.RightIndent     = 0
        $sel.ParagraphFormat.SpaceBefore     = 0
        $sel.ParagraphFormat.SpaceAfter      = 6
        $sel.ParagraphFormat.FirstLineIndent = 0
        $sel.Font.Bold = $false; $sel.Font.Italic = $false; $sel.Font.Size = 11; $sel.Font.Color = $cBlack
        Render-Inline $sel $line
        $inBulletList = $false; $inNumberList = $false
    }

    # Flush any pending table
    if ($inTable -and $tableHeaders.Count -gt 0) {
        Render-Table $doc $sel $word $tableHeaders $tableRows
    }

    # Footer
    try {
        $ftr = $doc.Sections.Item(1).Footers.Item(1)
        $ftr.Range.Select()
        $word.Selection.Collapse(1)
        $word.Selection.Font.Color = $cCyan; $word.Selection.Font.Size = 9; $word.Selection.Font.Bold = $false
        $word.Selection.ParagraphFormat.Alignment = 1
        $fLeaf = [System.IO.Path]::GetFileNameWithoutExtension($outPath) -replace '-', ' '
        $word.Selection.TypeText("AMP for Government / Jurisdiction Reviewers  |  $fLeaf")
    } catch {}

    # Save
    if (Test-Path $outPath) { Remove-Item $outPath -Force }
    $doc.SaveAs([ref]$outPath, [ref]16)
    $doc.Close($false)
    $kb = [math]::Round((Get-Item $outPath).Length / 1KB, 1)
    Write-Host "  Saved: $kb KB"
}

function Render-Inline($sel, $text) {
    # Handle **bold** inline markdown
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

function Render-Table($doc, $sel, $word, $headers, $rows) {
    if ($headers.Count -eq 0) { return }
    $sel.EndKey(6) | Out-Null; $sel.TypeParagraph()
    $nc = $headers.Count
    $nr = 1 + $rows.Count
    $tbl = $doc.Tables.Add($sel.Range, $nr, $nc)
    try { $tbl.Style = "Table Grid" } catch {}
    $tbl.AllowAutoFit = $true

    # Header row
    for ($c = 1; $c -le $nc; $c++) {
        $cell = $tbl.Cell(1, $c)
        $cell.Shading.BackgroundPatternColor = $cNavy
        $cell.Select()
        $word.Selection.Collapse(1)
        $word.Selection.Font.Color = $cWhite; $word.Selection.Font.Bold = $true; $word.Selection.Font.Size = 11
        $word.Selection.TypeText($headers[$c - 1])
    }

    # Data rows
    for ($r = 0; $r -lt $rows.Count; $r++) {
        $rowData = $rows[$r]
        $bg = if (($r % 2) -eq 0) { 15921906 } else { 16777215 }
        for ($c = 1; $c -le [math]::Min($nc, $rowData.Count); $c++) {
            $cell = $tbl.Cell($r + 2, $c)
            $cell.Shading.BackgroundPatternColor = $bg
            $cell.Select()
            $word.Selection.Collapse(1)
            $word.Selection.Font.Color = $cBlack; $word.Selection.Font.Bold = $false
            $word.Selection.Font.Italic = $false; $word.Selection.Font.Size = 11
            $parts = $rowData[$c - 1] -split '(\*\*[^*]+\*\*)'
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

    $sel.EndKey(6) | Out-Null
    $sel.TypeParagraph()
}

# ===== MAIN =====

Write-Host "Building Course 2 lesson files..."

$word = New-Object -ComObject Word.Application
$word.Visible = $true
$word.WindowState = 2

foreach ($lesson in $lessons) {
    if (-not (Test-Path $lesson.md)) {
        Write-Host "SKIPPED (md not found): $($lesson.md)"
        continue
    }
    Build-Lesson $lesson.md $lesson.out $word
}

$word.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
[GC]::Collect()

Write-Host "`nAll done."
Get-ChildItem "$lmsDir\Course2-Lesson-*.docx" | Select-Object Name, @{N="KB";E={[math]::Round($_.Length/1KB,1)}} | Sort-Object Name
