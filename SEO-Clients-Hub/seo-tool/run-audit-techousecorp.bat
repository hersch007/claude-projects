@echo off
cd /d "%~dp0"
echo Running SEO Audit for TechouseCorp...
echo.
node audit.js techousecorp
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\TechouseCorp\TechouseCorp-SEO-Audit-*.html"') do (
  start "" "..\clients\TechouseCorp\%%f"
  goto :done
)
:done
pause
