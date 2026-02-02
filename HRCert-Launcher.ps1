# HR Certificate Manager - Windows Launcher
# This script checks for PHP and runs the application

param(
    [switch]$Hidden = $false
)

$ErrorActionPreference = "SilentlyContinue"

# Hide window if requested
if ($Hidden) {
    $null = New-Item -Path "HKCU:\Console\$PID" -Force
}

$APP_HOME = "$env:APPDATA\HRCert"
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host ""
Write-Host "=========================================="
Write-Host "  HR Certificate Manager"
Write-Host "=========================================="
Write-Host ""

# Check if PHP is installed
$phpVersion = & php --version 2>$null
if (-not $phpVersion) {
    Write-Host "ERROR: PHP is not installed or not in PATH"
    Write-Host ""
    Write-Host "Please install PHP from: https://windows.php.net/"
    Write-Host ""
    Write-Host "Installation steps:"
    Write-Host "1. Download PHP from https://windows.php.net/download/"
    Write-Host "2. Extract to C:\php (or your preferred location)"
    Write-Host "3. Add C:\php to your system PATH environment variable"
    Write-Host "4. Restart this application"
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ PHP found"

# Create app directory
if (-not (Test-Path $APP_HOME)) {
    New-Item -ItemType Directory -Path $APP_HOME -Force > $null
}

# Copy files on first run
if (-not (Test-Path "$APP_HOME\artisan")) {
    Write-Host "✓ Preparing application files..."
    
    @("app", "bootstrap", "config", "database", "resources", "routes", "storage", "public") | ForEach-Object {
        if (Test-Path "$SCRIPT_DIR\$_") {
            Copy-Item "$SCRIPT_DIR\$_" "$APP_HOME\$_" -Recurse -Force
        }
    }
    
    @(".env", "artisan", "composer.json") | ForEach-Object {
        if (Test-Path "$SCRIPT_DIR\$_") {
            Copy-Item "$SCRIPT_DIR\$_" "$APP_HOME\$_" -Force
        }
    }
}

# Install dependencies
if (-not (Test-Path "$APP_HOME\vendor")) {
    Write-Host "Installing dependencies (this may take a minute)..."
    Push-Location $APP_HOME
    & composer install --no-dev --no-interaction 2>$null
    Pop-Location
}

# Setup database
Push-Location $APP_HOME
& php artisan config:clear 2>$null

if (-not (Test-Path "$APP_HOME\database\database.sqlite")) {
    Write-Host "✓ Initializing database..."
    & php artisan migrate --force --no-interaction
}

Write-Host ""
Write-Host "✓ Starting web server on http://localhost:8000"
Write-Host "✓ Opening in browser..."
Write-Host ""

# Open browser
Start-Process "http://localhost:8000"

# Start Laravel server
Write-Host "Server running - Press Ctrl+C to stop"
Write-Host ""

& php artisan serve --port=8000

Pop-Location
