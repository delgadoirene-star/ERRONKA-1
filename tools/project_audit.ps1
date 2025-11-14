# CORRECTED: Search from parent directory (project root), not script directory

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$root = Split-Path -Parent $scriptDir  # Go up one level to project root

Write-Host "Project audit starting at: $root`n" -ForegroundColor Cyan

# 1) PHP syntax check
Write-Host "=== 1) PHP syntax check ===" -ForegroundColor Yellow
Get-ChildItem -Path $root -Recurse -Filter *.php -ErrorAction SilentlyContinue |
  Where-Object { $_.FullName -notmatch '\\tools\\' } |
  ForEach-Object {
    $file = $_.FullName
    $out = & php -l $file 2>&1
    if ($LASTEXITCODE -ne 0) {
      Write-Host "SYNTAX ERROR: $file" -ForegroundColor Red
    }
  }
Write-Host "PHP syntax check complete.`n" -ForegroundColor Green

# 2) Security functions
Write-Host "=== 2) CSRF / Password / Session functions ===" -ForegroundColor Yellow
$secFuncs = Select-String -Path (Join-Path $root "**\*.php") -Pattern 'generateCSRFToken|verifyCSRFToken|session_regenerate_id|password_hash|password_verify|bind_param' -ErrorAction SilentlyContinue
Write-Host "Found $($secFuncs.Count) security function occurrences:`n" -ForegroundColor Cyan
$secFuncs | ForEach-Object { 
  Write-Host "$($_.Filename):$($_.LineNumber): $($_.Line.Trim())"
}

# 3) require_once check
Write-Host "`n=== 3) require/include without _once ===" -ForegroundColor Yellow
$badIncludes = Select-String -Path (Join-Path $root "**\*.php") -Pattern '\b(require|include)\s*\(' -ErrorAction SilentlyContinue |
  Where-Object { $_.Line -notmatch 'require_once|include_once' }
if ($badIncludes.Count -gt 0) {
  $badIncludes | ForEach-Object { Write-Host "$($_.Filename):$($_.LineNumber): $($_.Line.Trim())" -ForegroundColor Yellow }
} else {
  Write-Host "All uses are _once. Good!`n" -ForegroundColor Green
}

Write-Host "========== AUDIT FINISHED ==========" -ForegroundColor Cyan