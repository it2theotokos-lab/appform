<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Core\UploadManager;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionStatusHistory;
use App\Services\FormAccessService;
use App\Services\SubmissionValidator;
use App\Services\SubmissionWorkflowService;
use Exception;
use PDO;

class SubmissionController extends Controller {
    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                Auth::id(),
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }

    public function submitForm($params) {
        $slug = $params['slug'];
        $form = Form::findBySlug($slug);

        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $userId = Auth::id();
        $isAdmin = (Auth::role() === 'administrator');
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        $now = date('Y-m-d H:i:s');

        // 1. Enforce availability and restriction constraints (Bypass for admin)
        if (!$isAdmin) {
            $statusCheck = \App\Services\FormAvailabilityService::checkAvailability($form, $userId);
            if ($statusCheck !== 'available') {
                $greekMsg = \App\Services\FormAvailabilityService::getGreekMessage($statusCheck, $form);
                if ($isAjax) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $greekMsg]);
                    exit;
                }
                http_response_code(403);
                die('<div style="font-family:sans-serif; text-align:center; padding:50px;"><h2>' . htmlspecialchars($greekMsg) . '</h2></div>');
            }
        }

        $data = Request::all();
        $status = $data['status'] ?? 'submitted'; // draft or submitted

        // Terms of Use Server-side Validation
        if (!empty($form['require_terms_acceptance']) && $status === 'submitted') {
            if (empty($data['terms_acceptance']) || (int)$data['terms_acceptance'] !== 1) {
                $termsMsg = 'Πρέπει να διαβάσετε και να αποδεχθείτε τους όρους χρήσης πριν από την υποβολή.';
                if ($isAjax) {
                    http_response_code(422);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $termsMsg]);
                    exit;
                }
                http_response_code(422);
                die('<div style="font-family:sans-serif; text-align:center; padding:50px;"><h2>' . htmlspecialchars($termsMsg) . '</h2></div>');
            }
        }

        $accessCheck = FormAccessService::checkSubmissionAccess($userId, $form['id']);
        if (!$accessCheck['allowed']) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $accessCheck['reason']]);
                exit;
            }
            http_response_code(403);
            View::render('errors/403', ['message' => $accessCheck['reason']]);
            exit;
        }
        
        $version = FormVersion::getVersion($form['id'], $form['current_version']);
        if (!$version) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Σφάλμα: Η έκδοση φόρμας δεν βρέθηκε.']);
            exit;
        }

        $formVersionId = (int)$version['id'];
        $schema = json_decode($version['schema_json'], true);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        // Server-side validation
        $validator = new SubmissionValidator();
        $isFinal = ($status === 'submitted');
        
        if (!$validator->validate($data, $schema, $isFinal)) {
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Υπάρχουν σφάλματα στα πεδία.', 'errors' => $validator->errors()]);
                exit;
            }
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            $this->redirect('/forms/' . $slug);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Lock the form row for update to ensure atomic capacity enforcement
            $stmtLock = $db->prepare("SELECT * FROM forms WHERE id = ? FOR UPDATE");
            $stmtLock->execute([$form['id']]);
            $formLocked = $stmtLock->fetch(PDO::FETCH_ASSOC);

            // Re-evaluate capacity check atomically inside transaction
            if ($status === 'submitted' && !empty($formLocked['maximum_submissions'])) {
                $stmtCount = $db->prepare("
                    SELECT COUNT(*) FROM form_submissions 
                    WHERE form_id = ? AND status IN ('submitted', 'under_review', 'approved', 'rejected', 'returned')
                ");
                $stmtCount->execute([$form['id']]);
                $completedCount = (int)$stmtCount->fetchColumn();

                if ($completedCount >= (int)$formLocked['maximum_submissions']) {
                    $db->rollBack();
                    $msg = trim($formLocked['capacity_closed_message'] ?? '') !== '' ? $formLocked['capacity_closed_message'] : 'Η έρευνα έχει ολοκληρωθεί, καθώς συμπληρώθηκε ο απαιτούμενος αριθμός συμμετοχών.';
                    if ($isAjax) {
                        http_response_code(403);
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $msg]);
                        exit;
                    }
                    http_response_code(403);
                    die('<div style="font-family:sans-serif; text-align:center; padding:50px;"><h2>' . htmlspecialchars($msg) . '</h2></div>');
                }
            }

            $existingUuid = trim($data['submission_uuid'] ?? '');
            $existingSub = null;
            if ($existingUuid !== '') {
                $chkSub = $db->prepare("SELECT * FROM form_submissions WHERE uuid = ? AND user_id = ?");
                $chkSub->execute([$existingUuid, $userId]);
                $existingSub = $chkSub->fetch(PDO::FETCH_ASSOC);
            }

            if ($existingSub) {
                $uuid = $existingSub['uuid'];
                $submissionId = (int)$existingSub['id'];
            } else {
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            }
            
            // Filter answers according to schema
            $answers = $validator->validated();

            // Recalculate Calculated Fields server-side
            $fieldsMap = [];
            $calcFields = [];
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        $fieldsMap[$f['key']] = $f;
                        if ($f['type'] === 'calculated') {
                            $calcFields[] = $f;
                        }
                    }
                }
            }

            // Server-side enforce/override protected system-prefilled values
            foreach ($fieldsMap as $key => $field) {
                if (!empty($field['system_prefill_enabled']) && !empty($field['system_prefill_tag'])) {
                    $isProtected = !empty($field['system_prefill_readonly']) || !empty($field['system_prefill_hidden']);
                    if ($isProtected) {
                        // Ignore browser submitted value and overwrite it with server-side resolved value
                        $resolvedVal = \App\Services\SystemPrefillResolver::resolve($field['system_prefill_tag'], $form);
                        if ($resolvedVal !== null && $resolvedVal !== '') {
                            if (in_array($field['type'], ['select', 'radio'])) {
                                $options = $field['options'] ?? [];
                                $found = false;
                                foreach ($options as $opt) {
                                    if ((string)($opt['value']) === (string)($resolvedVal)) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if ($found) {
                                    $answers[$key] = $resolvedVal;
                                }
                            } else {
                                $answers[$key] = $resolvedVal;
                            }
                        }
                    }
                }
            }

            for ($pass = 0; $pass < 10; $pass++) {
                $anyChange = false;
                foreach ($calcFields as $cf) {
                    if (empty($cf['formula'])) continue;
                    try {
                        $newCalcVal = \App\Services\CalculationService::evaluate($cf['formula'], $answers, $fieldsMap);
                        if (isset($cf['decimalPlaces']) && is_numeric($cf['decimalPlaces'])) {
                            $newCalcVal = round($newCalcVal, (int)$cf['decimalPlaces']);
                        }
                        if (!isset($answers[$cf['key']]) || (float)$answers[$cf['key']] !== (float)$newCalcVal) {
                            $answers[$cf['key']] = $newCalcVal;
                            $anyChange = true;
                        }
                    } catch (\Exception $e) {
                        $answers[$cf['key']] = 'Error: ' . $e->getMessage();
                    }
                }
                if (!$anyChange) break;
            }

            foreach ($fieldsMap as $key => $field) {
                if (isset($field['conditional_logic']) && !empty($field['conditional_logic']['enabled'])) {
                    if (!\App\Services\ConditionalLogicService::isVisible($field['conditional_logic'], $answers, $fieldsMap)) {
                        $answers[$key] = null;
                        continue;
                    }
                }

                if ($field['type'] === 'password' && !empty($answers[$key])) {
                    $answers[$key] = password_hash($answers[$key], PASSWORD_BCRYPT);
                }

                if ($field['type'] === 'checkbox' && isset($answers[$key])) {
                    $answers[$key] = is_array($answers[$key]) ? json_encode($answers[$key], JSON_UNESCAPED_UNICODE) : $answers[$key];
                }

                if ($field['type'] === 'rich_text' && !empty($answers[$key])) {
                    $answers[$key] = strip_tags($answers[$key], '<b><i>StrongEm<u><p><br><ul><li><ol>');
                }

                if ($field['type'] === 'html') {
                    unset($answers[$key]);
                }
            }

            $submittedAt = ($status === 'submitted') ? date('Y-m-d H:i:s') : null;

            // Determine user_id to store (Set to NULL for true anonymous forms or guest users)
            $storeUserId = (!empty($form['is_anonymous']) || !$userId) ? null : $userId;

            $termsAcceptedAt = null;
            $termsVersionHash = null;
            if (!empty($form['require_terms_acceptance']) && $status === 'submitted') {
                $termsAcceptedAt = date('Y-m-d H:i:s');
                $termsVersionHash = hash('sha256', trim($form['terms_content'] ?? ''));
            }

            if ($existingSub) {
                $updSub = $db->prepare("
                    UPDATE form_submissions 
                    SET user_id = ?, data_json = ?, status = ?, submitted_at = COALESCE(submitted_at, ?),
                        terms_accepted_at = ?, terms_version_hash = ?
                    WHERE id = ?
                ");
                $updSub->execute([
                    $storeUserId,
                    json_encode($answers, JSON_UNESCAPED_UNICODE),
                    $status,
                    $submittedAt,
                    $termsAcceptedAt,
                    $termsVersionHash,
                    $submissionId
                ]);
            } else {
                $ins = $db->prepare("
                    INSERT INTO form_submissions (uuid, form_id, form_version_id, user_id, data_json, status, submitted_at,
                                                 terms_accepted_at, terms_version_hash)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $uuid,
                    $form['id'],
                    $formVersionId,
                    $storeUserId,
                    json_encode($answers, JSON_UNESCAPED_UNICODE),
                    $status,
                    $submittedAt,
                    $termsAcceptedAt,
                    $termsVersionHash
                ]);
                $submissionId = (int)$db->lastInsertId();
            }

            // Write to anonymous participation fingerprint table if anonymous form is submitted and user is logged in
            if (!empty($form['is_anonymous']) && $status === 'submitted' && $userId) {
                $secretKey = 'appform_secure_participation_hash_key';
                $hash = hash_hmac('sha256', $form['id'] . ':' . $userId, $secretKey);
                $stmtInsertPart = $db->prepare("
                    INSERT IGNORE INTO anonymous_participations (form_id, participation_hash, submission_uuid)
                    VALUES (?, ?, ?)
                ");
                $stmtInsertPart->execute([$form['id'], $hash, $uuid]);
            }

            // Trigger form notifications using central trigger service
            try {
                $subDetails = Submission::getDetailsByUuid($uuid);
                if ($status === 'draft') {
                    \App\Services\FormNotificationTriggerService::trigger((int)$form['id'], 'draft', $subDetails, $answers);
                    \App\Services\LifecycleNotificationService::notifyFormLifecycle('draft', $subDetails, $userId);
                } elseif ($status === 'submitted') {
                    \App\Services\FormNotificationTriggerService::trigger((int)$form['id'], 'submit', $subDetails, $answers);
                    \App\Services\LifecycleNotificationService::notifyFormLifecycle('submit', $subDetails, $userId);
                }
            } catch (\Throwable $eCustomNotif) {}

            if ($status === 'submitted') {
                // Send confirmation email to submitter
                try {
                    $currentUser = Auth::user();
                    if ($currentUser && !empty($currentUser['email'])) {
                        $subSubject = "Επιβεβαίωση Υποβολής Φόρμας: " . $form['title'];
                        $subBody = sprintf("Γεια σας %s,\n\nΗ υποβολή σας για τη φόρμα '%s' παραλήφθηκε επιτυχώς (UUID: %s).\n\nΜε εκτίμηση,\nAppForm Team", $currentUser['username'], $form['title'], $uuid);
                        \App\Services\EmailService::sendEmail($currentUser['email'], $subSubject, $subBody);
                    }

                    // Send new submission alert email to reviewer/manager
                    $reviewers = $db->query("
                        SELECT DISTINCT u.email 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.id 
                        WHERE r.slug IN ('administrator', 'manager', 'reviewer') AND u.is_active = 1 AND u.email != ''
                    ")->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($reviewers as $revEmail) {
                        $revSubject = sprintf("Νέα Υποβολή Φόρμας: %s (#%s)", $form['title'], $submissionId);
                        $revBody = sprintf("Γεια σας,\n\nΟ χρήστης '%s' υπέβαλε τη φόρμα '%s' (UUID: %s).\n\nΜπορείτε να την αξιολογήσετε στο:\n/admin/submissions/%s\n\nAppForm System", $currentUser['username'] ?? 'User', $form['title'], $uuid, $uuid);
                        \App\Services\EmailService::sendEmail($revEmail, $revSubject, $revBody);
                    }
                } catch (\Throwable $eSubmitterMail) {}

                // Trigger Webhooks
                \App\Services\WebhookService::trigger($form['id'], $submissionId, $answers);
            }

            // Handle file uploads
            $files = Request::files();
            foreach ($files as $fieldKey => $fileInfo) {
                if (!empty($fileInfo['name'])) {
                    // Find schema constraints for this field
                    $fieldConf = $this->getFieldSchemaConf($schema, $fieldKey);
                    $allowed = $fieldConf['acceptedTypes'] ?? null;
                    $maxSize = isset($fieldConf['maxSize']) ? ($fieldConf['maxSize'] * 1024 * 1024) : null;

                    $uploaded = UploadManager::upload($fileInfo, $allowed, $maxSize);

                    $insFile = $db->prepare("
                        INSERT INTO submission_files (submission_id, field_key, original_name, stored_name, mime_type, file_extension, file_size, checksum_sha256, storage_path, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insFile->execute([
                        $submissionId,
                        $fieldKey,
                        $uploaded['original_name'],
                        $uploaded['stored_name'],
                        $uploaded['mime_type'],
                        $uploaded['file_extension'],
                        $uploaded['file_size'],
                        $uploaded['checksum_sha256'],
                        $uploaded['storage_path'],
                        $userId
                    ]);

                    // Add file storage reference to answers list
                    $answers[$fieldKey] = '/my-submissions/' . $uuid . '/files/' . $uploaded['stored_name'] . '/download';
                }
            }

            // Update JSON content if file links were added
            $upd = $db->prepare("UPDATE form_submissions SET data_json = ? WHERE id = ?");
            $upd->execute([json_encode($answers, JSON_UNESCAPED_UNICODE), $submissionId]);

            // Start generic Form submission workflow if active workflow exists
            $hasWorkflow = false;
            if ($status === 'submitted' && $userId) {
                $hasWorkflow = \App\Services\WorkflowEngineService::start($submissionId, $userId, 'form');
                if ($hasWorkflow) {
                    $status = 'submitted';
                }
            }

            // Add History Record only if status has actually changed
            $oldStatus = $existingSub ? $existingSub['status'] : 'none';
            if ($oldStatus !== $status) {
                $hist = $db->prepare("
                    INSERT INTO submission_status_history (submission_id, old_status, new_status, notes, changed_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $note = ($oldStatus === 'none') ? 'Αρχική υποβολή' : 'Αλλαγή κατάστασης';
                $hist->execute([$submissionId, $oldStatus, $status, $note, $userId]);
            }

            $db->commit();
            $this->logAudit('create_submission', 'submissions', $submissionId, ['uuid' => $uuid]);

            $msg = ($status === 'draft') ? 'Το προσχέδιο αποθηκεύτηκε!' : 'Η φόρμα υποβλήθηκε επιτυχώς!';
            $redirectUrl = '/my-submissions';
            if (!$userId || !empty($form['is_public'])) {
                $redirectUrl = '/f/submitted/success?title=' . urlencode($form['title']);
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'submission_uuid' => $uuid,
                    'redirect' => $redirectUrl
                ]);
                exit;
            }

            Session::flash('success', $msg);
            $this->redirect($redirectUrl);

        } catch (Exception $e) {
            $db->rollBack();
            $msg = 'Σφάλμα υποβολής: ' . $e->getMessage();
            if ($isAjax) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            Session::flash('error', $msg);
            $this->redirect('/forms/' . $slug);
        }
    }

    private function getFieldSchemaConf(array $schema, string $key): array {
        foreach ($schema['sections'] as $sec) {
            foreach ($sec['fields'] as $f) {
                if ($f['key'] === $key) {
                    return $f;
                }
            }
        }
        return [];
    }

    public function mySubmissions() {
        $userId = Auth::id();
        $db = Database::getInstance();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $where = ["s.user_id = ?"];
        $params = [$userId];

        if ($search !== '') {
            $where[] = "(COALESCE(f.title, '') LIKE ? OR s.data_json LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($status !== '') {
            $where[] = "s.status = ?";
            $params[] = $status;
        }

        $sql = "
            SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') as form_title, f.slug as form_slug
            FROM form_submissions s
            LEFT JOIN forms f ON s.form_id = f.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY s.updated_at DESC, s.id DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('submissions/mine', [
            'title' => 'Οι Υποβολές Φορμών μου',
            'submissions' => $submissions,
            'totalRecords' => count($submissions)
        ]);
    }

    public function viewSubmission($params) {
        $uuid = $params['uuid'];
        $submission = Submission::getDetailsByUuid($uuid);

        if (!$submission) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Ownership authorization check
        if (Auth::role() !== 'administrator' && Auth::role() !== 'manager') {
            if ($submission['user_id'] !== Auth::id()) {
                http_response_code(403);
                View::render('errors/403');
                exit;
            }
        }

        $answers = json_decode($submission['data_json'], true);
        $schema = json_decode($submission['schema_json'], true);
        $files = SubmissionFile::getFilesBySubmissionId($submission['id']);
        $history = SubmissionStatusHistory::getHistoryBySubmissionId($submission['id']);

        View::render('portal/submission-view', [
            'title' => 'Στοιχεία Υποβολής #' . $submission['id'],
            'submission' => $submission,
            'schema' => $schema,
            'answers' => $answers,
            'files' => $files,
            'history' => $history
        ]);
    }

    public function downloadFile($params) {
        $uuid = $params['uuid'];
        $fileId = (int)$params['fileId'];

        $submission = Submission::findByUuid($uuid);
        if (!$submission) {
            http_response_code(404);
            die("Not Found");
        }

        // Ownership check
        if (Auth::role() !== 'administrator' && Auth::role() !== 'manager') {
            if ($submission['user_id'] !== Auth::id()) {
                http_response_code(403);
                die("Forbidden");
            }
        }

        $file = SubmissionFile::findById($fileId);
        if (!$file || $file['submission_id'] !== $submission['id']) {
            http_response_code(404);
            die("File Not Found");
        }

        if (!file_exists($file['storage_path'])) {
            http_response_code(404);
            die("File is missing from storage.");
        }

        $this->logAudit('download_file', 'submission_files', $fileId, ['name' => $file['original_name']]);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $file['file_size']);
        readfile($file['storage_path']);
        exit;
    }

    // --- Admin Review workflow actions ---

    public function listSubmissions() {
        $db = Database::getInstance();
        $userId = Auth::id();
        $userRole = Auth::role();

        $canViewAll = Auth::hasPermission('submissions.view.all') || $userRole === 'administrator';
        $canViewSubordinates = Auth::hasPermission('submissions.view.subordinates') || Auth::hasPermission('submissions.review') || $userRole === 'manager';
        $canViewOwn = Auth::hasPermission('submissions.view.own') || Auth::check();

        // Determine requested and maximum permitted scope
        $requestedScope = trim($_GET['scope'] ?? '');

        if ($requestedScope === 'all') {
            if (!$canViewAll) {
                http_response_code(403);
                View::render('errors/403');
                exit;
            }
            $activeScope = 'all';
        } elseif ($requestedScope === 'subordinates') {
            if (!$canViewSubordinates && !$canViewAll) {
                http_response_code(403);
                View::render('errors/403');
                exit;
            }
            $activeScope = 'subordinates';
        } elseif ($requestedScope === 'own') {
            $activeScope = 'own';
        } else {
            // Default scope based on highest permission
            if ($canViewAll) {
                $activeScope = 'all';
            } elseif ($canViewSubordinates) {
                $activeScope = 'subordinates';
            } else {
                $activeScope = 'own';
            }
        }

        $search = trim($_GET['search'] ?? '');
        $formId = trim($_GET['form_id'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $submitter = trim($_GET['submitter'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = [];
        $params = [];

        // Scope filter
        if ($activeScope === 'own') {
            $where[] = "s.user_id = ?";
            $params[] = $userId;
        } elseif ($activeScope === 'subordinates') {
            $subordinateIds = \App\Services\OrganizationalScopeService::getSubordinateIds($userId);
            // Include own + subordinates
            $allowedUserIds = array_unique(array_merge([$userId], $subordinateIds));
            $inClause = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $where[] = "s.user_id IN ({$inClause})";
            foreach ($allowedUserIds as $uId) {
                $params[] = $uId;
            }
        } // 'all' scope adds no user_id restriction

        if ($search !== '') {
            $where[] = "(COALESCE(f.title, '') LIKE ? OR u.username LIKE ? OR s.data_json LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($formId !== '') {
            $where[] = "s.form_id = ?";
            $params[] = $formId;
        }

        if ($status !== '') {
            $where[] = "s.status = ?";
            $params[] = $status;
        }

        if ($submitter !== '') {
            $where[] = "u.username LIKE ?";
            $params[] = "%{$submitter}%";
        }

        if ($dateFrom !== '') {
            $where[] = "s.created_at >= ?";
            $params[] = $dateFrom . " 00:00:00";
        }

        if ($dateTo !== '') {
            $where[] = "s.created_at <= ?";
            $params[] = $dateTo . " 23:59:59";
        }

        $sql = "
            SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') as form_title, u.username 
            FROM form_submissions s 
            LEFT JOIN forms f ON s.form_id = f.id 
            JOIN users u ON s.user_id = u.id 
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY s.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch forms for dropdown filter
        $forms = $db->query("SELECT id, title FROM forms ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/submissions/index', [
            'title' => 'Διαχείριση Υποβολών',
            'submissions' => $submissions,
            'forms' => $forms,
            'activeScope' => $activeScope,
            'canViewAll' => $canViewAll,
            'canViewSubordinates' => $canViewSubordinates,
            'canViewOwn' => $canViewOwn,
            'totalRecords' => count($submissions)
        ]);
    }

    public function showReview($params) {
        $uuid = $params['uuid'];
        $submission = Submission::getDetailsByUuid($uuid);

        if (!$submission) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Security check: ensure current user can view this submission
        $userId = Auth::id();
        $userRole = Auth::role();
        $canViewAll = Auth::hasPermission('submissions.view.all') || $userRole === 'administrator';
        $canViewSubordinates = Auth::hasPermission('submissions.view.subordinates') || Auth::hasPermission('submissions.review') || $userRole === 'manager';

        if (!$canViewAll) {
            if ($submission['user_id'] === $userId) {
                // Own submission
            } elseif ($canViewSubordinates) {
                $subordinateIds = \App\Services\OrganizationalScopeService::getSubordinateIds($userId);
                if (!in_array((int)$submission['user_id'], $subordinateIds)) {
                    http_response_code(403);
                    View::render('errors/403');
                    exit;
                }
            } else {
                http_response_code(403);
                View::render('errors/403');
                exit;
            }
        }

        $answers = json_decode($submission['data_json'], true);
        $schema = json_decode($submission['schema_json'], true);
        $files = SubmissionFile::getFilesBySubmissionId($submission['id']);
        $history = SubmissionStatusHistory::getHistoryBySubmissionId($submission['id']);

        View::render('admin/submissions/view', [
            'title' => 'Αξιολόγηση Υποβολής #' . $submission['id'],
            'submission' => $submission,
            'schema' => $schema,
            'answers' => $answers,
            'files' => $files,
            'history' => $history
        ]);
    }

    public function startReview($params) {
        $this->checkCsrf();
        $uuid = $params['uuid'];
        
        try {
            SubmissionWorkflowService::changeStatus($uuid, 'under_review', 'Έναρξη αξιολόγησης');
            Session::flash('success', 'Η κατάσταση άλλαξε σε Under Review.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->back();
    }

    public function approve($params) {
        $this->checkCsrf();
        $uuid = $params['uuid'];
        $notes = $_POST['review_notes'] ?? '';

        try {
            SubmissionWorkflowService::changeStatus($uuid, 'approved', $notes);
            Session::flash('success', 'Η υποβολή εγκρίθηκε.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/admin/submissions');
    }

    public function reject($params) {
        $this->checkCsrf();
        $uuid = $params['uuid'];
        $notes = $_POST['review_notes'] ?? '';

        try {
            SubmissionWorkflowService::changeStatus($uuid, 'rejected', $notes);
            Session::flash('success', 'Η υποβολή απορρίφθηκε.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/admin/submissions');
    }

    public function returnSubmission($params) {
        $this->checkCsrf();
        $uuid = $params['uuid'];
        $notes = $_POST['review_notes'] ?? '';

        try {
            SubmissionWorkflowService::changeStatus($uuid, 'returned', $notes);
            Session::flash('success', 'Η υποβολή επεστράφη στον χρήστη για διορθώσεις.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/admin/submissions');
    }

    public function returnToDraft($params) {
        $this->checkCsrf();
        if (Auth::role() !== 'administrator') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $uuid = $params['uuid'];
        $notes = $_POST['review_notes'] ?? $_POST['reason'] ?? '';

        if (empty(trim($notes))) {
            Session::flash('error', 'Η αιτιολογία είναι υποχρεωτική για την επιστροφή σε πρόχειρο.');
            $this->back();
            return;
        }

        try {
            SubmissionWorkflowService::changeStatus($uuid, 'draft', $notes);
            Session::flash('success', 'Η υποβολή επεστράφη σε κατάσταση Προσχεδίου (Draft).');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/admin/submissions');
    }

    public function deleteSubmission($params) {
        $this->checkCsrf();
        if (Auth::role() !== 'administrator' && !Auth::hasPermission('submissions.delete')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $uuid = $params['uuid'];
        $submission = Submission::findByUuid($uuid);
        if (!$submission) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $reason = $_POST['reason'] ?? 'Διαγραφή από τον διαχειριστή';

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $subId = $submission['id'];
            $db->exec("DELETE FROM submission_files WHERE submission_id = {$subId}");
            $db->exec("DELETE FROM submission_status_history WHERE submission_id = {$subId}");
            $db->exec("DELETE FROM form_submissions WHERE id = {$subId}");

            $this->logAudit('delete_submission', 'form_submissions', $subId, ['uuid' => $uuid, 'reason' => $reason]);

            $db->commit();
            Session::flash('success', 'Η υποβολή διαγράφηκε επιτυχώς.');
        } catch (Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Αποτυχία διαγραφής: ' . $e->getMessage());
        }
        $this->redirect('/admin/submissions');
    }
}
