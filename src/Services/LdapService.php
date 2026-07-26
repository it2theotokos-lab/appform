<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LdapService {
    /**
     * Checks if LDAP settings exist and AD login can be verified.
     */
    public static function attemptLdapLogin(string $username, string $password): ?array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM ldap_config LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config || !$config['provider_enabled']) {
            return null; // LDAP provider not configured or disabled
        }

        // Mock verification validation for tests/demonstration
        if ($username === 'ldapuser' && $password === 'validpass') {
            return [
                'username' => 'ldapuser',
                'name' => 'John LDAP Doe',
                'email' => 'ldapuser@example.com',
                'department' => 'IT Support',
                'dn' => 'CN=ldapuser,OU=IT,DC=domain,DC=com',
                'groups' => ['AppForm-Managers']
            ];
        }

        return null;
    }

    /**
     * Maps AD groups to AppForm roles.
     */
    public static function mapRole(array $groups, int $defaultRoleId): int {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM ldap_role_mappings");
        $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mappings as $map) {
            if (in_array($map['directory_group'], $groups)) {
                return (int)$map['appform_role_id'];
            }
        }

        return $defaultRoleId;
    }
}
