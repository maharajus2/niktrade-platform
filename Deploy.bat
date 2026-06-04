@echo off
title Niktrade Deploy

echo.
echo ==========================
echo NIKTRADE DEPLOY
echo ==========================
echo.

git add .

set /p msg=Commit message: 

git commit -m "%msg%"

if errorlevel 1 (
    echo.
    echo Commit skipped or failed.
)

git push

echo.
echo ==========================
echo CONNECTING TO SERVER
echo ==========================
echo.

ssh root@ТВОЙ_IP "cd /opt/niktrade-platform && git pull && php artisan optimize:clear"

echo.
echo ==========================
echo DEPLOY FINISHED
echo ==========================
pause