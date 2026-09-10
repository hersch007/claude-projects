# Deploy AnagraSign to one of the two SiteGround Node.js instances.
#
# GrowBig/GoGeek gives full SSH shell (unlike the Clinton cPanel host, which has
# none), so this uses scp + a single ssh session rather than an sftp batch file:
#   1. scp the app up (excluding node_modules, storage, .env, deploy/)
#   2. ssh: npm install --omit=dev
#   3. ssh: touch tmp/restart.txt   (tells Passenger to reload on the next request)
#
# Fill in $TARGETS below once, from Site Tools > Devs > SSH Keys Manager on each
# account (connection details are shown there after you add your key). See
# docs/deploy-siteground.md for the full walkthrough of setting the app up the
# first time — this script is for repeat deploys only.
#
# Usage (from the AnagraSign folder):
#   .\scripts\deploy-siteground.ps1 -Site grouprb
#   .\scripts\deploy-siteground.ps1 -Site partsofpractice

param(
    [Parameter(Mandatory = $true)]
    [ValidateSet("grouprb", "partsofpractice")]
    [string]$Site
)

# ---- TODO: fill in per-account details from SiteGround Site Tools > Devs > SSH Keys Manager ----
$TARGETS = @{
    grouprb = @{
        User = "TODO_ssh_username"        # e.g. u1234-abcde
        Host = "TODO.siteground.site"     # or your domain, whatever SSH Keys Manager shows
        Port = 18765                      # SiteGround SSH ports are non-standard, check the panel
        Dir  = "nodeapps/sign"            # the Application root you chose when creating the Node app
    }
    partsofpractice = @{
        User = "TODO_ssh_username"
        Host = "TODO.siteground.site"
        Port = 18765
        Dir  = "nodeapps/sign"
    }
}

$t = $TARGETS[$Site]
if ($t.User -like "TODO*" -or $t.Host -like "TODO*") {
    Write-Host "Fill in the SSH connection details for '$Site' at the top of this script first (see docs/deploy-siteground.md step 3)." -ForegroundColor Red
    exit 1
}

$KEY       = "$HOME\.ssh\id_ed25519"
$LOCAL_DIR = Split-Path -Parent $PSScriptRoot   # AnagraSign/
$REMOTE    = "$($t.User)@$($t.Host)"

Write-Host "Deploying AnagraSign to $Site ($REMOTE`:$($t.Dir))..." -ForegroundColor Cyan

# scp -r would nuke node_modules on the far side too, so upload only what changes on disk;
# stage a temp copy without the excluded folders and push that.
$staging = Join-Path $env:TEMP "anagrasign-deploy-$Site"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

Get-ChildItem $LOCAL_DIR -Force | Where-Object {
    $_.Name -notin @("node_modules", "storage", "deploy", ".git", ".env")
} | ForEach-Object {
    Copy-Item $_.FullName -Destination $staging -Recurse -Force
}

Write-Host "Uploading..." -ForegroundColor Cyan
& scp -r -i $KEY -P $t.Port "$staging\*" "${REMOTE}:$($t.Dir)/"
if ($LASTEXITCODE -ne 0) { Write-Host "scp failed (exit $LASTEXITCODE)" -ForegroundColor Red; exit $LASTEXITCODE }
Remove-Item $staging -Recurse -Force

Write-Host "Installing dependencies and restarting..." -ForegroundColor Cyan
$remoteCmd = "cd $($t.Dir) && npm install --omit=dev && mkdir -p tmp && touch tmp/restart.txt"
& ssh -i $KEY -p $t.Port $REMOTE $remoteCmd
if ($LASTEXITCODE -ne 0) { Write-Host "Remote command failed (exit $LASTEXITCODE)" -ForegroundColor Red; exit $LASTEXITCODE }

Write-Host "`nDone. Check https://sign.$(if ($Site -eq 'grouprb') { 'grouprb.com' } else { 'partsofpractice.com' })" -ForegroundColor Green
