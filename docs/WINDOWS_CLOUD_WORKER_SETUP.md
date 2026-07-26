# Windows Task Scheduler Deployment Guide

## 1. Production Worker Commands
Run these commands using the central CLI entry point:
*   **Queue Worker**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" cloud:work --max-jobs=5
    ```
*   **Retry Processor**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" cloud:retry
    ```
*   **Verification Worker**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" cloud:verify
    ```
*   **Cleanup Worker**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" cloud:cleanup
    ```

## 2. Task Overlap Policy
*   Configure the overlap policy to: **Do not start a new instance if the task is already running**.
*   For PowerShell task settings, set the policy to: `MultipleInstances = IgnoreNew`.
