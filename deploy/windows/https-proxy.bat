@echo off
setlocal

:: HTTPS proxy for LAN testing (camera/webcam requires secure context)
:: Prerequisites: mkcert installed and Node (npm/npx) available.
:: Usage: run your Laravel server on port 8000, then run this script.

:: Move to project root (two levels up from deploy/windows)
cd /d "%~dp0..\\.."
set "ROOT=%cd%"
set "CERT_DIR=%ROOT%\\certs"
set "CERT_FILE=%CERT_DIR%\\dev-cert.pem"
set "KEY_FILE=%CERT_DIR%\\dev-key.pem"
set "TARGET_PORT=8000"
set "HTTPS_PORT=8443"

echo ==========================================
echo   Face Verification Presence - HTTPS Proxy
echo ==========================================

:: Detect LAN IPv4 (Wi-Fi/Ethernet); fallback to localhost
set "LAN_IP="
<<<<<<< ours
<<<<<<< ours
<<<<<<< ours
<<<<<<< ours
for /f "usebackq delims=" %%i in (`powershell -NoProfile -ExecutionPolicy Bypass -Command "$ip=(Get-NetIPAddress -AddressFamily IPv4 -PrefixOrigin Dhcp -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -and $_.IPAddress -notmatch '^169\\.254\\.' } | Select-Object -First 1 -ExpandProperty IPAddress); if(-not $ip){ $ip=(Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -and $_.IPAddress -notmatch '^169\\.254\\.' } | Select-Object -First 1 -ExpandProperty IPAddress) }; if(-not $ip){ $ip='127.0.0.1' }; Write-Output $ip"`) do (
=======
for /f "delims=" %%i in ('powershell -NoProfile -ExecutionPolicy Bypass -Command "(Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -and $_.PrefixOrigin -ne ' + "'WellKnown'" + ' -and $_.IPAddress -notmatch ' + "'^169\\.254\\.'" + ' } | Select-Object -First 1 -ExpandProperty IPAddress) -join ''''"') do (
>>>>>>> theirs
    set "LAN_IP=%%i"
)
=======
for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command ^
    "$ip = Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias '*Wi*','*Ethernet*' -ErrorAction SilentlyContinue | Where-Object { $_.PrefixOrigin -ne 'WellKnown' } | Select-Object -First 1 -ExpandProperty IPAddress; if (-not $ip) { $ip = '127.0.0.1' }; Write-Output $ip"`) do set "LAN_IP=%%i"
>>>>>>> theirs
if not defined LAN_IP set "LAN_IP=127.0.0.1"
=======
for /f "usebackq tokens=*" %%i in (`powershell -NoProfile -Command ^
    "$ip = Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias '*Wi*','*Ethernet*' -ErrorAction SilentlyContinue | Where-Object { $_.PrefixOrigin -ne 'WellKnown' } | Select-Object -First 1 -ExpandProperty IPAddress; if (-not $ip) { $ip = '127.0.0.1' }; Write-Output $ip"`) do set "LAN_IP=%%i"
if "%LAN_IP%"=="" set "LAN_IP=127.0.0.1"
>>>>>>> theirs
=======
for /f "usebackq tokens=*" %%i in (`powershell -NoProfile -Command ^
    "$ip = Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias '*Wi*','*Ethernet*' -ErrorAction SilentlyContinue | Where-Object { $_.PrefixOrigin -ne 'WellKnown' } | Select-Object -First 1 -ExpandProperty IPAddress; if (-not $ip) { $ip = '127.0.0.1' }; Write-Output $ip"`) do set "LAN_IP=%%i"
if "%LAN_IP%"=="" set "LAN_IP=127.0.0.1"
>>>>>>> theirs

echo [INFO] Detected LAN IP: %LAN_IP%

:: Check mkcert
where mkcert >nul 2>&1
if errorlevel 1 (
    echo [ERROR] mkcert not found. Install it first (e.g. choco install mkcert) then rerun.
    exit /b 1
)

:: Ensure cert folder
if not exist "%CERT_DIR%" mkdir "%CERT_DIR%"

:: Generate/update certificate for current IP + localhost
echo [INFO] Generating local certificate (mkcert)...
mkcert -cert-file "%CERT_FILE%" -key-file "%KEY_FILE%" %LAN_IP% localhost 127.0.0.1 >nul
if errorlevel 1 (
    echo [ERROR] mkcert failed. Ensure trust store is installed: mkcert -install
    exit /b 1
)

:: Start HTTPS proxy to Laravel dev server
echo.
echo [INFO] Starting HTTPS proxy on port %HTTPS_PORT% => http://127.0.0.1:%TARGET_PORT%
echo [INFO] Access via: https://%LAN_IP%:%HTTPS_PORT%
echo [INFO] Keep this window open. Press Ctrl+C to stop.
echo.

npx local-ssl-proxy --source %HTTPS_PORT% --target %TARGET_PORT% --cert "%CERT_FILE%" --key "%KEY_FILE%"

endlocal
