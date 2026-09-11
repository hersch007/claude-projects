# Package AnagraSign for a SiteGround Node.js Project deploy.
#
# SiteGround's Node.js hosting takes an archive upload through its Site Tools wizard, not SSH/git
# — there is no way to automate the actual upload from here. This script does the one part that
# IS automatable: build a clean tar.gz (no node_modules, no real storage/ contents, no .env, no
# deploy/) and drop it in Downloads, ready for "Browse Files" in the wizard.
#
# See docs/deploy-siteground.md for the full deploy walkthrough, including why STORAGE_DIR is
# mandatory on this host and the /sign.html link-format gotcha.
#
# Usage (from the AnagraSign folder):
#   .\scripts\deploy-siteground.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot   # AnagraSign/
$staging = Join-Path $env:TEMP "anagrasign-pkg"
$archive = Join-Path $env:TEMP "anagrasign-deploy.tar.gz"
$downloadsCopy = Join-Path "$HOME\Downloads" "anagrasign-deploy.tar.gz"

if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path "$staging\storage\uploads", "$staging\storage\completed", "$staging\storage\outbox" -Force | Out-Null
foreach ($d in @("uploads", "completed", "outbox")) {
    New-Item -ItemType File -Path "$staging\storage\$d\.gitkeep" -Force | Out-Null
}

$include = @("public", "server", "scripts", "docs", ".gitignore", "package.json", "package-lock.json", "README.md", "CLAUDE.md", ".env.example")
foreach ($item in $include) {
    Copy-Item (Join-Path $root $item) -Destination $staging -Recurse -Force
}
# Test artifacts that shouldn't ship
Remove-Item (Join-Path $staging "docs\smoke-completed.pdf") -ErrorAction SilentlyContinue

if (Test-Path $archive) { Remove-Item $archive -Force }
Push-Location $staging
try {
    & tar -czf $archive .
    if ($LASTEXITCODE -ne 0) { throw "tar failed (exit $LASTEXITCODE)" }
} finally { Pop-Location }

Copy-Item $archive $downloadsCopy -Force
Remove-Item $staging -Recurse -Force

$sizeKB = [math]::Round((Get-Item $downloadsCopy).Length / 1KB)
Write-Host "`nPackaged: $downloadsCopy ($sizeKB KB)" -ForegroundColor Green
Write-Host "Now in Site Tools > Node.js > Deployment Options: Save and Deploy -> Browse Files -> pick this file -> Continue." -ForegroundColor Cyan
