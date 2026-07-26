<?php
namespace App\Core;

use PDO;

class Auth {
    private static $currentUser = null;

    public static function check(): bool {
        return Session::has('user_id');
    }

    public static function id() {
        return Session::get('user_id');
    }

    public static function user() {
        if (self::$currentUser === null && self::check()) {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role_name, r.slug as role_slug 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ? AND u.is_active = 1
            ");
            $stmt->execute([self::id()]);
            self::$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return self::$currentUser;
    }

    public static function role(): ?string {
        $user = self::user();
        return $user ? $user['role_slug'] : null;
    }

    public static function hasPermission(string $permissionSlug): bool {
        $user = self::user();
        if (!$user) {
            return false;
        }

        // Administrator bypasses all permission checks
        if ($user['role_slug'] === 'administrator') {
            return true;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
        ");
        $stmt->execute([$user['role_id'], $permissionSlug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function attempt(string $username, string $password): bool {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback AD LDAP Authentication Check
        // Guard: authentication_provider column may not exist on first boot (migration not yet applied)
        $authProvider = $user['authentication_provider'] ?? 'local';
        if (!$user || $authProvider === 'ldap') {
            $ldapUser = \App\Services\LdapService::attemptLdapLogin($username, $password);
            if ($ldapUser) {
                // Determine Default Role ID
                $stmtC = $db->query("SELECT default_role_id FROM ldap_config LIMIT 1");
                $defaultRoleId = (int)$stmtC->fetchColumn() ?: 2; // Default User Role ID

                $roleId = \App\Services\LdapService::mapRole($ldapUser['groups'], $defaultRoleId);

                if (!$user) {
                    // Auto-provision local user account
                    $stmtIns = $db->prepare("
                        INSERT INTO users (username, full_name, email, role_id, authentication_provider, external_identifier, directory_dn, directory_department, last_login_at, password_hash)
                        VALUES (?, ?, ?, ?, 'ldap', ?, ?, ?, CURRENT_TIMESTAMP, '')
                    ");
                    $stmtIns->execute([
                        $ldapUser['username'],
                        $ldapUser['name'],
                        $ldapUser['email'],
                        $roleId,
                        $ldapUser['username'],
                        $ldapUser['dn'],
                        $ldapUser['department']
                    ]);
                    $userId = $db->lastInsertId();

                    // Re-load newly created user record
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    // Update/Sync user info
                    $stmtUpd = $db->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, role_id = ?, directory_dn = ?, directory_department = ?, last_login_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmtUpd->execute([
                        $ldapUser['name'],
                        $ldapUser['email'],
                        $roleId,
                        $ldapUser['dn'],
                        $ldapUser['department'],
                        $user['id']
                    ]);
                }
            }
        }

        if (!$user) {
            return false;
        }

        if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            Session::flash('error', 'Ο λογαριασμός είναι ανενεργός.');
            return false;
        }

        // Check account lockout
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            Session::flash('error', 'Ο λογαριασμός είναι κλειδωμένος λόγω αποτυχημένων προσπαθειών. Δοκιμάστε ξανά αργότερα.');
            return false;
        }

        // Verify maintenance mode lockout for non-administrator accounts
        // Guard: system_settings table may not exist yet on first boot
        $isMaint = false;
        try {
            $stmtM = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
            $stmtM->execute();
            $isMaint = (int)$stmtM->fetchColumn() === 1;
        } catch (\Exception $e) {
            // system_settings not yet installed — skip maintenance check
        }
        if ($isMaint) {
            // Fetch user role details
            $stmtR = $db->prepare("SELECT slug FROM roles WHERE id = ?");
            $stmtR->execute([$user['role_id']]);
            $roleSlug = $stmtR->fetchColumn();
            if ($roleSlug !== 'administrator') {
                Session::flash('error', 'Η εφαρμογή βρίσκεται προσωρινά σε λειτουργία συντήρησης. Μόνο οι διαχειριστές επιτρέπεται να συνδεθούν.');
                return false;
            }
        }

        if (($user['authentication_provider'] ?? 'local') !== 'ldap' && !password_verify($password, $user['password_hash'])) {
            // Increment failed attempts
            $attempts = $user['failed_login_attempts'] + 1;
            if ($attempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 900); // Lock for 15 mins
                $upd = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
                $upd->execute([$attempts, $lockedUntil, $user['id']]);
            } else {
                $upd = $db->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
                $upd->execute([$attempts, $user['id']]);
            }
            return false;
        }

        // Login successful - Reset lockouts
        $upd = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = CURRENT_TIMESTAMP WHERE id = ?");
        $upd->execute([$user['id']]);

        // Regenerate session to protect from session fixation
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('last_activity', time());

        // Audit Log
        self::logAudit($user['id'], 'login', 'users', $user['id'], ['username' => $user['username']]);

        return true;
    }

    public static function logout() {
        if (self::check()) {
            $userId = self::id();
            $username = Session::get('username');
            
            // Audit Log
            self::logAudit($userId, 'logout', 'users', $userId, ['username' => $username]);
            
            Session::destroy();
            self::$currentUser = null;
        }
    }

    private static function logAudit(int $userId, string $action, string $entityType, int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {
            // Ignore audit log write errors during login/logout to prevent app halts
        }
    }
}
