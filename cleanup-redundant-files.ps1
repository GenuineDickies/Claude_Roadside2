# RoadRunner B3 - Redundant File Cleanup Script
# Created: February 5, 2026
# Purpose: Remove duplicate files and organize workspace

Write-Host "RoadRunner B3 - Workspace Cleanup" -ForegroundColor Cyan
Write-Host "==================================`n" -ForegroundColor Cyan

$workspaceRoot = "\\wsl.localhost\Ubuntu\var\www\html\claude_admin2"

# Create archive directory for reference files
$archiveDir = Join-Path $workspaceRoot "_archive_reference"
if (-not (Test-Path $archiveDir)) {
    Write-Host "Creating archive directory: _archive_reference" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $archiveDir -Force | Out-Null
}

# Files to archive (keep for reference)
$filesToArchive = @(
    "dash.jsx",
    "service-catalog.html"
)

Write-Host "`n[1/4] Archiving reference files..." -ForegroundColor Green
foreach ($file in $filesToArchive) {
    $sourcePath = Join-Path $workspaceRoot $file
    if (Test-Path $sourcePath) {
        $destPath = Join-Path $archiveDir $file
        Write-Host "  -> Archiving: $file" -ForegroundColor Gray
        Copy-Item -Path $sourcePath -Destination $destPath -Force
        Remove-Item -Path $sourcePath -Force
    }
}

# Remove the entire B3 subdirectory (complete duplicate)
$b3Dir = Join-Path $workspaceRoot "B3"
Write-Host "`n[2/4] Removing duplicate B3 directory..." -ForegroundColor Green
if (Test-Path $b3Dir) {
    Write-Host "  -> Removing: B3/ (complete duplicate)" -ForegroundColor Gray
    Remove-Item -Path $b3Dir -Recurse -Force
}

# Remove root-level duplicate page files (keep in pages/ directory only)
$rootDuplicates = @(
    "dashboard.php",
    "customers.php",
    "technicians.php",
    "invoices.php",
    "service-requests.php",
    "login.php"
)

Write-Host "`n[3/4] Removing root-level duplicate page files..." -ForegroundColor Green
foreach ($file in $rootDuplicates) {
    $filePath = Join-Path $workspaceRoot $file
    if (Test-Path $filePath) {
        Write-Host "  -> Removing: $file (duplicate of pages/$file)" -ForegroundColor Gray
        Remove-Item -Path $filePath -Force
    }
}

# Organize working files into a _working directory
$workingDir = Join-Path $workspaceRoot "_working"
if (-not (Test-Path $workingDir)) {
    Write-Host "`n[4/4] Creating _working directory for active development..." -ForegroundColor Green
    New-Item -ItemType Directory -Path $workingDir -Force | Out-Null
}

# Move active working files to _working directory
$workingFiles = @(
    "index.php",
    "services.php",
    "style.css"
)

foreach ($file in $workingFiles) {
    $sourcePath = Join-Path $workspaceRoot $file
    if (Test-Path $sourcePath) {
        $destPath = Join-Path $workingDir $file
        Write-Host "  -> Moving to _working/: $file" -ForegroundColor Gray
        Move-Item -Path $sourcePath -Destination $destPath -Force
    }
}

# Summary
Write-Host "`n==================================`n" -ForegroundColor Cyan
Write-Host "Cleanup Complete!" -ForegroundColor Green
Write-Host "`nWorkspace Structure:" -ForegroundColor Cyan
Write-Host "  [Keep] pages/          - All page files" -ForegroundColor White
Write-Host "  [Keep] config/         - Database configuration" -ForegroundColor White
Write-Host "  [Keep] includes/       - Helper functions" -ForegroundColor White
Write-Host "  [Keep] api/            - API endpoints" -ForegroundColor White
Write-Host "  [Keep] assets/         - CSS, JS, images" -ForegroundColor White
Write-Host "  [Keep] DESIGN.md       - Design system documentation" -ForegroundColor White
Write-Host "  [Keep] README.md       - Project notes" -ForegroundColor White
Write-Host "  [Keep] .github/        - GitHub configuration" -ForegroundColor White
Write-Host "  [New]  _working/       - Active development files" -ForegroundColor Yellow
Write-Host "  [New]  _archive_reference/ - Original reference files" -ForegroundColor Yellow
Write-Host "`n  [Removed] B3/         - Complete duplicate (deleted)" -ForegroundColor Red
Write-Host "  [Removed] Root duplicates - Moved to _working/ or deleted" -ForegroundColor Red

Write-Host "`nProduction Location: /var/www/html/public/B3/" -ForegroundColor Cyan
Write-Host "Working Location:    /var/www/html/claude_admin2/" -ForegroundColor Cyan

Write-Host "`nTo deploy changes to production:" -ForegroundColor Yellow
Write-Host "  Copy-Item '_working\file.php' -Destination '\\wsl.localhost\Ubuntu\var\www\html\public\B3\file.php'" -ForegroundColor Gray
