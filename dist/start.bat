@echo off
setlocal enabledelayedexpansion

echo.
echo ==========================================
echo   HR Certificate Manager
echo ==========================================
echo.

cd /d "%~dp0"

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo Error: PHP not found in PATH
    echo Please ensure PHP is installed and added to your system PATH
    echo.
    pause
    exit /b 1
)

REM Install dependencies if vendor doesn't exist
if not exist vendor (
    echo Installing dependencies...
    call composer install --no-dev
)

REM Clear cache
php artisan config:clear >nul 2>nul
php artisan cache:clear >nul 2>nul

REM Migrate database if needed
if not exist database\database.sqlite (
    echo Initializing database...
    php artisan migrate --force
)

echo.
echo ✓ Starting application...
echo ✓ Opening browser...
echo.
timeout /t 2

start http://localhost:8000

echo.
echo Press Ctrl+C to stop the server
echo.

php artisan serve --port=8000
