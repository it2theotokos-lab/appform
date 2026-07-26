# Windows Background Job Queue Deployment Guide

## 1. Production Scheduled Tasks
Deploy the job queue workers via the central CLI entry point:
*   **Default Queue Worker**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" queue:work --queue=default
    ```
*   **Email Queue Worker**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" queue:work --queue=email
    ```
*   **PDF/Reports Queue Worker**:
    ```cmd
    "C:\Antigravity-PRJ\Tools\PHP\php.exe" "C:\inetpub\wwwroot\betaappform\bin\console" queue:work --queue=pdf,reports
    ```

## 2. Recommended Production Schedule
*   **Default Worker**: every 2 minutes.
*   **Email Worker**: every 1 minute.
*   **PDF/Reports Worker**: every 5 minutes.
*   **Queue Cleanup**: daily.

## 3. Dedicated Service Account
Run tasks under `DOMAIN\AppFormService` local system service accounts. Enforce `IgnoreNew` overlap policy parameters.
