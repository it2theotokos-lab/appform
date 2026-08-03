<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LifecycleNotificationService {

    /**
     * Notify internal users about Form Lifecycle events.
     */
    public static function notifyFormLifecycle(
        string $event,
        array $submissionDetails,
        ?int $actorId = null,
        ?string $comment = null
    ): void {
        try {
            $db = Database::getInstance();
            $formId = (int)($submissionDetails['form_id'] ?? 0);
            $formTitle = $submissionDetails['form_title'] ?? 'Φόρμα';
            $subUuid = $submissionDetails['uuid'] ?? '';
            $subId = (int)($submissionDetails['id'] ?? 0);

            // Submitter details
            $submitterId = !empty($submissionDetails['user_id']) ? (int)$submissionDetails['user_id'] : null;
            $submitterName = !empty($submissionDetails['submitter_name']) ? $submissionDetails['submitter_name'] : 'Επισκέπτης';
            $submitterEmail = !empty($submissionDetails['user_email']) ? $submissionDetails['user_email'] : null;

            // Actor details
            $actorName = 'Σύστημα';
            if ($actorId) {
                $stmtActor = $db->prepare("SELECT username, full_name FROM users WHERE id = ?");
                $stmtActor->execute([$actorId]);
                $actorUser = $stmtActor->fetch(PDO::FETCH_ASSOC);
                if ($actorUser) {
                    $actorName = !empty($actorUser['full_name']) ? $actorUser['full_name'] : $actorUser['username'];
                }
            }

            // Submitter Manager
            $managerId = null;
            if ($submitterId) {
                $stmtMgr = $db->prepare("SELECT manager_id FROM users WHERE id = ? AND is_active = 1");
                $stmtMgr->execute([$submitterId]);
                $mgrVal = $stmtMgr->fetchColumn();
                if ($mgrVal) {
                    $managerId = (int)$mgrVal;
                }
            }

            // Reviewer/Approver where assigned
            $assignedReviewerId = null;
            if (!empty($submissionDetails['reviewed_by'])) {
                $assignedReviewerId = (int)$submissionDetails['reviewed_by'];
            }

            // Resolve admins/reviewers with permission
            $adminIds = self::getUsersWithPermission($db, 'submissions.review');

            // Recipient Sets
            $recipients = [];

            switch ($event) {
                case 'draft':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    break;

                case 'submit':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    if ($assignedReviewerId) {
                        $recipients['reviewer'] = $assignedReviewerId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'start_review':
                case 'under_review':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    if ($actorId) {
                        $recipients['actor'] = $actorId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'approve':
                case 'approved':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    if ($actorId) {
                        $recipients['actor'] = $actorId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'reject':
                case 'rejected':
                case 'return':
                case 'returned':
                case 'returned_for_correction':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    if ($actorId) {
                        $recipients['actor'] = $actorId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'return_to_draft':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    if ($actorId) {
                        $recipients['actor'] = $actorId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    break;

                case 'resubmit':
                    if ($submitterId) {
                        $recipients['submitter'] = $submitterId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    if ($assignedReviewerId) {
                        $recipients['reviewer'] = $assignedReviewerId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;
            }

            // In-memory deduplication and validation
            $uniqueRecipients = self::validateAndDeduplicate($db, $recipients);

            foreach ($uniqueRecipients as $roleKey => $userId) {
                // Generate personalized content based on the role the user is receiving it as
                $isSubmitter = ($roleKey === 'submitter');
                $title = '';
                $message = '';

                switch ($event) {
                    case 'draft':
                        $title = 'Το πρόχειρό σας αποθηκεύτηκε';
                        $message = "Το πρόχειρο της υποβολής σας για τη φόρμα «{$formTitle}» αποθηκεύτηκε επιτυχώς.";
                        break;

                    case 'submit':
                        if ($isSubmitter) {
                            $title = 'Η υποβολή σας καταχωρίστηκε';
                            $message = "Η υποβολή σας στη φόρμα «{$formTitle}» καταχωρίστηκε επιτυχώς. Αριθμός υποβολής: {$subUuid}";
                        } else {
                            $title = "Νέα υποβολή: {$formTitle}";
                            $message = $submitterEmail
                                ? "Ο χρήστης {$submitterName} ({$submitterEmail}) υπέβαλε τη φόρμα."
                                : "{$submitterName} υπέβαλε τη φόρμα.";
                            if ($subUuid) {
                                $message .= " Υποβολή: {$subUuid}";
                            }
                        }
                        break;

                    case 'start_review':
                    case 'under_review':
                        if ($isSubmitter) {
                            $title = 'Έναρξη αξιολόγησης υποβολής';
                            $message = "Η υποβολή σας {$subUuid} στη φόρμα «{$formTitle}» βρίσκεται υπό αξιολόγηση.";
                        } else {
                            $title = "Υπό αξιολόγηση: {$formTitle}";
                            $message = "Η υποβολή {$subUuid} τέθηκε υπό αξιολόγηση από τον χρήστη {$actorName}.";
                        }
                        break;

                    case 'approve':
                    case 'approved':
                        if ($isSubmitter) {
                            $title = 'Η υποβολή σας εγκρίθηκε';
                            $message = "Η υποβολή σας {$subUuid} στη φόρμα «{$formTitle}» εγκρίθηκε.";
                        } else {
                            $title = "Έγκριση υποβολής: {$formTitle}";
                            $message = "Η υποβολή {$subUuid} εγκρίθηκε από τον χρήστη {$actorName}.";
                        }
                        if ($comment) {
                            $message .= " Σχόλιο: {$comment}";
                        }
                        break;

                    case 'reject':
                    case 'rejected':
                        if ($isSubmitter) {
                            $title = 'Η υποβολή σας απορρίφθηκε';
                            $message = "Η υποβολή σας {$subUuid} στη φόρμα «{$formTitle}» απορρίφθηκε.";
                        } else {
                            $title = "Απόρριψη υποβολής: {$formTitle}";
                            $message = "Η υποβολή {$subUuid} απορρίφθηκε από τον χρήστη {$actorName}.";
                        }
                        if ($comment) {
                            $message .= " Αιτία: {$comment}";
                        }
                        break;

                    case 'return':
                    case 'returned':
                    case 'returned_for_correction':
                        if ($isSubmitter) {
                            $title = 'Η υποβολή επιστράφηκε για διόρθωση';
                            $message = "Η υποβολή σας {$subUuid} στη φόρμα «{$formTitle}» επιστράφηκε για διορθώσεις.";
                        } else {
                            $title = "Επιστροφή υποβολής: {$formTitle}";
                            $message = "Η υποβολή {$subUuid} επιστράφηκε για διορθώσεις από τον χρήστη {$actorName}.";
                        }
                        if ($comment) {
                            $message .= " Σχόλιο: {$comment}";
                        }
                        break;

                    case 'return_to_draft':
                        if ($isSubmitter) {
                            $title = 'Η υποβολή επεστράφη σε προσχέδιο';
                            $message = "Η υποβολή σας {$subUuid} στη φόρμα «{$formTitle}» επεστράφη σε κατάσταση προσχεδίου (draft).";
                        } else {
                            $title = "Επιστροφή σε προσχέδιο: {$formTitle}";
                            $message = "Η υποβολή {$subUuid} επεστράφη σε προσχέδιο από τον χρήστη {$actorName}.";
                        }
                        if ($comment) {
                            $message .= " Σχόλιο: {$comment}";
                        }
                        break;

                    case 'resubmit':
                        if ($isSubmitter) {
                            $title = 'Η επανυποβολή σας καταχωρίστηκε';
                            $message = "Η επανυποβολή σας {$subUuid} στη φόρμα «{$formTitle}» καταχωρίστηκε επιτυχώς.";
                        } else {
                            $title = "Επανυποβολή: {$formTitle}";
                            $message = "Η υποβολή {$subUuid} επανυποβλήθηκε από τον χρήστη {$submitterName}.";
                        }
                        break;
                }

                $linkUrl = ($isSubmitter || $roleKey === 'draft')
                    ? ($subUuid ? "/my-submissions" : "/my-submissions")
                    : ($subUuid ? "/admin/submissions/{$subUuid}" : "/admin/submissions");

                \App\Services\NotificationService::notify($userId, 'info', $title, mb_substr($message, 0, 255), $linkUrl);
            }

        } catch (\Exception $e) {
            self::logNotificationError('form_lifecycle', $submissionDetails['id'] ?? $submissionDetails['uuid'] ?? 'unknown', $e);
        }
    }

    /**
     * Notify internal users about Document / Workflow Lifecycle events.
     */
    public static function notifyDocumentLifecycle(
        string $event,
        array $documentContext,
        ?int $actorId = null,
        ?string $comment = null
    ): void {
        try {
            $db = Database::getInstance();
            $docId = (int)($documentContext['id'] ?? 0);
            $docNum = $documentContext['document_number'] ?? 'Έγγραφο';
            $creatorId = !empty($documentContext['created_by']) ? (int)$documentContext['created_by'] : null;

            // Resolve creator manager
            $managerId = null;
            if ($creatorId) {
                $stmtMgr = $db->prepare("SELECT manager_id FROM users WHERE id = ? AND is_active = 1");
                $stmtMgr->execute([$creatorId]);
                $mgrVal = $stmtMgr->fetchColumn();
                if ($mgrVal) {
                    $managerId = (int)$mgrVal;
                }
            }

            // Resolve admins
            $adminIds = self::getUsersWithPermission($db, 'document_instances.view_all');

            // Actor Details
            $actorName = 'Σύστημα';
            if ($actorId) {
                $stmtActor = $db->prepare("SELECT username, full_name FROM users WHERE id = ?");
                $stmtActor->execute([$actorId]);
                $actorUser = $stmtActor->fetch(PDO::FETCH_ASSOC);
                if ($actorUser) {
                    $actorName = !empty($actorUser['full_name']) ? $actorUser['full_name'] : $actorUser['username'];
                }
            }

            $recipients = [];

            switch ($event) {
                case 'document_submitted':
                    if ($creatorId) {
                        $recipients['creator'] = $creatorId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'workflow_task_assigned':
                    $assignedUserId = !empty($documentContext['assigned_user_id']) ? (int)$documentContext['assigned_user_id'] : null;
                    if ($assignedUserId) {
                        $recipients['assignee'] = $assignedUserId;
                    }
                    break;

                case 'workflow_approved':
                case 'workflow_advanced':
                    if ($creatorId) {
                        $recipients['creator'] = $creatorId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'workflow_rejected':
                    if ($creatorId) {
                        $recipients['creator'] = $creatorId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'workflow_returned':
                    if ($creatorId) {
                        $recipients['creator'] = $creatorId;
                    }
                    if ($managerId) {
                        $recipients['manager'] = $managerId;
                    }
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;

                case 'workflow_reassigned_old':
                    $oldUserId = !empty($documentContext['old_user_id']) ? (int)$documentContext['old_user_id'] : null;
                    if ($oldUserId) {
                        $recipients['old_assignee'] = $oldUserId;
                    }
                    break;

                case 'workflow_reassigned_new':
                    $newUserId = !empty($documentContext['new_user_id']) ? (int)$documentContext['new_user_id'] : null;
                    if ($newUserId) {
                        $recipients['new_assignee'] = $newUserId;
                    }
                    break;

                case 'workflow_cancelled':
                    if ($creatorId) {
                        $recipients['creator'] = $creatorId;
                    }
                    break;

                case 'pdf_ready':
                    if ($creatorId) {
                        $recipients['creator'] = $creatorId;
                    }
                    break;

                case 'pdf_failed':
                    foreach ($adminIds as $aid) {
                        $recipients['admin_' . $aid] = $aid;
                    }
                    break;
            }

            $uniqueRecipients = self::validateAndDeduplicate($db, $recipients);

            foreach ($uniqueRecipients as $roleKey => $userId) {
                $isCreator = ($roleKey === 'creator');
                $title = '';
                $message = '';
                $type = 'info';
                $linkUrl = "/documents/{$docId}";

                switch ($event) {
                    case 'document_submitted':
                        if ($isCreator) {
                            $title = 'Το έγγραφο υποβλήθηκε';
                            $message = "Το έγγραφο {$docNum} υποβλήθηκε επιτυχώς.";
                        } else {
                            $title = 'Νέα υποβολή εγγράφου';
                            $message = "Ο χρήστης {$actorName} υπέβαλε το έγγραφο {$docNum}.";
                        }
                        break;

                    case 'workflow_task_assigned':
                        $stepName = $documentContext['step_name'] ?? 'Βήμα Workflow';
                        $stepType = $documentContext['step_type'] ?? '';
                        $title = ($stepType === 'signature') ? 'Απαιτείται Υπογραφή' : 'Εκκρεμεί Έγκριση';
                        $message = "Έχετε μια νέα εργασία για το έγγραφο/φόρμα {$docNum} στο βήμα '{$stepName}'.";
                        $taskInstanceId = $documentContext['task_instance_id'] ?? 0;
                        if ($taskInstanceId) {
                            $linkUrl = "/workflow/tasks/{$taskInstanceId}";
                        }
                        break;

                    case 'workflow_approved':
                    case 'workflow_advanced':
                        $title = 'Έγκριση Workflow';
                        $message = "Το έγγραφο {$docNum} εγκρίθηκε / προωθήθηκε από τον χρήστη {$actorName}.";
                        break;

                    case 'workflow_rejected':
                        $title = 'Απόρριψη Εγγράφου/Φόρμας';
                        $message = "Το έγγραφο {$docNum} απορρίφθηκε από τον χρήστη {$actorName}.";
                        $type = 'danger';
                        break;

                    case 'workflow_returned':
                        $title = 'Επιστροφή για Διορθώσεις';
                        $message = "Το έγγραφο {$docNum} επιστράφηκε για διορθώσεις από τον χρήστη {$actorName}.";
                        $type = 'warning';
                        if ($comment) {
                            $message .= " Σχόλιο: '{$comment}'";
                        }
                        if ($isCreator) {
                            $linkUrl = "/documents/{$docId}/edit";
                        }
                        break;

                    case 'workflow_reassigned_old':
                        $stepName = $documentContext['step_name'] ?? 'Βήμα Workflow';
                        $title = 'Αφαίρεση Εργασίας Workflow';
                        $message = "Η εργασία '{$stepName}' για το έγγραφο {$docNum} σας αφαιρέθηκε.";
                        $type = 'warning';
                        break;

                    case 'workflow_reassigned_new':
                        $stepName = $documentContext['step_name'] ?? 'Βήμα Workflow';
                        $title = 'Νέα Εργασία Workflow (Επανανάθεση)';
                        $message = "Σας ανατέθηκε η εργασία '{$stepName}' για το έγγραφο {$docNum}.";
                        $taskInstanceId = $documentContext['task_instance_id'] ?? 0;
                        if ($taskInstanceId) {
                            $linkUrl = "/workflow/tasks/{$taskInstanceId}";
                        }
                        break;

                    case 'workflow_cancelled':
                        $title = 'Ακύρωση Workflow Εγγράφου';
                        $message = "Το workflow του εγγράφου {$docNum} ακυρώθηκε από τον διαχειριστή.";
                        $type = 'danger';
                        break;

                    case 'pdf_ready':
                        $title = 'Το τελικό PDF είναι έτοιμο';
                        $message = "Το τελικό PDF του εγγράφου {$docNum} δημιουργήθηκε επιτυχώς.";
                        $type = 'success';
                        break;

                    case 'pdf_failed':
                        $title = 'Αποτυχία δημιουργίας τελικού PDF';
                        $message = "Δεν δημιουργήθηκε το τελικό PDF για το έγγραφο {$docNum}.";
                        $type = 'danger';
                        if ($comment) {
                            $message .= " Σφάλμα: {$comment}";
                        }
                        break;
                }

                \App\Services\NotificationService::notify($userId, $type, $title, mb_substr($message, 0, 255), $linkUrl);
            }

        } catch (\Exception $e) {
            self::logNotificationError('document_lifecycle', $documentContext['id'] ?? $documentContext['document_number'] ?? 'unknown', $e);
        }
    }

    /**
     * Resolve users that have a specific permission or belong to the Administrator role.
     */
    private static function getUsersWithPermission(PDO $db, string $permissionSlug): array {
        $stmt = $db->prepare("
            SELECT DISTINCT u.id 
            FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE p.slug = ? AND u.is_active = 1
            UNION
            SELECT id FROM users 
            WHERE role_id = (SELECT id FROM roles WHERE slug = 'administrator') AND is_active = 1
        ");
        $stmt->execute([$permissionSlug]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private static function validateAndDeduplicate(PDO $db, array $recipients): array {
        $unique = [];
        $ids = array_values(array_filter(array_unique(array_values($recipients))));
        if (empty($ids)) {
            return [];
        }

        $inClause = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id FROM users WHERE id IN ($inClause) AND is_active = 1");
        $stmt->execute($ids);
        $activeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Keep mapping structure but filter only valid active users
        foreach ($recipients as $roleKey => $userId) {
            if ($userId && in_array($userId, $activeIds) && !in_array($userId, $unique, true)) {
                $unique[$roleKey] = (int)$userId;
            }
        }

        return $unique;
    }



    /**
     * Log errors without halting transaction logic.
     */
    private static function logNotificationError(string $type, string $entityId, \Exception $e): void {
        try {
            $logPath = 'storage/logs/mail_delivery.log';
            $logMsg = sprintf(
                "[%s] LIFECYCLE NOTIFICATION FAULT for %s ID %s: %s (%s)\n",
                date('Y-m-d H:i:s'),
                $type,
                $entityId,
                $e->getMessage(),
                get_class($e)
            );
            file_put_contents($logPath, $logMsg, FILE_APPEND);
        } catch (\Exception $exLog) {}
    }
}
