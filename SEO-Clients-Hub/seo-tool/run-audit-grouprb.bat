@echo off
cd /d "%~dp0"
echo Running SEO Audit for GroupRB...
echo.
node audit.js grouprb
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\GroupRB\GroupRB-SEO-Audit-*.html"') do (
  start "" "..\clients\GroupRB\%%f"
  goto :done
)
:done
pause
