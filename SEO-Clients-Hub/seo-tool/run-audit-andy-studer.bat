@echo off
cd /d "%~dp0"
echo Running SEO Audit for Andy Studer...
echo.
node audit.js andy-studer
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\Andy Studer\Andy-Studer-SEO-Audit-*.html"') do (
  start "" "..\clients\Andy Studer\%%f"
  goto :done
)
:done
pause
