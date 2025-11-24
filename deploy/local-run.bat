@echo off
setlocal

:: Move to project root
cd /d "%~dp0.."

echo ==========================================
echo   Face Verification Presence - Local Run
echo ==========================================

:: 1. Check/Install Portable PHP
if not exist "bin\php\php.exe" (
    echo [WARN] Portable PHP not found. Running setup...
    powershell -ExecutionPolicy Bypass -File setup_portable.ps1
    if errorlevel 1 (
        echo [ERROR] Setup failed!
        pause
        exit /b 1
    )
)

:: 2. Install Backend Dependencies
echo.
echo [INFO] Checking Backend Dependencies...
call .\composer.bat install
if errorlevel 1 (
    echo [ERROR] Composer install failed!
    pause
    exit /b 1
)

:: 3. Setup Database
echo.
echo [INFO] Migrating Database...
call .\php.bat artisan migrate --force
if errorlevel 1 (
    echo [ERROR] Migration failed!
    pause
    exit /b 1
)

:: 4. Build Frontend
echo.
echo [INFO] Building Frontend...
call npm install
call npm run build
if errorlevel 1 (
    echo [ERROR] Frontend build failed!
    pause
    exit /b 1
)

:: 5. Start Server
echo.
echo ==========================================
echo   Server Starting...
echo   Open: http://127.0.0.1:8000
echo   (Press Ctrl+C to stop)
echo ==========================================
.\php.bat artisan serve
