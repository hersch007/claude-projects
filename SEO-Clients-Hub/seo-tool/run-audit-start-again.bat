@echo off
cd /d "%~dp0"
echo Running SEO Audit for Start Again Associates...
echo.
node audit.js start-again
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\start-again-associates\Start-Again-Associates-SEO-Audit-*.html"') do (
  start "" "..\clients\start-again-associates\%%f"
  goto :done
)
:done
pause
