# AppForm Local Development Tools

This directory contains scripts to easily start, stop, restart, and monitor the local AppForm development environment.

---

## Usage

### 1. Start Environment
To start MariaDB on port 3307 and the PHP development server on port 8080:
```cmd
tools\start-local.bat
```
or via PowerShell:
```powershell
.\tools\start-local.ps1
```

### 2. Stop Environment
To stop the database and PHP server instances started by these tools:
```cmd
tools\stop-local.bat
```

### 3. Restart Environment
To restart both services and verify connection:
```cmd
tools\restart-local.bat
```

### 4. Health Check
To check the current status of the services and application:
```powershell
.\tools\health-check.ps1
```
