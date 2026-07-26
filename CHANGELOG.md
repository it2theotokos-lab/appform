# Change Log

All notable changes to **AppForm** will be documented in this file.

## [1.1.1-Stable] - 2026-07-26

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
