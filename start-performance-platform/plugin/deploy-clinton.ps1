# Deploy plugins to the City of Clinton instance (separate cPanel host).
#
# Clinton has NO shell access (cPanel "noshell"), so the ssh+unzip flow in
# ..\deploy.ps1 cannot be used. This uploads the extracted plugin FOLDERS over
# SFTP instead (sftp put -r), which overwrites files in place.
#
# Usage (from the /plugin folder):
#   .\deploy-clinton.ps1                       deploy core + ai + government-service-core
#   .\deploy-clinton.ps1 -Plugin start-performance-ai
#   .\deploy-clinton.ps1 -Plugin government-service-core,start-performance
#
# Auth: SSH key C:\Users\richa\.ssh\id_ed25519, authorized in cPanel → SSH Access
# for the "clinton" account. New plugins still need activating once in WP Admin.

param(
    [string[]]$Plugin = @("start-performance", "start-performance-ai", "government-service-core")
)

$SSH_USER   = "clinton"
$SSH_HOST   = "64.247.178.244"
$SSH_PORT   = 22
$KEY        = "C:\Users\richa\.ssh\id_ed25519"
$REMOTE_DIR = "public_html/wp-content/plugins"
$LOCAL_DIR  = $PSScriptRoot

$batch = @()
foreach ($p in $Plugin) {
    $src = Join-Path $LOCAL_DIR $p
    if (-not (Test-Path (Join-Path $src "$p.php"))) {
        Write-Host "Skipping $p (no $p\$p.php in $LOCAL_DIR)" -ForegroundColor Yellow
        continue
    }
    $ver = (Get-Content (Join-Path $src "$p.php") | Select-String "Version:\s+(.+)").Matches[0].Groups[1].Value.Trim()
    Write-Host "Queued $p $ver" -ForegroundColor Cyan
    $batch += "put -r `"$src`" $REMOTE_DIR"
}
if (-not $batch) { Write-Host "Nothing to deploy." -ForegroundColor Red; exit 1 }
$batch += "ls -l $REMOTE_DIR"

$batchFile = Join-Path $env:TEMP "sp-clinton-sftp.txt"
$batch -join "`n" | Set-Content -Path $batchFile -Encoding ascii

Write-Host "`nUploading to ${SSH_USER}@${SSH_HOST}:$REMOTE_DIR ..." -ForegroundColor Cyan
& sftp -o BatchMode=yes -i $KEY -P $SSH_PORT -b $batchFile "${SSH_USER}@${SSH_HOST}" |
    Where-Object { $_ -notmatch "^Uploading|^Entering|post-quantum|decrypt later|openssh.com/pq" }
if ($LASTEXITCODE -ne 0) { Write-Host "SFTP failed (exit $LASTEXITCODE)" -ForegroundColor Red; exit $LASTEXITCODE }

Remove-Item $batchFile -ErrorAction SilentlyContinue
Write-Host "`nDone. If a plugin is new on this site, activate it in WP Admin -> Plugins." -ForegroundColor Green
