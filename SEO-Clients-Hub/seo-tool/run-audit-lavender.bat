@echo off
cd /d "%~dp0"
echo Running SEO Audit for Lavender Healing Collective...
echo.
node audit.js lavender
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\lavender-healing-collective\Lavender-Healing-Collective-SEO-Audit-*.html"') do (
  start "" "..\clients\lavender-healing-collective\%%f"
  goto :done
)
:done
pause
