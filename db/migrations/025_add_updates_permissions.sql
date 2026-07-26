-- Migration: Add Updates/Upgrade permissions and map them to the Administrator role
-- Rollback: DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE slug IN ('updates.view', 'updates.manage', 'updates.rollback')); DELETE FROM permissions WHERE slug IN ('updates.view', 'updates.manage', 'updates.rollback');

-- 1. Insert permissions
INSERT INTO permissions (name, slug, description) VALUES
('Προβολή Αναβαθμίσεων', 'updates.view', 'Επιτρέπει την προβολή διαθέσιμων αναβαθμίσεων συστήματος'),
('Διαχείριση Αναβαθμίσεων', 'updates.manage', 'Επιτρέπει την εκτέλεση αναβαθμίσεων συστήματος'),
('Επαναφορά Αναβαθμίσεων', 'updates.rollback', 'Επιτρέπει την επαναφορά συστήματος σε προηγούμενη έκδοση')
ON DUPLICATE KEY UPDATE slug = VALUES(slug);

-- 2. Map permissions to Administrator role (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE slug IN ('updates.view', 'updates.manage', 'updates.rollback')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);
