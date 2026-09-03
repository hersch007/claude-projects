# Deploy a plugin zip to all sites that have it installed.
# Usage: .\deploy.ps1 -Zip start-performance-2.5.32.zip
#        .\deploy.ps1 -Plugin start-performance          (finds latest zip automatically)
#        .\deploy.ps1                                    (builds all zips, then deploys all plugins)

param(
    [string]$Zip,
    [string]$Plugin
)

$SSH_HOST = "start6zs@startwebservicesbackup.com"
$SSH_PORT = "22"
$WP_ROOT  = "/home/start6zs/public_html"
$SITES    = @("smti", "sp", "fruth", "wts-app")
$SCRIPT   = $PSScriptRoot

function Get-ZipPath {
    param([string]$slug)
    $found = Get-ChildItem -Path $SCRIPT -Filter "$slug-*.zip" |
             Sort-Object LastWriteTime -Descending |
             Select-Object -First 1
    if ($found) { return $found.FullName }
    return $null
}

function Deploy-Zip {
    param([string]$zipPath)

    $zipName = Split-Path $zipPath -Leaf
    $slug    = $zipName -replace '-[\d.]+\.zip$', ''

    Write-Host "`nDeploying $zipName..." -ForegroundColor Cyan

    # Upload to server
    & scp -P $SSH_PORT $zipPath "${SSH_HOST}:/tmp/" 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  SCP failed" -ForegroundColor Red
        return
    }

    # Build remote shell script
    $remoteScript = ""
    foreach ($site in $SITES) {
        $pluginDir = "$WP_ROOT/$site/wp-content/plugins/$slug"
        $phpFile   = "$pluginDir/$slug.php"
        $remoteScript += "if [ -d '$pluginDir' ]; then`n"
        $remoteScript += "  unzip -o /tmp/$zipName -d $WP_ROOT/$site/wp-content/plugins/ > /dev/null 2>&1`n"
        $remoteScript += "  ver=`$(grep 'Version:' $phpFile 2>/dev/null | head -1 | awk '{print `$NF}')`n"
        $remoteScript += "  echo '  ${site} OK ' `$ver`n"
        $remoteScript += "else`n"
        $remoteScript += "  echo '  ${site} skipped (not installed)'`n"
        $remoteScript += "fi`n"
    }

    $results = & ssh -p $SSH_PORT $SSH_HOST $remoteScript 2>&1
    foreach ($line in $results) {
        $s = $line.ToString()
        if ($s -match "skipped") {
            Write-Host $s -ForegroundColor DarkGray
        } else {
            Write-Host $s -ForegroundColor Green
        }
    }
}

# ── Main ─────────────────────────────────────────────────────────────────────

if ($Zip) {
    if ([System.IO.Path]::IsPathRooted($Zip)) {
        $zipPath = $Zip
    } else {
        $zipPath = Join-Path $SCRIPT $Zip
    }
    if (-not (Test-Path $zipPath)) { Write-Host "File not found: $zipPath" -ForegroundColor Red; exit 1 }
    Deploy-Zip $zipPath

} elseif ($Plugin) {
    $zipPath = Get-ZipPath $Plugin
    if (-not $zipPath) { Write-Host "No zip found for plugin: $Plugin" -ForegroundColor Red; exit 1 }
    Deploy-Zip $zipPath

} else {
    Write-Host "Building all plugins..." -ForegroundColor Cyan
    & "$SCRIPT\build.ps1"

    Write-Host "`nDeploying all plugins..." -ForegroundColor Cyan
    Get-ChildItem -Path $SCRIPT -Directory | ForEach-Object {
        $slug    = $_.Name
        $mainPHP = Join-Path $_.FullName "$slug.php"
        if (-not (Test-Path $mainPHP)) { return }
        $zipPath = Get-ZipPath $slug
        if ($zipPath) { Deploy-Zip $zipPath }
    }
}

Write-Host "`nDone." -ForegroundColor Cyan
