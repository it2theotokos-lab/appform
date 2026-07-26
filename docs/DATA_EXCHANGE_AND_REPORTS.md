# Data Exchange & Reports Module (AppForm v1.1.0)

## Overview
The **Data Exchange & Reports** (`/admin/data-exchange`) module provides a central dashboard for executing bulk imports and exports, audit history logs tracking, and generating PDF/Excel reports of survey submission results with support for Greek character formatting.

---

## Features

### 1. Data Export
- **Formats Supported**: CSV (with UTF-8 BOM protection against Excel formula injection), XLSX (Excel), JSON Schemas.
- **Entities Supported**: Users, Roles, Permissions, Repositories, Forms, Versions, Submissions, Files metadata, Workflow Steps/Instances/Comments, Notification settings, Menus, Settings, and Audit Logs.
- **Privacy Protections**: Passwords and sensitive tokens are encrypted or masked. Plain text user password hashes are excluded from user entity exports.

### 2. Data Import
- **Formats Supported**: CSV, JSON.
- **Duplicate Strategies**: Skip, Update Existing, Create Duplicate, or Stop Import.
- **Verification modes**: Integrated Dry Run simulation mode to dry-run validation without altering database records.

### 3. Survey Analytics & PDF Reporting
- Aggregates submissions of Form fields (Single choice, Multi choice, Number, Text) and generates responsive statistic cards and text answer appendices.
- Professional report generation with printable layouts (logo header, custom filters metadata, page counters, and full Greek language support).

---

## Permissions Configured
- `data_exchange.view` (View Panel)
- `data_exchange.export` (Perform Exports)
- `data_exchange.import` (Perform Imports)
- `data_exchange.reports` (Generate Analytics PDF Reports)
- `data_exchange.manage` (Manage Templates)
