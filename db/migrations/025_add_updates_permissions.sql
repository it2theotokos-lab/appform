-- Migration: Add Updates/Upgrade permissions and map them to the Administrator role
-- Rollback: DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE slug IN ('updates.view', 'updates.manage', 'updates.rollback')); DELETE FROM permissions WHERE slug IN ('updates.view', 'updates.manage', 'updates.rollback');

-- 1. Insert updates system permissions
INSERT INTO permissions (name, slug, description) VALUES
('View Updates', 'updates.view', 'Δυνατότητα προβολής της καρτέλας αναβαθμίσεων'),
('Manage Updates', 'updates.manage', 'Δυνατότητα εκτέλεσης αναβαθμίσεων συστήματος'),
('Rollback Updates', 'updates.rollback', 'Δυνατότητα εκτέλεσης rollback σε προηγούμενη έκδοση')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Assign permissions to Administrator role dynamically by slug (prevents foreign key constraint failure during clean install)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM permissions p
CROSS JOIN roles r
WHERE r.slug = 'administrator'
  AND p.slug IN ('updates.view', 'updates.manage', 'updates.rollback')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);
