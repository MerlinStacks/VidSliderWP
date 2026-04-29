---
description: Creates a correctly structured zip archive of true-video-product-gallery ready for WordPress upload. Uses .NET ZipFile API with forward-slash paths to avoid Linux server issues.
---

// turbo-all

# Zip Plugin for Deployment

> **Why this exists:** PowerShell's `Compress-Archive` uses backslashes (`\`) in zip entry paths. Linux servers (where WordPress typically runs) cannot resolve these, causing "Plugin file does not exist" errors. This workflow uses .NET's `ZipFile` API with explicit forward-slash paths.

## Steps

1. **Delete any existing zip** in the plugin root:

```powershell
Remove-Item "c:\Users\ratte\Local Sites\ck-testing\app\public\wp-content\plugins\true-video-product-gallery\true-video-product-gallery.zip" -Force -ErrorAction SilentlyContinue
```

2. **Build the zip** using .NET `System.IO.Compression` with forward-slash entry paths:

```powershell
$pluginDir = "c:\Users\ratte\Local Sites\ck-testing\app\public\wp-content\plugins\true-video-product-gallery"
$zipPath   = Join-Path $pluginDir "true-video-product-gallery.zip"
$rootName  = "true-video-product-gallery"

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

$excludePatterns = @('\\node_modules\\', '\\.git\\', '\\.agent\\')
$files = Get-ChildItem $pluginDir -Recurse -File | Where-Object {
    $dominated = $false
    foreach ($p in $excludePatterns) { if ($_.FullName -match $p) { $dominated = $true; break } }
    -not $dominated -and $_.FullName -ne $zipPath
}

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($pluginDir.Length + 1)
    $entryName    = "$rootName/" + $relativePath.Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zip, $file.FullName, $entryName, 'Optimal'
    ) | Out-Null
}

$zip.Dispose()
Write-Host "Zip created: $zipPath"
```

3. **Verify the zip** — confirm folder structure uses forward slashes and the main plugin PHP file is at the expected root:

```powershell
$zipPath = "c:\Users\ratte\Local Sites\ck-testing\app\public\wp-content\plugins\true-video-product-gallery\true-video-product-gallery.zip"
Add-Type -AssemblyName System.IO.Compression.FileSystem
$verify = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$total  = $verify.Entries.Count

Write-Host "`nTotal entries: $total"
Write-Host "`nFirst 15 entries:"
$verify.Entries | Select-Object -First 15 FullName | Format-Table -AutoSize

$mainFile = $verify.Entries | Where-Object { $_.FullName -eq "true-video-product-gallery/true-video-product-gallery.php" }
if ($mainFile) {
    Write-Host "`n[OK] Main plugin file found at: $($mainFile.FullName)"
} else {
    Write-Host "`n[ERROR] Main plugin file NOT found — WordPress will reject this zip."
}

$backslash = $verify.Entries | Where-Object { $_.FullName -match '\\' }
if ($backslash.Count -eq 0) {
    Write-Host "[OK] All paths use forward slashes."
} else {
    Write-Host "[ERROR] $($backslash.Count) entries still use backslashes."
}

$verify.Dispose()
```

4. **Done** — the zip is at `true-video-product-gallery/true-video-product-gallery.zip`, ready for upload via WordPress admin or file manager.
