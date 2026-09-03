<#
  append-month.ps1 — Post a day's per-client hours into the monthly matrix of
  Start Billables.xlsx, creating the month tab if it doesn't exist yet.

  Columns are resolved by HEADER NAME at write-time, using the "NON BILLABLE"
  column as the divider: client columns to its left are billable, to its right
  are non-billable. This means columns can be inserted/moved without breaking
  anything, and each tab may have a different layout.

  Params:
    -Workbook    path to Start Billables.xlsx
    -AppendJson  path to daily/append-YYYY-MM-DD.json
                 ({ date, billable:{name:hrs}, nonBillable:{name:hrs} })
    -NoBackup    skip the timestamped backup
    -KeepBackups how many backups to retain (default 5)
#>
param(
  [Parameter(Mandatory=$true)][string]$Workbook,
  [Parameter(Mandatory=$true)][string]$AppendJson,
  [switch]$NoBackup,
  [int]$KeepBackups = 5
)
$ErrorActionPreference = 'Stop'
function Col([int]$i){ $s=''; while($i -gt 0){ $m=($i-1)%26; $s=[char](65+$m)+$s; $i=[int](($i-$m)/26) }; return $s }

$payload = Get-Content -Raw -LiteralPath $AppendJson | ConvertFrom-Json
$date = [datetime]::ParseExact($payload.date, 'yyyy-MM-dd', $null)
$year = $date.Year; $month = $date.Month
$daysInMonth = [datetime]::DaysInMonth($year, $month)
$FIRST_ROW = 4

if (-not $NoBackup) {
  $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
  $wbDir = [IO.Path]::GetDirectoryName($Workbook)
  $bak = [IO.Path]::Combine($wbDir, "Start Billables.backup-$stamp.xlsx")
  Copy-Item -LiteralPath $Workbook -Destination $bak -Force
  Write-Output "Backup: $bak"
  $old = Get-ChildItem -LiteralPath $wbDir -Filter 'Start Billables.backup-*.xlsx' | Sort-Object LastWriteTime -Descending | Select-Object -Skip $KeepBackups
  if ($old) { $old | Remove-Item -Force; Write-Output ("Pruned {0} old backup(s), keeping {1} most recent." -f $old.Count, $KeepBackups) }
}

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false; $xl.DisplayAlerts = $false
try {
  $wb = $xl.Workbooks.Open($Workbook)

  function Get-Divider($ws) {
    for ($c = 2; $c -le 80; $c++) { if ([string]$ws.Cells.Item(1, $c).Value2 -eq 'NON BILLABLE') { return $c } }
    throw "No 'NON BILLABLE' divider column on sheet '$($ws.Name)'."
  }
  function Get-LastCol($ws, $div) {
    $c = $div + 1
    while ([string]$ws.Cells.Item(1, $c + 1).Value2 -ne '') { $c++ }
    return $c
  }
  function Get-LastDataRow($ws) {
    $r = $FIRST_ROW
    while ($ws.Cells.Item($r + 1, 1).Value2 -is [double] -and $ws.Cells.Item($r + 1, 1).Value2 -gt 0) { $r++ }
    return $r
  }

  # --- locate the sheet for this month ------------------------------------
  $target = $null
  foreach ($ws in $wb.Worksheets) {
    for ($r = $FIRST_ROW; $r -lt $FIRST_ROW + 40; $r++) {
      $v = $ws.Cells.Item($r, 1).Value2
      if ($v -is [double] -and $v -gt 0) {
        $d = [datetime]::FromOADate($v)
        if ($d.Year -eq $year -and $d.Month -eq $month) { $target = $ws; break }
      }
    }
    if ($target) { break }
  }

  # --- create the month tab by cloning the newest sheet -------------------
  if (-not $target) {
    $src = $null; $srcMax = -1
    foreach ($ws in $wb.Worksheets) {
      $v = $ws.Cells.Item($FIRST_ROW, 1).Value2
      if ($v -is [double] -and $v -gt $srcMax) { $srcMax = $v; $src = $ws }
    }
    if (-not $src) { throw "No existing month sheet to clone from." }
    $tmplLast = Get-LastDataRow $src
    $src.Copy($wb.Worksheets.Item(1))
    $target = $wb.Worksheets.Item(1)
    $target.Name = $date.ToString('MMMM')
    $lastRow = $FIRST_ROW + $daysInMonth - 1

    if ($lastRow -gt $tmplLast) {
      $target.Rows.Item("$tmplLast").Copy() | Out-Null
      $target.Range("A$($tmplLast+1):A$lastRow").EntireRow.Insert() | Out-Null
      try { $xl.CutCopyMode = $false } catch { }
    }
    for ($i = 0; $i -lt $daysInMonth; $i++) {
      $target.Cells.Item($FIRST_ROW + $i, 1).Value2 = ([datetime]::new($year, $month, ($daysInMonth - $i))).ToOADate()
    }
    for ($r = $lastRow + 1; $r -le $tmplLast; $r++) { $target.Rows.Item("$r").ClearContents() }

    $div = Get-Divider $target; $last = Get-LastCol $target $div
    $clientCols = (6..($div - 1)) + (($div + 1)..$last)
    foreach ($c in (@(3) + $clientCols)) { $L = Col $c; $target.Cells.Item(2, $c).Formula = "=SUM($L$FIRST_ROW`:$L$lastRow)" }
    foreach ($c in $clientCols) { $L = Col $c; $target.Range("$L$FIRST_ROW`:$L$lastRow").ClearContents() | Out-Null }
    Write-Output ("Created new tab '{0}' ({1} days)" -f $target.Name, $daysInMonth)
  }

  # --- resolve billable / non-billable columns by header ------------------
  $div = Get-Divider $target; $last = Get-LastCol $target $div
  $billMap = @{}; for ($c = 6; $c -lt $div; $c++) { $h = [string]$target.Cells.Item(1, $c).Value2; if ($h -ne '') { $billMap[$h] = $c } }
  $nbMap = @{};   for ($c = $div + 1; $c -le $last; $c++) { $h = [string]$target.Cells.Item(1, $c).Value2; if ($h -ne '') { $nbMap[$h] = $c } }

  # --- find the date row --------------------------------------------------
  $targetOA = [int]$date.ToOADate()
  $row = $null
  for ($r = $FIRST_ROW; $r -lt $FIRST_ROW + 40; $r++) {
    $v = $target.Cells.Item($r, 1).Value2
    if ($v -is [double] -and [int][math]::Floor($v) -eq $targetOA) { $row = $r; break }
  }
  if (-not $row) { throw "Could not find row for $($payload.date) on sheet '$($target.Name)'." }

  # clear the day's client cells first so a re-post is authoritative
  foreach ($c in (@($billMap.Values) + @($nbMap.Values))) { $target.Cells.Item($row, $c).ClearContents() | Out-Null }

  # --- write the values ---------------------------------------------------
  $written = @()
  if ($payload.billable) {
    foreach ($p in $payload.billable.PSObject.Properties) {
      if (-not $billMap.ContainsKey($p.Name)) { throw "Billable client '$($p.Name)' has no column on sheet '$($target.Name)'. Add the column first (tools/add-billable-column.ps1)." }
      $target.Cells.Item($row, $billMap[$p.Name]).Value2 = [double]$p.Value; $written += "$($p.Name)=$($p.Value)"
    }
  }
  if ($payload.nonBillable) {
    foreach ($p in $payload.nonBillable.PSObject.Properties) {
      if (-not $nbMap.ContainsKey($p.Name)) { throw "Non-billable client '$($p.Name)' has no column on sheet '$($target.Name)'." }
      $target.Cells.Item($row, $nbMap[$p.Name]).Value2 = [double]$p.Value; $written += "$($p.Name)(nb)=$($p.Value)"
    }
  }

  $wb.Save()
  Write-Output ("Sheet '{0}' row {1} ({2}): {3}" -f $target.Name, $row, $date.ToString('M/d/yyyy'), ($written -join ' '))
  Write-Output ("Row totals -> Billable(E)={0}  NonBill(O)={1}  Total(B)={2}" -f $target.Cells.Item($row,5).Value2, $target.Cells.Item($row,$div).Value2, $target.Cells.Item($row,2).Value2)
  $wb.Close($true)
  Write-Output "SAVED_OK"
}
finally {
  $xl.Quit()
  [void][Runtime.InteropServices.Marshal]::ReleaseComObject($xl)
}
