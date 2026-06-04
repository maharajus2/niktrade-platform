@echo off

echo ==========================
echo PUSH TO GITHUB
echo ==========================

git add .

set /p msg=Commit message:

git commit -m "%msg%"

git push

pause