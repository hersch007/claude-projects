# Build script for Start Performance plugins
# Run from the /plugin folder: .\build.ps1
# Builds every plugin subfolder that contains a matching .php main file.

Add-Type -Assembly "System.IO.Compression"
Add-Type -Assembly "System.IO.Compression.FileSystem"

function Build-Plugin {
    param( [string]$pluginDir )

    $slug     = Split-Path $pluginDir -Leaf
    $mainFile = Join-Path $pluginDir "$slug.php"

    if ( -not (Test-Path $mainFile) ) {
        Write-Host "Skipping $slug (no main file found)" -ForegroundColor Yellow
        return
    }

    $version = (Get-Content $mainFile | Select-String "Version:\s+(.+)").Matches[0].Groups[1].Value.Trim()
    $zipName = "$slug-$version.zip"
    $dest    = Join-Path $PSScriptRoot $zipName

    Write-Host "Building $zipName..." -ForegroundColor Cyan

    if (Test-Path $dest) { [System.IO.File]::Delete($dest) }

    $stream  = [System.IO.File]::Open($dest, [System.IO.FileMode]::Create)
    $archive = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)

    Get-ChildItem -Path $pluginDir -Recurse -File | ForEach-Object {
        $relativePath = $_.FullName.Substring($pluginDir.Length + 1).Replace('\', '/')
        $entryName    = "$slug/" + $relativePath
        $entry        = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream  = $entry.Open()
        $fileStream   = [System.IO.File]::OpenRead($_.FullName)
        $fileStream.CopyTo($entryStream)
        $fileStream.Dispose()
        $entryStream.Dispose()
    }

    $archive.Dispose()
    $stream.Dispose()

    Write-Host "Done: $zipName" -ForegroundColor Green
}

# Build all plugin subfolders
Get-ChildItem -Path $PSScriptRoot -Directory | ForEach-Object {
    Build-Plugin $_.FullName
}
