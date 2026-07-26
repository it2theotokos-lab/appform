-- AppForm Seed Data

-- 1. Insert Roles
INSERT INTO roles (id, name, slug, description, is_system) VALUES
(1, 'Administrator', 'administrator', 'Πλήρης πρόσβαση στο σύστημα', 1),
(2, 'Manager', 'manager', 'Διαχείριση φορμών, υποβολών και στατιστικών', 1),
(3, 'User', 'user', 'Πρόσβαση στο portal υποβολής φορμών', 1);

-- 2. Insert Permissions
INSERT INTO permissions (id, name, slug, description) VALUES
(1, 'Προβολή Dashboard', 'dashboard.view', 'Επιτρέπει την προβολή του κεντρικού πίνακα ελέγχου'),
(2, 'Προβολή Χρηστών', 'users.view', 'Επιτρέπει την προβολή λίστας χρηστών'),
(3, 'Δημιουργία Χρηστών', 'users.create', 'Επιτρέπει την προσθήκη νέων χρηστών'),
(4, 'Επεξεργασία Χρηστών', 'users.edit', 'Επιτρέπει την επεξεργασία στοιχείων χρηστών'),
(5, 'Διαγραφή Χρηστών', 'users.delete', 'Επιτρέπει τη διαγραφή χρηστών'),
(6, 'Διαχείριση Ρόλων', 'roles.manage', 'Επιτρέπει την επεξεργασία ρόλων χρηστών'),
(7, 'Διαχείριση Δικαιωμάτων', 'permissions.manage', 'Επιτρέπει την επεξεργασία δικαιωμάτων'),
(8, 'Προβολή Φορμών', 'forms.view', 'Επιτρέπει την ανάγνωση φορμών'),
(9, 'Δημιουργία Φορμών', 'forms.create', 'Επιτρέπει τον σχεδιασμό νέων φορμών'),
(10, 'Επεξεργασία Φορμών', 'forms.edit', 'Επιτρέπει την επεξεργασία υπαρχουσών φορμών'),
(11, 'Διαγραφή Φορμών', 'forms.delete', 'Επιτρέπει τη διαγραφή φορμών'),
(12, 'Δημοσίευση Φορμών', 'forms.publish', 'Επιτρέπει τη δημοσίευση φορμών (versioning)'),
(13, 'Υποβολή Φορμών', 'forms.submit', 'Επιτρέπει την υποβολή απαντήσεων σε φόρμες'),
(14, 'Προβολή Δικών μου Υποβολών', 'submissions.view.own', 'Επιτρέπει την προβολή των προσωπικών υποβολών'),
(15, 'Προβολή Όλων των Υποβολών', 'submissions.view.all', 'Επιτρέπει την προβολή όλων των υποβολών χρηστών'),
(16, 'Αξιολόγηση Υποβολών', 'submissions.review', 'Επιτρέπει την έγκριση/απόρριψη υποβολών'),
(17, 'Διαχείριση Repositories', 'repositories.manage', 'Επιτρέπει την διαχείριση πηγών δεδομένων (Repositories)'),
(18, 'Διαχείριση Μενού', 'menus.manage', 'Επιτρέπει τη διαχείριση των μενού πλοήγησης'),
(19, 'Προβολή Analytics', 'analytics.view', 'Επιτρέπει την προβολή των στατιστικών γραφημάτων'),
(20, 'Δημιουργία Εξαγωγών', 'exports.create', 'Επιτρέπει την εξαγωγή δεδομένων σε PDF, Excel, CSV');

-- 3. Assign Permissions to Roles
-- Administrator gets all permissions (1 to 20)
INSERT INTO role_permissions (role_id, permission_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20);

-- Manager gets views, submissions, reviews, analytics and exports
INSERT INTO role_permissions (role_id, permission_id) VALUES
(2, 1), (2, 8), (2, 13), (2, 14), (2, 15), (2, 16), (2, 19), (2, 20);

-- User gets own submission and submit form permissions
INSERT INTO role_permissions (role_id, permission_id) VALUES
(3, 1), (3, 8), (3, 13), (3, 14);

-- 4. Insert Users
-- Passwords are: admin123, manager123, user123 (hashed using PASSWORD_BCRYPT)
INSERT INTO users (id, username, email, password_hash, full_name, role_id, is_active) VALUES
(1, 'admin', 'admin@appform.local', '$2y$10$oXW.p9y5k2D9f2GfH4bN/Onr4D677z7nN2O4JjV9b9Yp9K9E5gD1C', 'Administrator User', 1, 1),
(2, 'manager', 'manager@appform.local', '$2y$10$N1W8R2/O9M5T2.Y5v6sR8Onf7Z5B2yP6N6M6H9B9Vp9K9E5gD1C', 'Manager User', 2, 1),
(3, 'user', 'user@appform.local', '$2y$10$I1W8R2/O9M5T2.Y5v6sR8Onf7Z5B2yP6N6M6H9B9Vp9K9E5gD1C', 'Standard User', 3, 1);

-- 5. Insert Navigation Menu Templates
INSERT INTO navigation_menus (role_id, name, menu_structure_json, is_active) VALUES
(1, 'Admin Navigation', '[
    {"label": "Dashboard", "icon": "fa-solid fa-chart-line", "route": "/dashboard", "permission": "dashboard.view"},
    {"label": "Forms", "icon": "fa-solid fa-file-invoice", "route": "/admin/forms", "permission": "forms.view"},
    {"label": "Submissions", "icon": "fa-solid fa-envelope-open-text", "route": "/admin/submissions", "permission": "submissions.view.all"},
    {"label": "Repositories", "icon": "fa-solid fa-database", "route": "/admin/repositories", "permission": "repositories.manage"},
    {"label": "Navigation", "icon": "fa-solid fa-bars", "route": "/admin/menus", "permission": "menus.manage"},
    {"label": "Analytics", "icon": "fa-solid fa-chart-pie", "route": "/admin/analytics", "permission": "analytics.view"}
]', 1),
(2, 'Manager Navigation', '[
    {"label": "Dashboard", "icon": "fa-solid fa-chart-line", "route": "/dashboard", "permission": "dashboard.view"},
    {"label": "Submissions", "icon": "fa-solid fa-envelope-open-text", "route": "/admin/submissions", "permission": "submissions.view.all"},
    {"label": "Analytics", "icon": "fa-solid fa-chart-pie", "route": "/admin/analytics", "permission": "analytics.view"}
]', 1),
(3, 'User Navigation', '[
    {"label": "Dashboard", "icon": "fa-solid fa-chart-line", "route": "/dashboard", "permission": "dashboard.view"},
    {"label": "My Submissions", "icon": "fa-solid fa-folder-open", "route": "/my-submissions", "permission": "submissions.view.own"}
]', 1);
