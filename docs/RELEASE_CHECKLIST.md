# AppForm Release Checklist

Pre-release tasks checklist for version 1.0.0:

1. [x] Autoloading: Integrate Composer PSR-4.
2. [x] PDF generation: Verify Dompdf output starts with `%PDF`.
3. [x] Excel generation: Verify PhpSpreadsheet XLSX valid zip layout.
4. [x] Database: Confirm schema and migration consistency.
5. [x] Hardening: Escape user inputs to prevent CSV injection.
6. [x] Release package: Exclude credentials, logs, and development files.
