<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use App\Models\Submission;
use Exception;

class SubmissionWorkflowService {
    protected static $transitions = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['under_review', 'approved', 'rejected', 'returned', 'draft', 'cancelled'],
        'under_review' => ['approved', 'rejected', 'returned', 'draft', 'cancelled'],
        'returned' => ['submitted', 'draft', 'cancelled'],
        'approved' => ['draft'],
        'rejected' => ['draft'],
        'cancelled' => ['draft']
    ];

    public static function changeStatus(string $uuid, string $newStatus, ?string $notes = ''): bool {
        $db = Database::getInstance();
        $submission = Submission::findByUuid($uuid);

        if (!$submission) {
            throw new Exception("Η υποβολή δεν βρέθηκε.");
        }

        $oldStatus = $submission['status'];
        
        // 1. Validate status transition
        $allowed = self::$transitions[$oldStatus] ?? [];
        if (!in_array($newStatus, $allowed)) {
            throw new Exception("Η μετάβαση από $oldStatus σε $newStatus δεν επιτρέπεται.");
        }

        // Validate notes requirement for rejection or return
        if (in_array($newStatus, ['rejected', 'returned']) && empty(trim($notes ?? ''))) {
            throw new Exception("Τα σχόλια αξιολόγησης είναι υποχρεωτικά για αυτή την ενέργεια.");
        }

        $db->beginTransaction();
        try {
            // Update submission status
            $reviewedBy = in_array($newStatus, ['approved', 'rejected', 'returned', 'under_review']) ? Auth::id() : null;
            $reviewedAt = $reviewedBy ? date('Y-m-d H:i:s') : null;

            $stmt = $db->prepare("
                UPDATE form_submissions 
                SET status = ?, reviewed_by = ?, reviewed_at = ?, review_notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $reviewedBy, $reviewedAt, $notes, $submission['id']]);

            // Add history record
            $hist = $db->prepare("
                INSERT INTO submission_status_history (submission_id, old_status, new_status, notes, changed_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $hist->execute([$submission['id'], $oldStatus, $newStatus, $notes, Auth::id()]);

            // Add audit log record
            $audit = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $audit->execute([
                Auth::id(),
                'status_change',
                'submissions',
                $submission['id'],
                json_encode(['old_status' => $oldStatus, 'new_status' => $newStatus], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            $db->commit();

            // Send status change notification email to user or reviewers
            try {
                $subDetails = Submission::getDetailsByUuid($uuid);
                $formId = (int)$subDetails['form_id'];
                $userEmail = $subDetails['user_email'] ?? '';

                // Map target transition trigger event
                $triggerEvent = 'submit';
                if ($oldStatus === 'returned' && $newStatus === 'submitted') $triggerEvent = 'resubmit';
                elseif ($newStatus === 'under_review') $triggerEvent = 'start_review';
                elseif ($newStatus === 'returned') $triggerEvent = 'return';
                elseif ($newStatus === 'approved') $triggerEvent = 'approve';
                elseif ($newStatus === 'rejected') $triggerEvent = 'reject';

                // Trigger custom notification rules
                $answers = json_decode($subDetails['data_json'], true) ?: [];
                \App\Services\FormNotificationTriggerService::trigger($formId, $triggerEvent, $subDetails, $answers);

                if (!empty($userEmail)) {
                    $subject = sprintf("Ενημέρωση Κατάστασης Υποβολής #%s: %s", $subDetails['id'], strtoupper($newStatus));
                    $body = sprintf(
                        "Γεια σας %s,\n\nΗ κατάσταση της υποβολής σας για τη φόρμα '%s' άλλαξε σε: %s.\n\nΣχόλια/Παρατηρήσεις:\n%s\n\nΜε εκτίμηση,\nAppForm Team",
                        $subDetails['username'],
                        $subDetails['form_title'],
                        strtoupper($newStatus),
                        $notes ?: 'Καμία παρατήρηση.'
                    );
                    \App\Services\EmailService::sendEmail($userEmail, $subject, $body);
                }
            } catch (\Throwable $eNotif) {}

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
