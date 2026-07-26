# Change Log

All notable changes to **AppForm** will be documented in this file.

## [1.1.3-Stable] - 2026-07-26

### Fixed
- **Settings Navigation Layout**: Refactored settings views to use a unified navigation partial component with flexbox wrapper to prevent tab items misalignment across different resolutions.
- **Cloud Backup (OAuth Integration)**: Replaced mock connection states with real Client OAuth redirection and callback verification, implementing client credentials configuration and identity retrieval.
- **Update Mechanism on Windows Server**: Corrected FastCGI PHP binary path resolution, allowing updates to run via CLI on IIS. Shifted updater and diagnostic files to `public/storage/`.

## [1.1.2-Stable] - 2026-07-26

### Fixed
- **Installer Storage Path Check**: Refactored the installer system requirements logic to perform robust write-tests on both the root `storage/` and public `public/storage/` directories, preventing false unwritable reports under Windows/IIS.
- **Clean Package Exclusions**: Refactored the release builder script to completely exclude runtime data (logs, signatures, PDFs) from both `storage/` and `public/storage/` directories, preserving structure with `.gitkeep` placeholders.

## [1.1.1-Stable] - 2026-07-26 (Withdrawn/Invalid)

### Fixed
- **Permissions Migration 025**: Refactored `role_permissions` mapping to resolve Administrator role dynamically by slug, preventing clean-install foreign key constraint failures.
- **Update Package Manifest Builder**: Reprogrammed release builder script to dynamically compile all modified/added production assets using Git diff comparison, correcting package omission bugs.

## [1.1.0-Stable] - 2026-07-26 (Withdrawn/Invalid)

### Added
- **Update & Upgrade Engine**: Integrated 25-step update execution pipeline with database dumps and automatic rollbacks.
- **Detached Background CLI Execution**: Enabled Windows/IIS compatible background execution worker.
- **Database-Independent Maintenance Override**: Enabled local file offline maintenance locks.
- **Updates UI Settings Tab**: Added real-time polling panel to monitor installer status and download diagnostic log files.

## [1.0.0-Stable] - 2026-07-20

### Added
- **Wide Form Designer Workspace**: Added full-width 3-column builder screen.
- **Reporting Hierarchy & Tree View**: Added manager assignments, loops validations, and interactive org hierarchy tree.
- **LDAP / Active Directory Integration**: Connected config views, dual login fallback and auto provisioning syncs.
- **System Health Dashboard**: Real-time status checks on DB, job queue, LDAP, SMTP configurations.
