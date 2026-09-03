@echo off
cd /d "%~dp0"
echo Running SEO Audit for Mothership Roofing...
echo.
node audit.js mothershiproofing
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\MothershipRoofing\Mothership*-SEO-Audit-*.html"') do (
  start "" "..\clients\MothershipRoofing\%%f"
  goto :done
)
:done
pause
