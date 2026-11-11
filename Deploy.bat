@echo off
title Niktrade Deploy

echo.
echo ==========================
echo NIKTRADE DEPLOY
echo ==========================
echo.

if "%~1"=="" (
    echo Usage: Deploy.bat FULL_COMMIT REMOTE_BRANCH
    echo Commit and push reviewed changes separately before deploying.
    exit /b 1
)
if "%~2"=="" exit /b 1

echo.
echo ==========================
echo CONNECTING TO SERVER
echo ==========================
echo.

scp scripts/deploy.sh root@5.63.152.207:/tmp/niktrade-deploy.sh
if errorlevel 1 exit /b 1
ssh root@5.63.152.207 "bash /tmp/niktrade-deploy.sh %~1 %~2"
if errorlevel 1 exit /b 1

echo.
echo ==========================
echo DEPLOY FINISHED
echo ==========================
pause
