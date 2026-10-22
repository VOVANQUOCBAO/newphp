@echo off
setlocal
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0support\scripts\chay_local.ps1" %*
set "taskExit=%errorlevel%"
if not "%taskExit%"=="0" (
    echo.
    echo Khong khoi dong duoc. Xem README.md de cau hinh XAMPP / MySQL.
)
echo.
pause
exit /b %taskExit%
