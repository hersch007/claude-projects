@echo off
cd /d "%~dp0"
echo Running SEO Audit for Karen Hubbars Therapy...
echo.
node audit.js karen-hubbars
echo.
echo Done! Opening report...
for /f "delims=" %%f in ('dir /b /o-d "..\clients\Karen Hubbars Therapy\Karen-Hubbars-Therapy-SEO-Audit-*.html"') do (
  start "" "..\clients\Karen Hubbars Therapy\%%f"
  goto :done
)
:done
pause
