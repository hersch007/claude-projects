@echo off
cd /d "%~dp0"
echo Running SEO Audit for Ari + Ava...
echo.
node audit.js ariandava
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\AriAndAva\Ari*-SEO-Audit-*.html"') do (
  start "" "..\clients\AriAndAva\%%f"
  goto :done
)
:done
pause
