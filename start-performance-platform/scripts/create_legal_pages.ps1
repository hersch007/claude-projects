$ErrorActionPreference = "Stop"

$user   = "start6zs"
$host_  = "startwebservicesbackup.com"
$port   = 22
$script = "scripts/create_legal_pages.php"
$remote = "/home1/start6zs/create_legal_pages.php"

$sites = @{
    smti  = "/home1/start6zs/public_html/smti"
    sp    = "/home1/start6zs/public_html/sp"
    fruth = "/home1/start6zs/public_html/fruth"
}

Write-Host ""
Write-Host "=== Uploading legal pages script ===" -ForegroundColor Cyan
$local = (Resolve-Path $script).Path.Replace("\", "/")
& scp -P $port $local "${user}@${host_}:${remote}"
if ($LASTEXITCODE -ne 0) { Write-Host "SCP failed" -ForegroundColor Red; exit 1 }
Write-Host "[OK] Uploaded" -ForegroundColor Green

Write-Host ""
Write-Host "=== Creating pages on all sites ===" -ForegroundColor Cyan

$cmds = ""
foreach ($site in $sites.GetEnumerator()) {
    $name = $site.Key
    $path = $site.Value
    $cmds += "echo '--- $name ---'; wp eval-file $remote --path=$path 2>/dev/null; "
}
$cmds += "rm -f $remote; echo 'Script removed.'"

& ssh -p $port "${user}@${host_}" $cmds

Write-Host ""
Write-Host "=== Done ===" -ForegroundColor Green
Write-Host "Pages created/updated on smti, sp, and fruth." -ForegroundColor White
Write-Host "URLs will be: /terms/  /privacy/  /dpa/" -ForegroundColor White
