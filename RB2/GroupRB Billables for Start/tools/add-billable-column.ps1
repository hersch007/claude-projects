<#
  add-billable-column.ps1 — Insert a new BILLABLE client column into a month tab
  of Start Billables.xlsx, keeping the billable block contiguous.

  Inserts the column immediately before the "NON BILLABLE" divider (so it sits
  right after the last existing billable client), matches billable-column
  formatting, gives it a month-total (row 2), and rewrites Billable(E) to span
  the whole billable block: =SUM(F:<last billable>). Excel auto-shifts the
  non-billable columns and their formulas; because the tools resolve columns by
  header name, nothing else needs updating.

  Params: -Workbook, -Sheet (e.g. "July"), -Header (e.g. "Automotive Service Products")
          -NoBackup to skip the backup.

  Idempotent: exits without change if a column with the same header already exists.
#>
param(
  [Parameter(Mandatory=$true)][string]$Workbook,
  [Parameter(Mandatory=$true)][string]$Sheet,
  [Parameter(Mandatory=$true)][string]$Header,
  [switch]$NoBackup
)
$ErrorActionPreference = 'Stop'
$xlPasteFormats = -4122
$FIRST_ROW = 4
function Col([int]$i){ $s=''; while($i -gt 0){ $m=($i-1)%26; $s=[char](65+$m)+$s; $i=[int](($i-$m)/26) }; return $s }

if (-not $NoBackup) {
  $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
  $bak = [IO.Path]::Combine([IO.Path]::GetDirectoryName($Workbook), "Start Billables.backup-$stamp.xlsx")
  Copy-Item -LiteralPath $Workbook -Destination $bak -Force
  Write-Output "Backup: $bak"
}

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false; $xl.DisplayAlerts = $false
try {
  $wb = $xl.Workbooks.Open($Workbook)
  $ws = $wb.Worksheets.Item($Sheet)

  # already present?
  for ($c = 6; $c -le 80; $c++) {
    if ([string]$ws.Cells.Item(1, $c).Value2 -eq $Header) { Write-Output "Column '$Header' already exists (col $c). No change."; $wb.Close($false); return }
  }

  # divider + last day row
  $div = 0
  for ($c = 2; $c -le 80; $c++) { if ([string]$ws.Cells.Item(1, $c).Value2 -eq 'NON BILLABLE') { $div = $c; break } }
  if ($div -eq 0) { throw "No 'NON BILLABLE' divider column on sheet '$Sheet'." }
  $lastRow = $FIRST_ROW
  while ($ws.Cells.Item($lastRow + 1, 1).Value2 -is [double] -and $ws.Cells.Item($lastRow + 1, 1).Value2 -gt 0) { $lastRow++ }

  # insert a blank column at the divider position; it becomes the new last billable
  # column. Excel copies formatting from the left (billable) column by default.
  $ws.Columns.Item($div).Insert() | Out-Null
  $newCol = $div            # new column sits where the divider was
  $L = Col $newCol

  # header + month total + clear any carried-over data
  $ws.Cells.Item(1, $newCol).Value2 = $Header
  $ws.Cells.Item(2, $newCol).Formula = "=SUM($L$FIRST_ROW`:$L$lastRow)"
  $ws.Range("$L$FIRST_ROW`:$L$lastRow").ClearContents() | Out-Null

  # rewrite Billable(E) to span the full billable block F..newCol
  $ws.Cells.Item(2, 5).Formula = "=SUM(F2:$L`2)"
  for ($r = $FIRST_ROW; $r -le $lastRow; $r++) { $ws.Cells.Item($r, 5).Formula = "=SUM(F$r`:$L$r)" }

  $wb.Save()
  Write-Output ("Added billable column '{0}' at {1} (col {2}) on '{3}', rows {4}-{5}. Billable(E) = SUM(F:{1})." -f $Header, $L, $newCol, $Sheet, $FIRST_ROW, $lastRow)
  $wb.Close($true)
  Write-Output "SAVED_OK"
}
finally { $xl.Quit(); [void][Runtime.InteropServices.Marshal]::ReleaseComObject($xl) }
