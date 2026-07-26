param (
    [string]$PhpPath = "C:\Antigravity-PRJ\Tools\PHP\php.exe",
    [string]$AppPath = "C:\inetpub\wwwroot\betaappform"
)

Write-Output "Installing AppForm Job Queue Tasks..."

$Action = New-ScheduledTaskAction -Execute $PhpPath -Argument """$AppPath\bin\console"" queue:work --queue=default,email,pdf" -WorkingDirectory $AppPath
$Trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 2) -RepetitionDuration ([TimeSpan]::MaxValue)
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew

Register-ScheduledTask -TaskName "AppForm_Job_Queue_Worker" -Action $Action -Trigger $Trigger -Settings $Settings -User "NT AUTHORITY\SYSTEM" -Force

Write-Output "Task AppForm_Job_Queue_Worker registered successfully with IgnoreNew overlap policy."
