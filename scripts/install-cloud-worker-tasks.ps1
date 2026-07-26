param (
    [string]$PhpPath = "C:\Antigravity-PRJ\Tools\PHP\php.exe",
    [string]$AppPath = "C:\inetpub\wwwroot\betaappform"
)

Write-Output "Verifying environment prerequisites..."

if (-not (Test-Path $PhpPath)) {
    Write-Error "PHP executable not found at: $PhpPath"
    exit 1
}

if (-not (Test-Path "$AppPath\bin\console")) {
    Write-Error "bin/console CLI entry point not found at: $AppPath\bin\console"
    exit 1
}

# Create log directories if missing
$LogDir = "$AppPath\storage\logs"
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

Write-Output "Installing AppForm Cloud Worker Tasks..."

$Action = New-ScheduledTaskAction -Execute $PhpPath -Argument """$AppPath\bin\console"" cloud:work --max-jobs=5" -WorkingDirectory $AppPath
$Trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 2) -RepetitionDuration ([TimeSpan]::MaxValue)

# Overlap Policy: IgnoreNew
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew

Register-ScheduledTask -TaskName "AppForm_Cloud_Worker" -Action $Action -Trigger $Trigger -Settings $Settings -User "NT AUTHORITY\SYSTEM" -Force

Write-Output "Task AppForm_Cloud_Worker registered successfully with IgnoreNew overlap policy."
