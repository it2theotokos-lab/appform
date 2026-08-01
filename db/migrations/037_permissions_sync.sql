-- AppForm Migration 037: Permissions Sync — ensure ALL system permissions exist
-- Version: v1.1.27
-- Description: Idempotent INSERT IGNORE for the 26 permissions that were present in
--              the local database but absent from config/permissions.php and thus never
--              seeded in beta/production instances via Online Update.
--              Safe to re-run: ON DUPLICATE KEY UPDATE is a no-op.
--
-- Affected permission groups:
--   forms.manage, forms.assign
--   submissions.create, submissions.edit.own, submissions.edit.any,
--   submissions.delete, submissions.delete.own, submissions.delete.any,
--   submissions.download.own, submissions.approve, submissions.reject,
--   submissions.return_to_draft, submissions.return_for_correction,
--   submissions.export, submissions.view_history, submissions.view.subordinates
--   settings.view
--   updates.view, updates.manage, updates.rollback
--   audit.view
--   data_exchange.view, data_exchange.export, data_exchange.import,
--   data_exchange.reports, data_exchange.manage

INSERT INTO permissions (name, slug, description) VALUES
  ('Διαχείριση Στοιχείων Φορμών',       'forms.manage',                    'Διαχείριση Στοιχείων Φορμών'),
  ('Ανάθεση Φόρμας σε Ρόλους',          'forms.assign',                    'Ανάθεση Φόρμας σε Ρόλους'),
  ('Δημιουργία Υποβολής',               'submissions.create',               'Δημιουργία Υποβολής'),
  ('Επεξεργασία Δικών μου Υποβολών',    'submissions.edit.own',             'Επεξεργασία Δικών μου Υποβολών'),
  ('Επεξεργασία Οποιασδήποτε Υποβολής', 'submissions.edit.any',             'Επεξεργασία Οποιασδήποτε Υποβολής'),
  ('Διαγραφή Υποβολών',                 'submissions.delete',               'Διαγραφή Υποβολών'),
  ('Διαγραφή Δικών μου Υποβολών',       'submissions.delete.own',           'Διαγραφή Δικών μου Υποβολών'),
  ('Διαγραφή Οποιασδήποτε Υποβολής',   'submissions.delete.any',           'Διαγραφή Οποιασδήποτε Υποβολής'),
  ('Λήψη Αρχείων Υποβολής',            'submissions.download.own',         'Λήψη Αρχείων Υποβολής'),
  ('Έγκριση Υποβολής',                 'submissions.approve',              'Έγκριση Υποβολής'),
  ('Απόρριψη Υποβολής',                'submissions.reject',               'Απόρριψη Υποβολής'),
  ('Επιστροφή Υποβολής σε Πρόχειρο',   'submissions.return_to_draft',      'Επιστροφή Υποβολής σε Πρόχειρο'),
  ('Επιστροφή Υποβολής για Διορθώσεις','submissions.return_for_correction', 'Επιστροφή Υποβολής για Διορθώσεις'),
  ('Εξαγωγή Υποβολών',                 'submissions.export',               'Εξαγωγή Υποβολών'),
  ('Προβολή Ιστορικού Υποβολής',       'submissions.view_history',         'Προβολή Ιστορικού Υποβολής'),
  ('Προβολή Υποβολών Υφισταμένων',     'submissions.view.subordinates',    'Προβολή Υποβολών Υφισταμένων'),
  ('Προβολή Ρυθμίσεων Συστήματος',     'settings.view',                    'Προβολή Ρυθμίσεων Συστήματος'),
  ('Προβολή Αναβαθμίσεων',             'updates.view',                     'Δυνατότητα προβολής της καρτέλας αναβαθμίσεων'),
  ('Διαχείριση Αναβαθμίσεων',          'updates.manage',                   'Δυνατότητα εκτέλεσης αναβαθμίσεων συστήματος'),
  ('Επαναφορά Αναβαθμίσεων',           'updates.rollback',                 'Δυνατότητα εκτέλεσης rollback σε προηγούμενη έκδοση'),
  ('Προβολή Καταγραφών Ελέγχου (Audit Logs)', 'audit.view',               'Επιτρέπει την προβολή των audit logs'),
  ('Προβολή Εισαγωγών/Εξαγωγών',       'data_exchange.view',               'Προβολή Εισαγωγών/Εξαγωγών'),
  ('Εξαγωγή Δεδομένων',               'data_exchange.export',             'Εξαγωγή Δεδομένων'),
  ('Εισαγωγή Δεδομένων',              'data_exchange.import',             'Εισαγωγή Δεδομένων'),
  ('Αναφορές PDF',                     'data_exchange.reports',            'Αναφορές PDF'),
  ('Διαχείριση Data Exchange',         'data_exchange.manage',             'Διαχείριση Data Exchange')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Assign ALL permissions to the Administrator role (idempotent)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM permissions p
CROSS JOIN roles r
WHERE r.slug = 'administrator'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);
