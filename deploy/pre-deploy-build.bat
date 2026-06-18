@echo off
:: ============================================================================
::  VillaCRM Platform — Local Pre-Deploy Build (Windows)
::  Run this on your Windows machine BEFORE uploading files to the server.
::  It compiles frontend assets and installs production Composer dependencies.
::
::  Usage: double-click, or run from cmd/PowerShell:
::    .\deploy\pre-deploy-build.bat
:: ============================================================================
setlocal enabledelayedexpansion

cd /d "%~dp0\.."

echo.
echo  VillaCRM Platform ^| Pre-Deploy Build
echo  ======================================
echo.

:: â”€â”€ 1. Check Node.js â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Node.js not found.
    echo          Download from https://nodejs.org and install, then re-run.
    pause
    exit /b 1
)
for /f "tokens=*" %%v in ('node --version') do set NODE_VER=%%v
echo  [OK] Node.js %NODE_VER%

:: â”€â”€ 2. Check npm â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] npm not found. Reinstall Node.js from https://nodejs.org
    pause
    exit /b 1
)

:: â”€â”€ 3. Check Composer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo  [ERROR] Composer not found.
    echo          Download from https://getcomposer.org/Composer-Setup.exe
    pause
    exit /b 1
)
for /f "tokens=*" %%v in ('composer --version --no-ansi 2^>nul') do set COMP_VER=%%v
echo  [OK] %COMP_VER%

echo.
echo  Step 1/3 ^| Installing npm packages...
call npm install --silent
if %errorlevel% neq 0 (
    echo  [ERROR] npm install failed.
    pause
    exit /b 1
)
echo  [OK] npm packages installed

echo.
echo  Step 2/3 ^| Building production assets...
call npm run build
if %errorlevel% neq 0 (
    echo  [ERROR] npm run build failed. Check Vite config.
    pause
    exit /b 1
)
echo  [OK] Assets compiled to public\build\

echo.
echo  Step 3/3 ^| Installing Composer dependencies (production)...
call composer install --no-dev --optimize-autoloader --prefer-dist --quiet
if %errorlevel% neq 0 (
    echo.
    echo  [ERROR] Composer install failed.
    echo          ======================================================================
    echo          This is extremely common on Windows when a local web server, such as
    echo          Laravel Herd, XAMPP, php-cgi, PHP-FPM, or 'php artisan serve', is active
    echo          and has locked files in the 'vendor/' directory.
    echo.
    echo          Please STOP or PAUSE Laravel Herd or your local PHP services to release
    echo          the file locks, then run this build script again.
    echo          ======================================================================
    echo.
    pause
    exit /b 1
)
echo  [OK] Composer dependencies installed (no dev packages)

:: â”€â”€ 4. Create zip for upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo.
set /p MAKE_ZIP="Create upload zip? (y/n): "
if /i "%MAKE_ZIP%"=="y" (
    for /f "tokens=1-3 delims=/" %%a in ("%date%") do set DATESTR=%%c%%a%%b
    set ARCHIVE=VillaCRM_!DATESTR!.zip

    echo  Creating !ARCHIVE!...
    tar.exe -a -c -f "!ARCHIVE!" --exclude=node_modules --exclude=.git --exclude=.env --exclude=database/database.sqlite --exclude=*.zip --exclude=mobile *
    if !errorlevel! neq 0 (
        echo  [WARNING] Archive created, but some files may have been locked or skipped.
    ) else (
        echo  [OK] Archive created: !ARCHIVE!
    )

    echo.
    echo  â”€â”€ Upload instructions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    echo  1. Upload !ARCHIVE! to cPanel File Manager
    echo     (Upload to /home/yourusername/ — NOT inside public_html)
    echo  2. Right-click the zip ^> Extract
    echo  3. Rename extracted folder to 'villacrm' if needed
    echo  4. Upload deploy\installer.php to public_html\install.php
    echo  5. Visit https://yourdomain.com/install.php
    echo  â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
) else (
    echo.
    echo  â”€â”€ Manual upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    echo  Upload this entire folder to /home/yourusername/villacrm/
    echo  using FileZilla or cPanel File Manager.
    echo.
    echo  DO NOT upload these folders (save time + space):
    echo    node_modules\     .git\     .env     mobile\
    echo.
    echo  Upload deploy\installer.php to public_html\install.php
    echo  â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
)

echo.
echo  Build complete!
echo.
pause
