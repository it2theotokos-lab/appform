@echo off
echo Restarting AppForm local services...
call "%~dp0stop-local.bat"
timeout /t 2 /nobreak > nul
call "%~dp0start-local.bat"
