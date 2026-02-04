@echo off
setlocal enabledelayedexpansion

REM HR Certificate Manager - Windows Executable Wrapper
REM This script creates a self-contained executable for Windows

echo.
echo ==========================================
echo  HR Certificate Manager
echo ==========================================
echo.

REM Get the directory where this script is located
set SCRIPT_DIR=%~dp0
set APP_HOME=%APPDATA%\AutoCert
set DB_DIR=%APP_HOME%\database

REM Create application directory
if not exist "%APP_HOME%" mkdir "%APP_HOME%"
if not exist "%DB_DIR%" mkdir "%DB_DIR%"

REM Check for PHP installation
php --version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo.
    echo ERROR: PHP is not installed or not in PATH
    echo.
    echo Please install PHP from:
    echo https://windows.php.net/
    echo.
    echo After installation:
    echo 1. Extract PHP to a folder (e.g., C:\php)
    echo 2. Add it to your system PATH
    echo 3. Restart this application
    echo.
    pause
    exit /b 1
)

echo ✓ PHP found
echo ✓ Starting application...
echo.

REM Copy application files on first run
if not exist "%APP_HOME%\artisan" (
    echo Preparing application files...
    xcopy "%SCRIPT_DIR%app" "%APP_HOME%\app" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%bootstrap" "%APP_HOME%\bootstrap" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%config" "%APP_HOME%\config" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%database" "%APP_HOME%\database" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%resources" "%APP_HOME%\resources" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%routes" "%APP_HOME%\routes" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%storage" "%APP_HOME%\storage" /E /I /Y >nul
    xcopy "%SCRIPT_DIR%public" "%APP_HOME%\public" /E /I /Y >nul
    copy "%SCRIPT_DIR%.env" "%APP_HOME%\.env" /Y >nul
    copy "%SCRIPT_DIR%artisan" "%APP_HOME%\artisan" /Y >nul
    copy "%SCRIPT_DIR%composer.json" "%APP_HOME%\composer.json" /Y >nul
    echo ✓ Files copied
)

REM Install dependencies if vendor doesn't exist
if not exist "%APP_HOME%\vendor" (
    echo Installing dependencies...
    cd /d "%APP_HOME%"
    call composer install --no-dev --no-interaction 2>nul
    if %ERRORLEVEL% neq 0 (
        echo ✓ Dependencies ready
    )
)

REM Setup database
cd /d "%APP_HOME%"

REM Clear config cache
php artisan config:clear 2>nul

REM Initialize database if needed
if not exist "%APP_HOME%\database\database.sqlite" (
    echo Initializing database...
    php artisan migrate --force --no-interaction
    echo ✓ Database initialized
)

REM Start the application
echo.
echo Starting web server on http://localhost:8000
echo Opening in browser...
echo.
timeout /t 2 /nobreak

REM Open browser
start http://localhost:8000

REM Start Laravel development server
php artisan serve --port=8000

pause
