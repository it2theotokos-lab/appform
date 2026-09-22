# Change Log

All notable changes to **AppForm** will be documented in this file.

## [1.1.29-Stable] - 2026-08-03

### Fixed
- **Submission Access Permissions Toggle**: Fixed Bootstrap `d-none` CSS override issue and isolated toggle script into a standalone IIFE in `forms/edit.php`, ensuring Selected Roles and Selected Users sections display correctly when selected.


### Added
- **Global Notifications — Rule Builder UI**: The Global Notifications editor (`Administration → Settings → Global Notifications`) now includes a full interactive Rule Builder, consistent with the form-level Notification Rules already in the application. Admins can add/remove rules, choose context variables (`user_name`, `user_email`, `user_role`, `site_name`), select operators (`equals`, `not_equals`, `contains`, `is_empty`, etc.), and set match mode (`All` / `Any`).
- **Global Notifications — Execution Rules Engine (`triggerGlobal`)**: Added `FormNotificationTriggerService::triggerGlobal(string $slug, string $toEmail, array $context)`. Reuses the existing `evaluateConditions()` engine — no second rules system introduced. Fetches the template by slug, evaluates `conditional_logic_json` rules against the context, replaces Smart Tags (`{user_name}`, `{user_email}`, `{user_role}`, `{site_name}`, `{site_url}`, `{action_url}`), and fires the email via `EmailService::sendEmail()`.
- **`user_registered` Event Hook**: `UserController::store()` now fires `triggerGlobal('user_registered', …)` immediately after a new user is created. Context includes `user_name`, `user_email`, `user_role`, `site_name`, `site_url`, and `action_url`. Trigger is fully non-blocking.
- **HTML Preview Modal**: The Global Notifications editor retains its iframe-based safe HTML preview (sandbox) for the HTML body field.

### Fixed
- **Global Notifications JS**: The JavaScript block was missing the complete Rule Builder logic (`renderGlobalRules`, `addGlobalRuleItem`, remove handler, `conditional_logic_json` loader on edit). All are now implemented.
- **Preview Modal**: The `globalPreviewModal` HTML structure was accidentally omitted; restored.

## [1.1.23-Stable] - 2026-07-31

### Added
- **Organizational Structure**: Introduced 2-tier organizational unit hierarchy (`Department` → `Sub-department` / `Team`).
- **Migration `034_org_structure_and_extra_user_details.sql`**: Added `org_units` table with strict parent constraints and `org_unit_id`, `personal_email`, `corporate_phone`, `mobile_phone`, `internal_phone` columns in `users`.
- **Org Structure Tree View**: New tree view (`/admin/users/organization`) with quick-action creation modals for Departments, Sub-departments, and Teams.
- **User Management & Profile Tabs**: Tabbed interface in User Edit page (Basic Info, Organizational Placement, Extra Contact Details). Integrated "Create Team" action and Org Unit column in main User Management table (`/admin/users`).
- **Tests**: Created `OrgStructureTest.php` (8 automated tests covering unit creation, hierarchy validation, deletion guards, user assignment, and extra contact details storage).

## [1.1.22-Stable] - 2026-07-31

### Fixed
- **EL/EN Localization Polish**: Corrected `Lang::get()` dictionary lookup logic for both `el` and `en` locales. Translated all global modals (Confirm, Signature, Help Modal), search input placeholders, and close buttons. Localized contextual help templates (`dashboard.php`, `settings.php`, `users.php`) and added missing workflow status labels (`submitted`, `returned`, etc.) to the dictionary.
- **Favicon Path Depth & Full Lifecycle Verification**: Fixed incorrect path resolution depth (`dirname(__DIR__, 2)` → `dirname(__DIR__, 3)`) across all layout templates (`app.php`, `guest.php`, `focus.php`, `public.php`). Verified custom favicon upload, persistence, hard-refresh rendering, fallback to default `/assets/img/favicon.ico` on deletion, and confirmed zero impact on Application Logo.

## [1.1.21-Stable] - 2026-07-31

### Fixed
- **BUG-4 Corrective Release (Clean Install)**: This release supersedes v1.1.20 for new installations. Migration `032_app_logo.sql` used non-existent column names (`` `key` ``, `` `value` ``, `` `description` ``). Corrected to `setting_key`, `setting_value` per the canonical `system_settings` schema (`004_phase4.sql`). Same column-name fixes applied to `SettingsController::uploadLogo()` (2 queries), `SettingsController::deleteLogo()` (2 queries), and `sidebar.php` (1 query).

### Tests
- `MigrationTest_032.php` (25 tests — A through I): migration SQL syntax, canonical column names, INSERT IGNORE idempotency, upgrade-from-v1.1.19 safety, controller/sidebar column correctness, no legacy refs.
- `LogoUploadTest.php` (15 tests), `LanguageTest.php` (9 tests), `SettingsNavTest.php` (8 tests) — all pass.

## [1.1.20-Stable] - 2026-07-31


### Added
- **Language Selector (EL/EN)**: New `Lang` service with 200+ translation keys. Global `__()` helper registered at bootstrap. Language toggle (ΕΛ / EN) buttons added to the header. Session-persisted locale preference. All sidebar section titles, settings tabs, header dropdown items, footer, and UI labels are now translatable.
- **Custom Application Logo Upload**: Upload a custom logo (JPG, PNG, WEBP · max 2 MB) via Settings → General. The sidebar renders the custom logo when set and falls back to the default icon+text. Server-side MIME validation via `getimagesize()`. One-click deletion/reset to default. Logo files are excluded from update ZIPs (`public/storage/logos/`).
- **Migration `032_app_logo.sql`**: Inserts the `app_logo_path` key into `system_settings`.
- **Tests**: `LanguageTest.php` (9 tests), `LogoUploadTest.php` (12 tests), `SettingsNavTest.php` (8 tests) — all pass.

### Fixed
- **BUG-4 — Migration column mismatch (SQLSTATE[42S22])**: `032_app_logo.sql` used non-existent columns `` `key` ``, `` `value` ``, `` `description` `` instead of `setting_key`, `setting_value` (canonical schema from `004_phase4.sql`). The same wrong column names were present in `SettingsController::uploadLogo()` (2 queries), `SettingsController::deleteLogo()` (2 queries), and `sidebar.php` (1 query). All corrected. Added 7 regression tests (T1c/T1d/T1e, T8c/T8d, T13/T14/T15) to `LogoUploadTest.php`.

- **Settings Navigation Cleanup**: Removed the **Demo Data**, **Active Directory / LDAP**, and **Πρόσθετα (Plugins)** tabs from the settings navigation. All remaining tab labels translated with `__()`.

## [1.1.19-Stable] - 2026-07-30

### Fixed
- **Manual/Local Update State Transition (BUG-3)**: The `localUpdate()` controller was prematurely transitioning the update record to `waiting_for_lock` before launching the background worker. The worker then attempted the same `waiting_for_lock → waiting_for_lock` transition, which is explicitly invalid in the state machine, causing a crash immediately after a successful upload and SHA-256 verification. The controller now leaves the record in `pending` and only appends a diagnostic log entry. The worker (`UpdateEngineService::runUpdate()`) is now the sole owner of the `waiting_for_lock` transition, ensuring exactly one `pending → waiting_for_lock` transition per update cycle.
- **Worker Pre-flight for Local Uploads**: `UpdateEngineService::runUpdate()` now correctly skips the GitHub network check when the provider is `local_upload`, consistent with the controller's `runPreFlightChecks(skipNetworkCheck=true)` call.

## [1.1.14-Stable] - 2026-07-29

### Added
- **Structured Repositories**: Repositories now support dynamic JSON columns definition (`columns_json`) in addition to default `value` and `label`, enabling multi-column configuration.
- **Repository Builder UI**: Replaced existing Repository Form with a structured Columns Manager and Data Grid editor.
- **Form Builder Fields**:
  - Extracted Auto-complete properties into Dynamic Mappings configuration, allowing users to map repository column values into different fields upon selection.
  - Enhanced Multi-Select Repository Tags UI to search from multiple customizable columns and multiple repos.
- **Database**: Added migration `031_repository_columns.sql` to support new JSON mappings.

## [1.1.13-Stable] - 2026-07-29

### Fixed
- **Worker Detach**: Removed blocking `proc_close` in `ProcessRunner` fallback, allowing the FastCGI process to terminate cleanly and avoiding HTTP timeout errors during updates.
- **Update Error Handling**: Enhanced UI diagnostics and fetch error handling for Local Update (e.g. CSRF invalidation, invalid ZIP pre-flight failures) to properly report errors instead of a generic "Network error".

## [1.1.7-Stable] - 2026-07-28

### Fixed
- **Production Update Package Download**: Integrated active `ReleaseProvider` and `downloadAsset()` in update startup sequence to resolve missing package ZIP on production environments before background worker dispatch.
- **Update Verification**: Enforced package existence, positive size validation, and SHA-256 integrity verification before starting update workflow, with automatic rollbacks and cleanups.

## [1.1.6-Stable] - 2026-07-28

### Added
- **Cloud Backup Fixes**: Integrated with the central Enterprise Job Queue for isolated upload tasks.
- **Stale Sync Lock Handling**: Added timeouts and worker heartbeats to detect and clear stale replication locks.
- **Resumable Chunks**: Real cURL-based resumable uploads to Google Drive with folder resolution and integrity checks (MD5/SHA-256).
- **Database Migrations**: Applied safe migrations 027 and 028 for schema updates.

## [1.1.5-Stable] - 2026-07-27

### Fixed
- **Pre-flight Checks**: Added checks for GitHub Release API, cURL/SSL, PHP ZipArchive, and write permissions before updating.
- **mysqldump Execution**: Added support for custom mysqldump executable path with fallback to Windows PATH.
- **Backup Validations**: Added validation to ensure database and file backups are created successfully and are valid.
- **Rollback State Transitions**: Allowed rollback transitions from failed states to prevent state machine transition errors.
- **Force Unlock**: Implemented robust unlock mechanism resetting update_status.json and setting maintenance_mode to 0.

## [1.1.4-Stable] - 2026-07-26

### Fixed
- **Cloud Backup UI Restoration**: Restored the Cloud Backup tab rendering by fixing the activeTab conditional wrapper block and aligning controller variable names.
- **OAuth Settings Completion**: Standardized client secrets storage, masking passwords in HTML settings inputs, and preserving existing secret settings values when inputs are submitted blank.
- **OAuth Redirection and Connection Flow**: Enabled support for dynamic Microsoft OneDrive tenant IDs, enforced user profile verification on OAuth callbacks, and implemented real connection tests for Google Drive and OneDrive API endpoints.

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
## [1.1.46] - 2026-09-22

### Fixed
- Aligned submission status storage with all reviewing workflow transitions, including return for corrections and cancellation.
- Allowed users with `submissions.review` permission to use the existing return-to-draft review action.
- Displayed the responsible user for every submission status-history entry in both reviewer and submitter views.
