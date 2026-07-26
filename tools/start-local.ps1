# AppForm Local Environment Startup Script

$mariadbPath = "C:\Antigravity-PRJ\Tools\MariaDB\bin\mariadbd.exe"
$myIni = "C:\Antigravity-PRJ\Tools\MariaDB\my.ini"
$phpPath = "C:\Antigravity-PRJ\Tools\PHP\php.exe"
$appPath = "C:\Antigravity-PRJ\Projects\Appform"

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "AppForm Startup Initializing..." -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan

# 1. Start MariaDB on 3307 if not already listening
$port3307 = Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue
if ($port3307) {
    Write-Host "MariaDB: Already running on 3307." -ForegroundColor Green
} else {
    Write-Host "MariaDB: Starting service on 3307..." -ForegroundColor Yellow
    Start-Process -FilePath $mariadbPath -ArgumentList "--defaults-file=$myIni" -WindowStyle Hidden
    
    # Wait for TCP 3307
    $retries = 10
    do {
        Start-Sleep -Seconds 1
        $port3307 = Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue
        $retries--
    } while (-not $port3307 -and $retries -gt 0)

    if ($port3307) {
        Write-Host "MariaDB: Successfully started." -ForegroundColor Green
    } else {
        Write-Error "MariaDB: Failed to bind to port 3307."
        exit 1
    }
}

# 2. Verify DB Connection via PHP
Write-Host "Verifying database connection..."
$dbCheck = & $phpPath -r "
try {
    new PDO('mysql:host=127.0.0.1;port=3307;dbname=appform_release_test', 'appform_local', 'local_appform_pwd_2026', [PDO::ATTR_TIMEOUT => 2]);
    echo 'OK';
} catch (Exception $ex) {
    echo 'ERR';
}
"
if ($dbCheck -eq "OK") {
    Write-Host "Database Connection: Verified." -ForegroundColor Green
} else {
    Write-Warning "Database Connection Failed: $dbCheck"
}

# 3. Start PHP Dev Server on 8080 if not running
$port8080 = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue
if ($port8080) {
    Write-Host "PHP Server: Already running on 8080." -ForegroundColor Green
} else {
    Write-Host "PHP Server: Starting on 127.0.0.1:8080..." -ForegroundColor Yellow
    Start-Process -FilePath $phpPath -ArgumentList "-S 127.0.0.1:8080 -t public" -WorkingDirectory $appPath -WindowStyle Hidden
    
    # Wait for HTTP 8080
    $retries = 10
    $httpOk = $false
    do {
        Start-Sleep -Seconds 1
        try {
            $req = Invoke-WebRequest -Uri "http://127.0.0.1:8080/login" -UseBasicParsing -TimeoutSec 2 -ErrorAction SilentlyContinue
            if ($req.StatusCode -eq 200) { $httpOk = $true }
        } catch {}
        $retries--
    } while (-not $httpOk -and $retries -gt 0)

    if ($httpOk) {
        Write-Host "PHP Server: Successfully started." -ForegroundColor Green
    } else {
        Write-Error "PHP Server: Failed to respond on port 8080."
        exit 1
    }
}

# 4. Open Default Browser
Start-Process "http://127.0.0.1:8080"

# 5. Display Status Report
Write-Host "`n==================================" -ForegroundColor Green
Write-Host "AppForm Local Environment Ready" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Green
Write-Host "MariaDB : Running" -ForegroundColor Green
Write-Host "PHP     : Running" -ForegroundColor Green
Write-Host "URL     : http://127.0.0.1:8080" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Green
