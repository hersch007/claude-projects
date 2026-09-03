@echo off
cd /d "%~dp0"
echo Running SEO Audit for Garlock...
echo.
node audit.js garlock
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\Garlock\Garlock-SEO-Audit-*.html"') do (
  start "" "..\clients\Garlock\%%f"
  goto :done
)
:done
pause
