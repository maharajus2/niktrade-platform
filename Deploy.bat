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

ssh root@2.26.104.37 "cd /opt/niktrade-platform && git pull && php artisan optimize:clear"

echo.
echo ==========================
echo DEPLOY FINISHED
echo ==========================
pause