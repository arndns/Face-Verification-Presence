@echo off
setlocal

:: Move to project root
cd /d "%~dp0.."

echo ==========================================
echo   Face Verification Presence - Quick Run
echo ==========================================

:: 1. Build Frontend
echo.
echo [INFO] Installing & Building Frontend...
call npm install
call npm run build
if errorlevel 1 (
    echo [ERROR] Frontend build failed!
    pause
    exit /b 1
)

:: 2. Start Server
echo.
echo ==========================================
echo   Server Starting...
echo   Open: http://127.0.0.1:8000
echo   (Press Ctrl+C to stop)
echo ==========================================
.\php.bat artisan serve
