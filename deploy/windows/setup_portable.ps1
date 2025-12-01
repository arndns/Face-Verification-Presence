# Bootstrap portable PHP 8.2 + Composer for this project (Windows)
# Run from deploy/windows; it will place binaries in <repo>/bin and write php.bat/composer.bat in <repo>.

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Paths
$ScriptDir   = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Resolve-Path (Join-Path $ScriptDir '..\..')
$BinDir      = Join-Path $ProjectRoot 'bin'
$PhpDir      = Join-Path $BinDir 'php'
$PhpZipPath  = Join-Path $BinDir 'php.zip'

# PHP download source (match the version we tested with)
$PhpZipUrl = 'https://windows.php.net/downloads/releases/php-8.2.29-Win32-vs16-x64.zip'

Write-Host "==> Project root: $ProjectRoot"
New-Item -ItemType Directory -Force -Path $BinDir | Out-Null

# Download PHP zip
Write-Host "==> Downloading PHP from $PhpZipUrl"
Invoke-WebRequest -Uri $PhpZipUrl -OutFile $PhpZipPath

# Extract PHP
if (Test-Path $PhpDir) { Remove-Item -Recurse -Force $PhpDir }
Write-Host "==> Extracting PHP to $PhpDir"
Expand-Archive -Path $PhpZipPath -DestinationPath $PhpDir -Force

# Configure php.ini with common extensions
$phpIni = Join-Path $PhpDir 'php.ini'
Copy-Item (Join-Path $PhpDir 'php.ini-production') $phpIni -Force
$extensions = @(
    'extension_dir = "ext"',
    'extension=curl',
    'extension=mbstring',
    'extension=openssl',
    'extension=zip',
    'extension=fileinfo',
    'extension=gd',
    'extension=pdo_sqlite',
    'extension=sqlite3'
)
$iniContent = Get-Content $phpIni
foreach ($ext in $extensions) {
    $pattern = '^;?' + [regex]::Escape($ext) + '$'
    $iniContent = $iniContent -replace $pattern, $ext
    if (-not ($iniContent -contains $ext)) { $iniContent += $ext }
}
$iniContent | Set-Content $phpIni -Encoding ASCII

# Create php.bat wrapper in project root
$phpBat = "@echo off`n""%~dp0bin\\php\\php.exe"" %*`n"
Set-Content -Path (Join-Path $ProjectRoot 'php.bat') -Value $phpBat -Encoding ASCII

# Install Composer (portable)
Write-Host "==> Installing Composer"
& (Join-Path $ProjectRoot 'php.bat') -r "copy('https://getcomposer.org/installer','composer-setup.php');"
& (Join-Path $ProjectRoot 'php.bat') composer-setup.php --install-dir=$BinDir --filename=composer.phar
Remove-Item (Join-Path $ProjectRoot 'composer-setup.php') -ErrorAction SilentlyContinue

# Create composer.bat wrapper in project root
$composerBat = "@echo off`n""%~dp0php.bat"" ""%~dp0bin\\composer.phar"" %*`n"
Set-Content -Path (Join-Path $ProjectRoot 'composer.bat') -Value $composerBat -Encoding ASCII

Write-Host "==> Done. Use php via php.bat and composer via composer.bat in project root."
