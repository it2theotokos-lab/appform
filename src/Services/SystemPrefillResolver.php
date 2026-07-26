<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;

class SystemPrefillResolver {
    public static $supportedTags = [
        '{user_id}',
        '{user_display}',
        '{user_first_name}',
        '{user_last_name}',
        '{user_email}',
        '{employee_id}',
        '{user_department}',
        '{user_department_id}',
        '{user_role}',
        '{user_role_id}',
        '{user_manager}',
        '{user_manager_email}',
        '{current_date}',
        '{current_time}',
        '{current_datetime}',
        '{form_id}',
        '{form_title}',
        '{form_slug}'
    ];

    public static function isIdentityTag(string $tag): bool {
        return in_array($tag, [
            '{user_id}',
            '{user_display}',
            '{user_first_name}',
            '{user_last_name}',
            '{user_email}',
            '{employee_id}',
            '{user_department}',
            '{user_department_id}',
            '{user_role}',
            '{user_role_id}',
            '{user_manager}',
            '{user_manager_email}'
        ]);
    }

    public static function resolve(string $tag, array $form): ?string {
        $now = time();
        if ($tag === '{current_date}') {
            return date('Y-m-d', $now);
        }
        if ($tag === '{current_time}') {
            return date('H:i:s', $now);
        }
        if ($tag === '{current_datetime}') {
            return date('Y-m-d H:i:s', $now);
        }
        if ($tag === '{form_id}') {
            return (string)($form['id'] ?? '');
        }
        if ($tag === '{form_title}') {
            return $form['title'] ?? '';
        }
        if ($tag === '{form_slug}') {
            return $form['slug'] ?? '';
        }

        // Identity tags resolve to empty if anonymous form or user not authenticated
        if (!empty($form['is_anonymous']) || !Auth::check()) {
            return '';
        }

        $user = Auth::user();
        if (!$user) {
            return '';
        }

        if ($tag === '{user_id}') {
            return (string)$user['id'];
        }
        if ($tag === '{user_display}') {
            return $user['full_name'] ?: $user['username'];
        }
        if ($tag === '{user_first_name}') {
            $parts = explode(' ', trim($user['full_name'] ?? ''));
            return $parts[0] ?? '';
        }
        if ($tag === '{user_last_name}') {
            $parts = explode(' ', trim($user['full_name'] ?? ''));
            return isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
        }
        if ($tag === '{user_email}') {
            return $user['email'] ?? '';
        }
        if ($tag === '{employee_id}') {
            return $user['external_identifier'] ?? '';
        }
        if ($tag === '{user_department}') {
            return $user['directory_department'] ?? '';
        }
        if ($tag === '{user_department_id}') {
            return ''; // default / unavailable fallback
        }
        if ($tag === '{user_role}') {
            return $user['role_name'] ?? '';
        }
        if ($tag === '{user_role_id}') {
            return (string)($user['role_id'] ?? '');
        }

        // Fetch manager info if needed
        if ($tag === '{user_manager}' || $tag === '{user_manager_email}') {
            if (empty($user['manager_id'])) {
                return '';
            }
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT full_name, username, email FROM users WHERE id = ?");
            $stmt->execute([$user['manager_id']]);
            $mgr = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$mgr) {
                return '';
            }
            if ($tag === '{user_manager}') {
                return $mgr['full_name'] ?: $mgr['username'];
            }
            if ($tag === '{user_manager_email}') {
                return $mgr['email'] ?? '';
            }
        }

        return '';
    }
}
