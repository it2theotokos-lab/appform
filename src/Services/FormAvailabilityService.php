<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class FormAvailabilityService {
    public static function checkAvailability(array $form, ?int $userId): string {
        $db = Database::getInstance();

        // 1. Check inactive
        if (empty($form['is_active']) || $form['status'] !== 'published') {
            return 'inactive';
        }

        // 2. Check not_started
        $now = date('Y-m-d H:i:s');
        if (!empty($form['submission_starts_at']) && $now < $form['submission_starts_at']) {
            return 'not_started';
        }

        // 3. Check expired
        if (!empty($form['submission_expires_at']) && $now > $form['submission_expires_at']) {
            return 'expired';
        }

        // 4. Check capacity_reached
        if (!empty($form['maximum_submissions'])) {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM form_submissions 
                WHERE form_id = ? AND status IN ('submitted', 'correction_requested', 'under_review', 'approved', 'rejected', 'returned')
            ");
            $stmt->execute([$form['id']]);
            $completedCount = (int)$stmt->fetchColumn();

            if ($completedCount >= (int)$form['maximum_submissions']) {
                return 'capacity_reached';
            }
        }

        // 5. Check already_submitted (single submission rule)
        if ($userId && !empty($form['single_submission_enabled'])) {
            $hasSubmitted = false;
            if (!empty($form['is_anonymous'])) {
                // Anonymous Single Submission Check using SHA-256 HMAC hash
                $secretKey = 'appform_secure_participation_hash_key';
                $hash = hash_hmac('sha256', $form['id'] . ':' . $userId, $secretKey);
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM anonymous_participations WHERE form_id = ? AND participation_hash = ?");
                $stmtCheck->execute([$form['id'], $hash]);
                $hasSubmitted = ((int)$stmtCheck->fetchColumn() > 0);
            } else {
                // Normal Single Submission Check
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM form_submissions WHERE form_id = ? AND user_id = ? AND status IN ('submitted', 'correction_requested', 'under_review', 'approved', 'rejected', 'returned')");
                $stmtCheck->execute([$form['id'], $userId]);
                $hasSubmitted = ((int)$stmtCheck->fetchColumn() > 0);
            }

            if ($hasSubmitted) {
                return 'already_submitted';
            }
        }

        // One completed submission per user, per form, per calendar day.
        // Returned submissions are excluded so an approved correction can be edited.
        if ($userId && !empty($form['daily_submission_enabled'])) {
            $stmtCheck = $db->prepare("
                SELECT COUNT(*) FROM form_submissions
                WHERE form_id = ? AND user_id = ?
                  AND status IN ('submitted', 'correction_requested', 'under_review', 'approved', 'rejected')
                  AND DATE(COALESCE(submitted_at, created_at)) = CURDATE()
            ");
            $stmtCheck->execute([$form['id'], $userId]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                return 'already_submitted_today';
            }
        }

        return 'available';
    }

    public static function getGreekMessage(string $status, array $form): string {
        switch ($status) {
            case 'inactive':
                return 'Η φόρμα δεν είναι ενεργή.';
            case 'not_started':
                return 'Η φόρμα δεν είναι ακόμη διαθέσιμη για υποβολή.';
            case 'expired':
                return trim($form['expiration_message'] ?? '') !== '' ? $form['expiration_message'] : 'Η προθεσμία υποβολής της συγκεκριμένης φόρμας έχει λήξει.';
            case 'capacity_reached':
                return trim($form['capacity_closed_message'] ?? '') !== '' ? $form['capacity_closed_message'] : 'Η έρευνα έχει ολοκληρωθεί, καθώς συμπληρώθηκε ο απαιτούμενος αριθμός συμμετοχών.';
            case 'already_submitted':
                return 'Έχετε ήδη υποβάλει αυτή τη φόρμα και δεν επιτρέπεται δεύτερη υποβολή.';
            case 'already_submitted_today':
                return 'Έχετε ήδη υποβάλει αυτή τη φόρμα σήμερα. Μπορείτε να ζητήσετε διόρθωση της υπάρχουσας υποβολής από τον reviewer.';
            default:
                return 'Διαθέσιμη';
        }
    }

    public static function getTodaySubmission(int $formId, int $userId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM form_submissions
            WHERE form_id = ? AND user_id = ?
              AND status IN ('submitted', 'correction_requested', 'under_review', 'approved', 'rejected')
              AND DATE(COALESCE(submitted_at, created_at)) = CURDATE()
            ORDER BY COALESCE(submitted_at, created_at) DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$formId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
