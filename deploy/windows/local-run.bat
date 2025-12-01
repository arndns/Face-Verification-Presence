@echo off
setlocal

:: Move to project root (two levels up from deploy/windows)
cd /d "%~dp0..\\.."
set "ROOT=%cd%"
set "NPM_CMD=npm"
set "NODE_VERSION=20.19.6"
set "NODE_ARCHIVE=node-v%NODE_VERSION%-win-x64.zip"
set "NODE_DIR=%ROOT%\\bin\\node\\node-v%NODE_VERSION%-win-x64"
set "NODE_ZIP=%ROOT%\\bin\\node.zip"
set "PHP_CMD=%ROOT%\\php.bat"
if exist "%ROOT%\\npm.bat" set "NPM_CMD=%ROOT%\\npm.bat"
if not exist "%PHP_CMD%" set "PHP_CMD=php"

echo ==========================================
echo   Face Verification Presence - Local Run
echo ==========================================

:: 0b. Ensure Node/NPM (portable) if not installed globally
if not exist "%NODE_DIR%\\node.exe" (
    echo.
    echo [INFO] Node %NODE_VERSION% not found. Downloading portable Node...
    if not exist "%ROOT%\\bin" mkdir "%ROOT%\\bin"
    if exist "%NODE_ZIP%" del "%NODE_ZIP%"
    powershell -NoProfile -Command "Invoke-WebRequest -Uri \"https://nodejs.org/dist/v%NODE_VERSION%/%NODE_ARCHIVE%\" -OutFile \"\"\"%NODE_ZIP%\"\"\""
    if errorlevel 1 (
        echo [ERROR] Failed to download Node.
        pause
        exit /b 1
    )
    if exist "%ROOT%\\bin\\node" rmdir /s /q "%ROOT%\\bin\\node"
    mkdir "%ROOT%\\bin\\node"
    tar -xf "%NODE_ZIP%" -C "%ROOT%\\bin\\node"
    if errorlevel 1 (
        echo [ERROR] Failed to extract Node.
        pause
        exit /b 1
    )
)

:: 0c. Create wrappers if missing
if not exist "%ROOT%\\node.bat" (
    >"%ROOT%\\node.bat" echo @echo off
    >>"%ROOT%\\node.bat" echo "%NODE_DIR%\\node.exe" %%*
)
if not exist "%ROOT%\\npm.bat" (
    >"%ROOT%\\npm.bat" echo @echo off
    >>"%ROOT%\\npm.bat" echo set "NODE_HOME=%NODE_DIR%"
    >>"%ROOT%\\npm.bat" echo set "PATH=%%NODE_HOME%%;%%NODE_HOME%%\\node_modules\\npm\\bin;%%PATH%%"
    >>"%ROOT%\\npm.bat" echo "%%NODE_HOME%%\\npm.cmd" %%*
)
if exist "%ROOT%\\npm.bat" set "NPM_CMD=%ROOT%\\npm.bat"

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
if exist "%ROOT%\\php.bat" set "PHP_CMD=%ROOT%\\php.bat"

:: 1b. Ensure composer wrapper (if missing)
if not exist "composer.bat" (
    if exist "bin\composer.phar" (
        if exist "php.bat" (
            set "COMPOSER_PHP_CMD=%%~dp0php.bat"
        ) else (
            set "COMPOSER_PHP_CMD=php"
        )
        >"composer.bat" echo @echo off
        >>"composer.bat" echo "%COMPOSER_PHP_CMD%" "%%~dp0bin\composer.phar" %%*
    ) else if exist "php.bat" (
        echo [INFO] Composer not found. Downloading...
        call .\php.bat -r "copy('https://getcomposer.org/installer','composer-setup.php');"
        call .\php.bat composer-setup.php --install-dir=bin --filename=composer.phar
        del composer-setup.php
        if exist "php.bat" (
            set "COMPOSER_PHP_CMD=%%~dp0php.bat"
        ) else (
            set "COMPOSER_PHP_CMD=php"
        )
        >"composer.bat" echo @echo off
        >>"composer.bat" echo "%COMPOSER_PHP_CMD%" "%%~dp0bin\composer.phar" %%*
    ) else if exist "%PHP_CMD%" (
        echo [INFO] Composer not found. Downloading using %PHP_CMD%...
        call "%PHP_CMD%" -r "copy('https://getcomposer.org/installer','composer-setup.php');"
        call "%PHP_CMD%" composer-setup.php --install-dir=bin --filename=composer.phar
        del composer-setup.php
        if exist "php.bat" (
            set "COMPOSER_PHP_CMD=%%~dp0php.bat"
        ) else (
            set "COMPOSER_PHP_CMD=php"
        )
        >"composer.bat" echo @echo off
        >>"composer.bat" echo "%COMPOSER_PHP_CMD%" "%%~dp0bin\composer.phar" %%*
    ) else (
        echo [WARN] composer.bat not found and PHP is unavailable; please install PHP/Composer globally or rerun setup_portable.ps1.
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
call "%PHP_CMD%" artisan migrate --force
if errorlevel 1 (
    echo [ERROR] Migration failed!
    pause
    exit /b 1
)

:: 4. Build Frontend
echo.
echo [INFO] Building Frontend...
call %NPM_CMD% install
call %NPM_CMD% run build
if errorlevel 1 (
    echo [ERROR] Frontend build failed!
    pause
    exit /b 1
)

:: 5. Start Server
echo.
echo ==========================================
echo   Server Starting...
echo   Open: http://192.168.0.85:8000 (or http://<LAN-IP>:8000)
echo   (Press Ctrl+C to stop)
echo ==========================================
call "%PHP_CMD%" artisan serve --host 0.0.0.0 --port 8000
