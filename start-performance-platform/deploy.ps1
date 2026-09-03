# Start Performance -- Multi-Site Deployment Script
# Usage:
#   .\deploy.ps1                      deploy all plugins to all sites
#   .\deploy.ps1 -Site smti           deploy all plugins to one site
#   .\deploy.ps1 -Plugin core         deploy one plugin to all its sites
#   .\deploy.ps1 -Plugin core -Site sp

param(
    [string]$Site   = "",
    [string]$Plugin = ""
)

# ── CONFIG ────────────────────────────────────────────────────────────────────

$SSH_USER = "start6zs"
$SSH_HOST = "startwebservicesbackup.com"
$SSH_PORT = 22

$LOCAL_PLUGIN_DIR  = "C:\Users\richa\Documents\Claude Projects\start-performance-platform\plugin"
$LOCAL_CURRENT_DIR = "C:\Users\richa\Documents\Claude Projects\start-performance-platform\current-plugins"
$LOCAL_CUSTOM_DIR  = "C:\Users\richa\Documents\Claude Projects\Custom Builds\Fruth\plugins\core"

# ── SITES ─────────────────────────────────────────────────────────────────────

$SITES = [ordered]@{
    smti  = "/home1/start6zs/public_html/smti"
    sp    = "/home1/start6zs/public_html/sp"
    fruth = "/home1/start6zs/public_html/fruth"
    wts   = "/home1/start6zs/public_html/wts-app"
    fiber = "/home1/start6zs/public_html/fiber"
}

# ── PLUGIN MAP ────────────────────────────────────────────────────────────────

$PLUGINS = @(
    [pscustomobject]@{ key="core";         prefix="start-performance-2";            sites=@("smti","sp","fruth","wts","fiber"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="ai";           prefix="start-performance-ai-";          sites=@("smti","sp","fruth","fiber"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="intelligence"; prefix="start-performance-intelligence-"; sites=@("smti","sp","fruth","fiber"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="tickets";      prefix="start-performance-tickets-";      sites=@("smti","sp","fruth"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="sales";        prefix="start-performance-sales-";        sites=@("smti","sp","fruth"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="operations";   prefix="start-performance-operations-";   sites=@("smti","sp","fruth"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="knowledge";    prefix="start-performance-knowledge-";    sites=@("smti","sp","fruth","wts"); dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="chat";         prefix="start-performance-chat-";         sites=@("sp");                dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="wts-lms";      prefix="wts-lms-";                        sites=@("wts");               dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="smti-sales";   prefix="start-performance-smti-sales-";   sites=@("smti");              dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="smti-service"; prefix="start-performance-smti-service-"; sites=@("smti");              dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="smti-tickets"; prefix="smti-service-tickets-";           sites=@("smti");              dir=$LOCAL_CURRENT_DIR },
    [pscustomobject]@{ key="fruth-quotes"; prefix="sales-quote-system-";             sites=@("fruth");             dir=$LOCAL_CUSTOM_DIR  },
    [pscustomobject]@{ key="fiberco-sp";   prefix="fiberco-sp-";                     sites=@("fiber");             dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="fiberco-quote";prefix="fiberco-quote-";                  sites=@("fiber");             dir=$LOCAL_PLUGIN_DIR  },
    [pscustomobject]@{ key="fiberco-chat"; prefix="fiberco-ai-chatbot-";             sites=@("fiber");             dir=$LOCAL_PLUGIN_DIR  }
)

# ── HELPERS ───────────────────────────────────────────────────────────────────

function Get-LatestZip($dir, $prefix) {
    # Pick the highest zip by REAL version, not string sort — otherwise "1.2.10"
    # sorts before "1.2.9" ('1' < '9') and ships a stale build.
    $zips = Get-ChildItem -Path $dir -Filter ($prefix + "*.zip") -File -ErrorAction SilentlyContinue
    if (-not $zips) { return $null }
    $parsed = foreach ($z in $zips) {
        $v = "0.0.0"
        if     ($z.Name -match '(\d+\.\d+\.\d+)') { $v = $Matches[1] }
        elseif ($z.Name -match '(\d+\.\d+)')      { $v = $Matches[1] }
        [pscustomobject]@{ File = $z; Ver = [version]$v }
    }
    return ($parsed | Sort-Object Ver -Descending | Select-Object -First 1).File
}

function Write-Ok($msg)   { Write-Host ("  [OK] " + $msg) -ForegroundColor Green }
function Write-Err($msg)  { Write-Host ("  [!!] " + $msg) -ForegroundColor Red   }
function Write-Head($msg) { Write-Host ("`n=== " + $msg + " ===") -ForegroundColor Cyan }

# ── FILTER ────────────────────────────────────────────────────────────────────

$deployPlugins = $PLUGINS
if ($Plugin) {
    $deployPlugins = @($PLUGINS | Where-Object { $_.key -eq $Plugin })
    if (-not $deployPlugins) { Write-Err ("Unknown plugin: " + $Plugin); exit 1 }
}

$deploySites = @($SITES.Keys)
if ($Site) {
    if (-not $SITES.Contains($Site)) { Write-Err ("Unknown site: " + $Site); exit 1 }
    $deploySites = @($Site)
}

Write-Host "`nStart Performance Deployment" -ForegroundColor White
Write-Host ("Host  : " + $SSH_USER + "@" + $SSH_HOST)
Write-Host ("Sites : " + ($deploySites -join ", "))

# ── PHASE 1: Upload all zips ──────────────────────────────────────────────────

Write-Head "PHASE 1 - Uploading zips"

$queue = @()

foreach ($p in $deployPlugins) {
    $zip = Get-LatestZip -dir $p.dir -prefix $p.prefix
    if (-not $zip) {
        Write-Err ("No zip found for: " + $p.key + " (prefix=" + $p.prefix + ")")
        continue
    }
    $localPath = $zip.FullName.Replace("\", "/")
    $remoteTmp = "/tmp/" + $zip.Name
    Write-Host ("  " + $p.key + " -> " + $zip.Name)
    $out = & scp -P $SSH_PORT $localPath ($SSH_USER + "@" + $SSH_HOST + ":" + $remoteTmp) 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Err ("Upload failed for " + $zip.Name + ": " + $out)
    } else {
        Write-Ok ("Uploaded " + $zip.Name)
        $queue += [pscustomobject]@{ plugin=$p; remoteTmp=$remoteTmp }
    }
}

# ── PHASE 2: Install via single SSH session ───────────────────────────────────

Write-Head "PHASE 2 - Installing"

$script = ""
foreach ($entry in $queue) {
    $p = $entry.plugin
    foreach ($siteKey in $p.sites) {
        if ($deploySites -notcontains $siteKey) { continue }
        $wpPath = $SITES[$siteKey]
        $script += "echo MARKER_" + $p.key + "_" + $siteKey + "; "
        $script += "wp plugin install " + $entry.remoteTmp + " --force --activate --path=" + $wpPath + " 2>/dev/null; "
    }
}
foreach ($entry in $queue) {
    $script += "rm -f " + $entry.remoteTmp + "; "
}

if ($script) {
    $results = & ssh -p $SSH_PORT ($SSH_USER + "@" + $SSH_HOST) $script 2>&1
    $current = ""
    foreach ($line in ($results -split "`n")) {
        $line = $line.Trim()
        if ($line -match "^MARKER_(.+)_([^_]+)$") {
            $current = $Matches[1] + " -> " + $Matches[2]
        } elseif ($line -match "Success:|installed|updated") {
            Write-Ok $current
        } elseif ($line -match "Error|Warning" -and $current -and $line -notmatch "deprecated") {
            Write-Err ($current + ": " + $line)
        }
    }
}

Write-Host "`nDeployment complete.`n" -ForegroundColor Green
