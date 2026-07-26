@echo off
powershell -NoProfile -ExecutionPolicy Bypass -Command "
Write-Host 'Stopping AppForm local services...' -ForegroundColor Yellow
Get-Process -Name 'mariadbd', 'mysqld' -ErrorAction SilentlyContinue | Where-Object { $_.Path -like '*MariaDB*' } | Stop-Process -Force
Get-Process -Name 'php' -ErrorAction SilentlyContinue | Stop-Process -Force
Write-Host 'Services stopped.' -ForegroundColor Green
"
pause
