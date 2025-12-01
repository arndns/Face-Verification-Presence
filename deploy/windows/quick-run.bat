@echo off
setlocal

:: Move to project root (two levels up from deploy/windows)
cd /d "%~dp0..\\.."

echo ==========================================
echo   Face Verification Presence - Quick Run
echo ==========================================

:: 1. Build Frontend
echo.
echo [INFO] Installing ^& Building Frontend...
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
echo   Open: http://192.168.0.85:8000 (or http://<LAN-IP>:8000)
echo   (Press Ctrl+C to stop)
echo ==========================================
.\php.bat artisan serve --host 0.0.0.0 --port 8000
