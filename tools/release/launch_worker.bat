@echo off
:: AppForm Update Worker Launcher
:: Arguments: <php_exe> <worker_script> <update_id>
:: This batch file launches the PHP worker as a truly detached process.
set PHP_EXE=%~1
set WORKER=%~2
set UPDATE_ID=%~3
start "" /B "%PHP_EXE%" -f "%WORKER%" -- %UPDATE_ID%
exit /b 0
