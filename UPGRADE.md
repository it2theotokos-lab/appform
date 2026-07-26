# Upgrade Guide

## Upgrading from v0.9.x to v1.0.0 Stable

1. Backup your current files and database.
2. Replace all core files from the `src/` directory.
3. Execute schema migrations in chronological order:
   - `db/migrations/020_ldap_auth.sql`
   - `db/migrations/021_user_hierarchy.sql`
4. Clear cache files inside `temp/cache/` directory.
5. Re-run tests to confirm system health.
