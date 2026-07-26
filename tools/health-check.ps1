# AppForm Local Environment Health Check

$phpPath = "C:\Antigravity-PRJ\Tools\PHP\php.exe"
$appPath = "C:\Antigravity-PRJ\Projects\Appform"

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "AppForm Health Diagnostics..." -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan

# 1. Check Port 3307 & MariaDB Process
$port3307 = Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue
if ($port3307) {
    Write-Host "[OK] Port 3307 is Listening (MariaDB)" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Port 3307 is NOT Listening" -ForegroundColor Red
}

# 2. Check Port 8080 & PHP Process
$port8080 = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue
if ($port8080) {
    Write-Host "[OK] Port 8080 is Listening (PHP Web Server)" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Port 8080 is NOT Listening" -ForegroundColor Red
}

# 3. Check Database Connection
$dbCheck = & $phpPath -r "
try {
    new PDO('mysql:host=127.0.0.1;port=3307;dbname=appform_release_test', 'appform_local', 'local_appform_pwd_2026', [PDO::ATTR_TIMEOUT => 2]);
    echo 'OK';
} catch (Exception $ex) {
    echo 'ERR';
}
"
if ($dbCheck -eq "OK") {
    Write-Host "[OK] Database Connection Reachable" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Database Connection Offline: $dbCheck" -ForegroundColor Red
}

# 4. Check Web Response (HTTP 200)
try {
    $res = Invoke-WebRequest -Uri "http://127.0.0.1:8080/login" -UseBasicParsing -TimeoutSec 2 -ErrorAction Stop
    if ($res.StatusCode -eq 200) {
        Write-Host "[OK] Application Login Page returned HTTP 200" -ForegroundColor Green
    } else {
        Write-Host "[FAIL] Application Login Page returned HTTP $($res.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "[FAIL] Application is unreachable: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "==================================" -ForegroundColor Cyan
