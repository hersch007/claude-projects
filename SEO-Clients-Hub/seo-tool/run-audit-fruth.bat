@echo off
cd /d "%~dp0"
echo Running SEO Audit for Fruth Custom Packaging...
echo.
node audit.js fruth
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\Fruth\Fruth-Custom-Packaging-SEO-Audit-*.html"') do (
  start "" "..\clients\Fruth\%%f"
  goto :done
)
:done
pause
