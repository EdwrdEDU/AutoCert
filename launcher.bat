@echo off
setlocal enabledelayedexpansion

REM HR Certificate Manager - Standalone Executable Launcher
REM This script extracts and runs the PHAR archive

set APP_DIR=%APPDATA%\HRCert
set PHAR_FILE=%~dp0HRCert.phar
set EXTRACTED_DIR=%APP_DIR%\extracted

REM Create directories if they don't exist
if not exist "%APP_DIR%" mkdir "%APP_DIR%"
if not exist "%EXTRACTED_DIR%" mkdir "%EXTRACTED_DIR%"

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo.
    echo ==========================================
    echo  HR Certificate Manager - Error
    echo ==========================================
    echo.
    echo PHP is not installed or not in PATH
    echo.
    echo Please install PHP from:
    echo https://windows.php.net/download/
    echo.
    echo After installation, restart your computer or add PHP to your PATH
    echo.
    pause
    exit /b 1
)

REM Extract PHAR if not already extracted
if not exist "%EXTRACTED_DIR%\artisan" (
    echo Extracting application...
    php -r "copy('%PHAR_FILE%', '%EXTRACTED_DIR%\app.phar'); chdir('%EXTRACTED_DIR%'); include 'app.phar'; require_once 'bootstrap/autoload.php';" 2>nul
    
    REM Alternative extraction method
    php -r "$phar = new Phar('%PHAR_FILE%'); $phar->extractTo('%EXTRACTED_DIR%');" 2>nul
)

REM Change to extracted directory
cd /d "%EXTRACTED_DIR%"

REM Run setup if needed
if not exist "%APP_DIR%\database.sqlite" (
    echo Initializing application...
    if not exist vendor (
        echo Installing dependencies...
        call composer install --no-dev --no-interaction --prefer-dist 2>nul
    )
    php artisan config:clear 2>nul
    php artisan migrate --force --no-interaction 2>nul
)

REM Clear cache
php artisan config:clear 2>nul
php artisan cache:clear 2>nul

echo.
echo ==========================================
echo  HR Certificate Manager
echo ==========================================
echo.
echo Opening application...
timeout /t 2 /nobreak

start http://localhost:8000

echo.
echo Server running on http://localhost:8000
echo Press Ctrl+C to stop
echo.

php artisan serve --port=8000
