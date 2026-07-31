# AppForm Local Testing Credentials & Protocol

## MANDATORY LOCAL TEST CREDENTIALS

For all local browser, HTTP, E2E, and integration tests on `http://127.0.0.1:8080`, the ONLY authorized admin credentials are:

- **Username:** `admin`
- **Email:** `admin@appform.local`
- **Password:** `ChangeMe!2025`

---

## STRICT RULES FOR TESTING & AGENTS

1. **NO ALTERNATIVE CREDENTIALS:**
   Never search for, guess, or use alternative passwords (such as `Admin123`, `Admin_Clean_2026!`, etc.) or arbitrary test accounts unless explicitly authorized by the user.

2. **AUTHENTICATION FAILURE PROTOCOL:**
   If login fails using `admin` / `ChangeMe!2025`:
   - **STOP IMMEDIATELY.**
   - Report the exact HTTP status code, URL, and relevant error log.
   - **DO NOT** attempt to reset passwords, modify user tables, re-seed database data, or alter hashes without explicit user approval.

3. **ISOLATION FROM PRODUCTION & RELEASE:**
   This documentation and rule applies strictly to local development and test execution. It MUST NOT be included in release archives or production build outputs.
