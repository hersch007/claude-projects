@echo off
cd /d "%~dp0"
echo Running SEO Audit for Guidance Through Grief Counseling...
echo.
node audit.js guidance-grief
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\Guidance Through Grief Counseling\Guidance-Through-Grief-Counseling-SEO-Audit-*.html"') do (
  start "" "..\clients\Guidance Through Grief Counseling\%%f"
  goto :done
)
:done
pause
