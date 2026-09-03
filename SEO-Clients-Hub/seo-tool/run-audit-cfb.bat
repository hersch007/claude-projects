@echo off
cd /d "%~dp0"
echo Running SEO Audit for CFB Cleanroom Film...
echo.
node audit.js cfb
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\CFB\CFB-Cleanroom-Film-SEO-Audit-*.html"') do (
  start "" "..\clients\CFB\%%f"
  goto :done
)
:done
pause
