@echo off
cd /d "%~dp0"
echo Running SEO Audit for Root and Grow Providence...
echo.
node audit.js root-and-grow
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\Root and Grow Providence\Root-and-Grow-Providence-SEO-Audit-*.html"') do (
  start "" "..\clients\Root and Grow Providence\%%f"
  goto :done
)
:done
pause
