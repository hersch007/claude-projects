# apply-wts-style.ps1
# Applies WTS brand colors and logo to one or more AMP lesson .docx files.
# Usage: Pass file paths as arguments, or edit $files below and run directly.
#
# What this script does (per file):
#   [1] XML: Replace old teal H3 color (#0d6b8c) -> WTS cyan (#00AEEF)
#   [2] XML: Replace italic+navy callout boxes (#1a4f8a) -> WTS cyan (#00AEEF)
#   [3] COM: Insert WTS logo at top (centered, 1.8 in wide), skips if already present
#   [4] COM: Update footer font color -> WTS cyan
#   [5] COM: Update header font color -> WTS cyan
#
# Color guide:
#   H1/H2/table headers : navy #1a4f8a  (DO NOT change)
#   H3 subheadings      : was teal #0d6b8c -> now WTS cyan #00AEEF
#   Callout boxes       : italic normal para, was navy -> now WTS cyan #00AEEF

param([string[]]$Files)

$base     = "C:\Users\richa\Documents\Claude Projects\Wireless-Training-LMS\Wireless-LMS-Project"
$logoPath = "$base\assets\WTS-Oval-Logo-Blue.png"

# Default file list (used when running directly without arguments)
$defaultFiles = @(
    "$base\lms\Lesson-01-What-Is-AMP.docx",
    "$base\lms\Lesson-02-User-Roles-Responsibilities.docx",
    "$base\lms\Lesson-03-Projects-Dashboard-Status.docx",
    "$base\lms\Lesson-04-Project-Information-Form-PIF.docx",
    "$base\lms\Lesson-05-Component-Types-NWF-TWF-NCM.docx",
    "$base\lms\Lesson-06-Security-Compliance-Official-Record.docx"
)
if ($Files -and $Files.Count -gt 0) {
    $targetFiles = $Files
} else {
    $targetFiles = $defaultFiles | Where-Object { Test-Path $_ }
}

Add-Type -AssemblyName System.IO.Compression.FileSystem

# ---- STEPS 1+2 via direct XML replacement ----
# Faster and more reliable than COM paragraph iteration.
# Colors to replace (uppercase hex as used in Word XML):
#   Old teal (H3):        0D6B8C -> 00AEEF
#   Italic navy (callout): <w:i/><w:color w:val="1A4F8A"/> -> 00AEEF
# H2/title/table navy without italic is NOT touched.

function Apply-XmlColors($docPath) {
    $tmpDir = "$env:TEMP\wts_style_tmp"
    Remove-Item $tmpDir -Recurse -Force -ErrorAction SilentlyContinue

    [System.IO.Compression.ZipFile]::ExtractToDirectory($docPath, $tmpDir)

    # ---- document.xml ----
    $xmlFile = "$tmpDir\word\document.xml"
    $xml = [System.IO.File]::ReadAllText($xmlFile, [System.Text.Encoding]::UTF8)

    # Step 1: H3 teal (#0d6b8c) -> WTS cyan in direct text runs
    $tealBefore = ($xml -split '(?i)0d6b8c').Count - 1
    $xml = $xml.Replace('<w:color w:val="0D6B8C"/>', '<w:color w:val="00AEEF"/>')
    $xml = $xml.Replace('<w:color w:val="0d6b8c"/>', '<w:color w:val="00AEEF"/>')
    $tealAfter  = ($xml -split '(?i)0d6b8c').Count - 1

    # Step 2: Italic + navy -> WTS cyan  (Lessons 5/6 callout box style)
    $oldIN = '<w:i/><w:color w:val="1A4F8A"/>'
    $newIC = '<w:i/><w:color w:val="00AEEF"/>'
    $boxBefore = ($xml -split [regex]::Escape($oldIN)).Count - 1
    $xml = $xml.Replace($oldIN, $newIC)
    $boxAfter  = ($xml -split [regex]::Escape($oldIN)).Count - 1

    # Step 3: Orange callout boxes (#CC4400) -> WTS cyan  (Lessons 3/4 style)
    $orangeBefore = ($xml -split 'CC4400').Count - 1
    $xml = $xml.Replace('<w:color w:val="CC4400"/>', '<w:color w:val="00AEEF"/>')
    $orangeAfter  = ($xml -split 'CC4400').Count - 1

    # Step 4: Green correct-answer text (#006600) -> WTS cyan  (Lessons 3/4 style)
    $greenBefore = ($xml -split '006600').Count - 1
    $xml = $xml.Replace('<w:color w:val="006600"/>', '<w:color w:val="00AEEF"/>')
    $greenAfter  = ($xml -split '006600').Count - 1

    [System.IO.File]::WriteAllText($xmlFile, $xml, [System.Text.Encoding]::UTF8)

    # ---- styles.xml -- update Heading3 color if it still uses old teal ----
    $styFile = "$tmpDir\word\styles.xml"
    $styleTeal = 0
    if (Test-Path $styFile) {
        $sxml = [System.IO.File]::ReadAllText($styFile, [System.Text.Encoding]::UTF8)
        $styleTeal = ($sxml -split '(?i)0d6b8c').Count - 1
        $sxml = $sxml.Replace('<w:color w:val="0D6B8C"/>', '<w:color w:val="00AEEF"/>')
        $sxml = $sxml.Replace('<w:color w:val="0d6b8c"/>', '<w:color w:val="00AEEF"/>')
        [System.IO.File]::WriteAllText($styFile, $sxml, [System.Text.Encoding]::UTF8)
    }

    # Repack as .docx
    Remove-Item $docPath -Force
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tmpDir, $docPath)
    Remove-Item $tmpDir -Recurse -Force

    return [PSCustomObject]@{
        TealDirect = ($tealBefore  - $tealAfter)
        TealStyle  = $styleTeal
        BoxItalic  = ($boxBefore   - $boxAfter)
        Orange     = ($orangeBefore - $orangeAfter)
        Green      = ($greenBefore  - $greenAfter)
    }
}

# ---- STEPS 3-5 via COM (logo + header + footer) ----
function Apply-LogoAndHeaderFooter($docPath, $word, $logoPath) {
    $doc = $word.Documents.Open($docPath)
    if ($null -eq $doc) { return "ERROR: could not open" }

    $newCyan = 15707648   # BGR of #00AEEF

    # STEP 3: Insert logo at top if not already there
    $logoMsg = "Logo already present - skipped"
    try {
        $p1 = $doc.Paragraphs.Item(1)
        $hasLogo = $false
        try { $hasLogo = $p1.Range.InlineShapes.Count -gt 0 } catch {}
        if (-not $hasLogo) {
            $rng = $doc.Range(0, 0)
            $rng.InsertBefore([char]13)
            $p1 = $doc.Paragraphs.Item(1)
            try { $p1.Style = $doc.Styles.Item("Normal") } catch {}
            $p1.Format.Alignment   = 1
            $p1.Format.SpaceAfter  = 6
            $p1.Format.SpaceBefore = 10
            $p1.Format.LeftIndent  = 0
            $p1.Format.RightIndent = 0
            $pr = $p1.Range
            $pr.Collapse(1)
            $pr.Font.Size = 11; $pr.Font.Bold = $false; $pr.Font.Italic = $false; $pr.Font.Color = 0
            $shape = $pr.InlineShapes.AddPicture($logoPath, $false, $true)
            $ratio = $shape.Height / $shape.Width
            $newW  = $word.InchesToPoints(1.8)
            $shape.Width = $newW; $shape.Height = $newW * $ratio
            $wIn = [math]::Round($shape.Width / 72, 2)
            $hIn = [math]::Round($shape.Height / 72, 2)
            $logoMsg = "Logo inserted ($wIn x $hIn in)"
        }
    } catch { $logoMsg = "Logo FAILED: $_" }

    # STEP 4: Footer -> WTS cyan
    $ftrMsg = "Footer updated"
    try {
        $ftr = $doc.Sections.Item(1).Footers.Item(1)
        $ftr.Range.Font.Color = $newCyan
    } catch { $ftrMsg = "Footer skipped: $_" }

    # STEP 5: Header -> WTS cyan
    $hdrMsg = "Header updated"
    try {
        $hdr = $doc.Sections.Item(1).Headers.Item(1)
        $hdr.Range.Font.Color = $newCyan
    } catch { $hdrMsg = "Header skipped: $_" }

    $doc.SaveAs([ref]$docPath, [ref]16)
    $doc.Close($false)

    return @{ Logo = $logoMsg; Footer = $ftrMsg; Header = $hdrMsg }
}

# ---- Main loop ----
Write-Host "WTS Brand Style Script"
Write-Host "Logo: $logoPath"
Write-Host "Files to process: $($targetFiles.Count)"

$word = New-Object -ComObject Word.Application
$word.Visible = $false

foreach ($filePath in $targetFiles) {
    $leaf = Split-Path $filePath -Leaf
    Write-Host ""
    Write-Host "== $leaf =="

    if (-not (Test-Path $filePath)) {
        Write-Host "  SKIPPED: file not found"
        continue
    }

    # XML color replacements (fast, no COM)
    try {
        $result = Apply-XmlColors $filePath
        Write-Host "  [1] H3 teal->cyan: $($result.TealDirect) direct + $($result.TealStyle) in styles.xml"
        Write-Host "  [2] Callout italic+navy: $($result.BoxItalic) replaced"
        Write-Host "  [2] Callout orange: $($result.Orange) replaced"
        Write-Host "  [2] Correct answer green: $($result.Green) replaced"
    } catch {
        Write-Host "  [1+2] XML color patch FAILED: $_"
    }

    # COM: logo + header + footer
    try {
        $comResult = Apply-LogoAndHeaderFooter $filePath $word $logoPath
        Write-Host "  [3] $($comResult.Logo)"
        Write-Host "  [4] $($comResult.Footer)"
        Write-Host "  [5] $($comResult.Header)"
    } catch {
        Write-Host "  [3-5] COM step FAILED: $_"
    }

    $kb = [math]::Round((Get-Item $filePath).Length / 1KB, 1)
    Write-Host "  Final size: $kb KB"
}

$word.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
[GC]::Collect()
Write-Host ""
Write-Host "All done."
