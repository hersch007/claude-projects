@echo off
cd /d "%~dp0"
echo Running SEO Audit for Joe Welch Photography...
echo.
node audit.js joewelchphoto
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\JoeWelchPhotography\Joe-Welch-Photography-SEO-Audit-*.html"') do (
  start "" "..\clients\JoeWelchPhotography\%%f"
  goto :done
)
:done
pause
