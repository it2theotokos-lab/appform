<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class FormAccessService {
    public static function canUserViewForm(?int $userId, int $formId): bool {
        $db = Database::getInstance();

        // Load form details to check if is_public is active
        $stmtF = $db->prepare("SELECT * FROM forms WHERE id = ?");
        $stmtF->execute([$formId]);
        $form = $stmtF->fetch(PDO::FETCH_ASSOC);
        if ($form && !empty($form['is_public'])) {
            return true;
        }

        if (!$userId) return false;

        // Administrator has bypass access
        $userStmt = $db->prepare("SELECT r.slug, u.role_id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return false;
        if ($user['slug'] === 'administrator') return true;

        // Check user specific assignment first
        $stmtUser = $db->prepare("SELECT can_view FROM form_user_assignments WHERE form_id = ? AND user_id = ?");
        $stmtUser->execute([$formId, $userId]);
        $userAssign = $stmtUser->fetch();
        if ($userAssign !== false) {
            return (bool)$userAssign['can_view'];
        }

        // Check role specific assignment
        $stmtRole = $db->prepare("SELECT can_view FROM form_role_assignments WHERE form_id = ? AND role_id = ?");
        $stmtRole->execute([$formId, $user['role_id']]);
        $roleAssign = $stmtRole->fetch();
        if ($roleAssign !== false) {
            return (bool)$roleAssign['can_view'];
        }

        return false;
    }

    public static function canUserSubmitForm(?int $userId, int $formId): bool {
        $res = self::checkSubmissionAccess($userId, $formId);
        return $res['allowed'];
    }

    public static function checkSubmissionAccess(?int $userId, int $formId): array {
        $db = Database::getInstance();

        // 1. Fetch form
        $stmtF = $db->prepare("SELECT * FROM forms WHERE id = ?");
        $stmtF->execute([$formId]);
        $form = $stmtF->fetch(PDO::FETCH_ASSOC);

        if (!$form) {
            return ['allowed' => false, 'reason' => 'Η φόρμα δεν βρέθηκε.'];
        }

        // 2. Check active/published status using Central Availability Service
        $availStatus = FormAvailabilityService::checkAvailability($form, $userId);
        if ($availStatus !== 'available') {
            if ($availStatus === 'inactive') {
                return ['allowed' => false, 'reason' => 'Η φόρμα δεν είναι ενεργή.'];
            }
            if ($availStatus === 'not_started') {
                return ['allowed' => false, 'reason' => 'Η περίοδος υποβολών δεν έχει ξεκινήσει.'];
            }
            if ($availStatus === 'expired') {
                return ['allowed' => false, 'reason' => 'Η περίοδος υποβολών έχει λήξει.'];
            }
            if ($availStatus === 'capacity_reached') {
                return ['allowed' => false, 'reason' => 'Έχει συμπληρωθεί το όριο υποβολών.'];
            }
            if ($availStatus === 'already_submitted') {
                return ['allowed' => false, 'reason' => 'Έχετε ήδη υποβάλει αυτή τη φόρμα και δεν επιτρέπεται δεύτερη υποβολή.'];
            }
        }

        // 3. Administrator bypass
        if ($userId) {
            $userStmt = $db->prepare("SELECT r.slug, u.role_id FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['slug'] === 'administrator') {
                return ['allowed' => true, 'reason' => ''];
            }
        } else {
            $user = null;
        }

        // 4. Check forms.submit permission for authenticated users
        $accessMode = $form['submission_access_mode'] ?? 'all_authenticated';

        if ($userId && $user) {
            $stmtPerm = $db->prepare("
                SELECT COUNT(*) FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = ? AND p.slug = 'forms.submit'
            ");
            $stmtPerm->execute([$user['role_id']]);
            $hasGlobalSubmit = ((int)$stmtPerm->fetchColumn() > 0);

            if (!$hasGlobalSubmit) {
                return ['allowed' => false, 'reason' => 'Δεν υπάρχει permission forms.submit'];
            }
        }

        // 5. Access Mode evaluation
        if ($accessMode === 'public') {
            if (!empty($form['is_public'])) {
                return ['allowed' => true, 'reason' => ''];
            }
            return ['allowed' => false, 'reason' => 'Η φόρμα δεν επιτρέπει δημόσια υποβολή.'];
        }

        if (!$userId) {
            return ['allowed' => false, 'reason' => 'Απαιτείται σύνδεση για την υποβολή αυτής της φόρμας.'];
        }

        if ($accessMode === 'all_authenticated') {
            return ['allowed' => true, 'reason' => ''];
        }

        if ($accessMode === 'selected_roles') {
            $stmtRole = $db->prepare("SELECT can_submit FROM form_role_assignments WHERE form_id = ? AND role_id = ?");
            $stmtRole->execute([$formId, $user['role_id']]);
            $roleAssign = $stmtRole->fetch();
            if ($roleAssign && (bool)$roleAssign['can_submit']) {
                return ['allowed' => true, 'reason' => ''];
            }
            return ['allowed' => false, 'reason' => 'Ο ρόλος δεν επιτρέπεται στη συγκεκριμένη φόρμα.'];
        }

        if ($accessMode === 'selected_users') {
            $stmtUser = $db->prepare("SELECT can_submit FROM form_user_assignments WHERE form_id = ? AND user_id = ?");
            $stmtUser->execute([$formId, $userId]);
            $userAssign = $stmtUser->fetch();
            if ($userAssign && (bool)$userAssign['can_submit']) {
                return ['allowed' => true, 'reason' => ''];
            }
            return ['allowed' => false, 'reason' => 'Ο χρήστης δεν βρίσκεται στους επιλεγμένους χρήστες.'];
        }

        if ($accessMode === 'nobody') {
            return ['allowed' => false, 'reason' => 'Η φόρμα δεν δέχεται υποβολές.'];
        }

        return ['allowed' => false, 'reason' => 'Μη έγκυρο access mode.'];
    }
}
