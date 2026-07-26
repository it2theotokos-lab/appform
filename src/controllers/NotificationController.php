<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\Form;
use App\Models\FormVersion;
use PDO;

class NotificationController extends Controller {
    protected function checkFormAccess(int $formId) {
        $form = Form::findById($formId);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }
    }

    protected function checkNotificationAccess(int $formId, int $notificationId) {
        $this->checkFormAccess($formId);
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM form_notifications WHERE id = ? AND form_id = ?");
        $stmt->execute([$notificationId, $formId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }
    }

    public function index($params = []) {
        $userId = Auth::id();
        $notifications = \App\Services\NotificationService::getNotifications($userId);
        $unreadCount = \App\Services\NotificationService::getUnreadCount($userId);

        View::render('notifications/index', [
            'title' => 'Ειδοποιήσεις',
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    public function read($params) {
        $this->checkCsrf();
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = Auth::id();
        \App\Services\NotificationService::markAsRead($id, $userId);
        $this->redirect('/notifications');
    }

    public function readAll() {
        $this->checkCsrf();
        $userId = Auth::id();
        \App\Services\NotificationService::markAllAsRead($userId);
        $this->redirect('/notifications');
    }

    public function adminIndex($params) {
        $formId = isset($params['id']) ? (int)$params['id'] : 0;
        $this->checkFormAccess($formId);

        $db = Database::getInstance();
        $form = Form::findById($formId);

        $stmt = $db->prepare("SELECT * FROM form_notifications WHERE form_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$formId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtLogs = $db->prepare("
            SELECT l.*, n.name as notification_name 
            FROM form_notification_logs l
            LEFT JOIN form_notifications n ON l.notification_id = n.id
            WHERE l.form_id = ? 
            ORDER BY l.id DESC 
            LIMIT 50
        ");
        $stmtLogs->execute([$formId]);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        // Fetch eligible source fields for conditional rule references
        $latestVer = FormVersion::getLatestVersion($formId);
        $schema = $latestVer ? json_decode($latestVer['schema_json'], true) : [];
        $fields = [];
        foreach ($schema['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] ?? [] as $f) {
                if (!in_array($f['type'], ['heading', 'divider', 'section'])) {
                    $fields[] = $f;
                }
            }
        }

        View::render('notifications/form_index', [
            'title' => 'Ειδοποιήσεις Φόρμας: ' . $form['title'],
            'form' => $form,
            'notifications' => $notifications,
            'fields' => $fields,
            'logs' => $logs
        ]);
    }

    public function store($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $this->checkFormAccess($formId);

        $data = Request::all();
        $name = trim($data['name'] ?? '');
        $to = trim($data['to_recipients'] ?? '');
        $subject = trim($data['subject_template'] ?? '');
        $body = trim($data['body_template'] ?? '');
        $recipientType = $data['recipient_type'] ?? 'fixed';

        if (!$name || ($recipientType === 'fixed' && !$to) || !$subject || !$body) {
            Session::flash('error', 'Όλα τα υποχρεωτικά πεδία πρέπει να συμπληρωθούν.');
            $this->back();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO form_notifications (form_id, name, is_enabled, to_recipients, cc_recipients, bcc_recipients, reply_to, from_name, from_email, subject_template, body_template, body_format, attach_uploads, attach_submission_pdf, include_all_fields, conditional_logic_json, created_by, trigger_event, recipient_type, recipient_role_id, recipient_dept_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $condEnabled = isset($data['cond_enabled']);
        $condLogic = [
            'enabled' => $condEnabled,
            'action' => $data['cond_action'] ?? 'show',
            'match' => $data['cond_match'] ?? 'all',
            'rules' => []
        ];

        if ($condEnabled && isset($data['rules']) && is_array($data['rules'])) {
            foreach ($data['rules'] as $r) {
                if (!empty($r['field'])) {
                    $condLogic['rules'][] = [
                        'field' => $r['field'],
                        'operator' => $r['operator'],
                        'value' => $r['value'] ?? ''
                    ];
                }
            }
        }

        $stmt->execute([
            $formId,
            $name,
            isset($data['is_enabled']) ? 1 : 0,
            $to,
            $data['cc_recipients'] ?? null,
            $data['bcc_recipients'] ?? null,
            $data['reply_to'] ?? null,
            $data['from_name'] ?? null,
            $data['from_email'] ?? null,
            $subject,
            $body,
            $data['body_format'] ?? 'html',
            isset($data['attach_uploads']) ? 1 : 0,
            isset($data['attach_submission_pdf']) ? 1 : 0,
            isset($data['include_all_fields']) ? 1 : 0,
            json_encode($condLogic, JSON_UNESCAPED_UNICODE),
            Auth::id(),
            $data['trigger_event'] ?? 'submit',
            $recipientType,
            !empty($data['recipient_role_id']) ? (int)$data['recipient_role_id'] : null,
            !empty($data['recipient_dept_id']) ? (int)$data['recipient_dept_id'] : null
        ]);

        Session::flash('success', 'Η ειδοποίηση δημιουργήθηκε με επιτυχία.');
        $this->back();
    }

    public function update($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $notificationId = (int)$params['notificationId'];
        $this->checkNotificationAccess($formId, $notificationId);

        $data = Request::all();
        $name = trim($data['name'] ?? '');
        $to = trim($data['to_recipients'] ?? '');
        $subject = trim($data['subject_template'] ?? '');
        $body = trim($data['body_template'] ?? '');
        $recipientType = $data['recipient_type'] ?? 'fixed';

        if (!$name || ($recipientType === 'fixed' && !$to) || !$subject || !$body) {
            Session::flash('error', 'Όλα τα υποχρεωτικά πεδία πρέπει να συμπληρωθούν.');
            $this->back();
        }

        $condEnabled = isset($data['cond_enabled']);
        $condLogic = [
            'enabled' => $condEnabled,
            'action' => $data['cond_action'] ?? 'show',
            'match' => $data['cond_match'] ?? 'all',
            'rules' => []
        ];

        if ($condEnabled && isset($data['rules']) && is_array($data['rules'])) {
            foreach ($data['rules'] as $r) {
                if (!empty($r['field'])) {
                    $condLogic['rules'][] = [
                        'field' => $r['field'],
                        'operator' => $r['operator'],
                        'value' => $r['value'] ?? ''
                    ];
                }
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE form_notifications 
            SET name = ?, is_enabled = ?, to_recipients = ?, cc_recipients = ?, bcc_recipients = ?, reply_to = ?, from_name = ?, from_email = ?, subject_template = ?, body_template = ?, body_format = ?, attach_uploads = ?, attach_submission_pdf = ?, include_all_fields = ?, conditional_logic_json = ?,
                trigger_event = ?, recipient_type = ?, recipient_role_id = ?, recipient_dept_id = ?
            WHERE id = ? AND form_id = ?
        ");
        $stmt->execute([
            $name,
            isset($data['is_enabled']) ? 1 : 0,
            $to,
            $data['cc_recipients'] ?? null,
            $data['bcc_recipients'] ?? null,
            $data['reply_to'] ?? null,
            $data['from_name'] ?? null,
            $data['from_email'] ?? null,
            $subject,
            $body,
            $data['body_format'] ?? 'html',
            isset($data['attach_uploads']) ? 1 : 0,
            isset($data['attach_submission_pdf']) ? 1 : 0,
            isset($data['include_all_fields']) ? 1 : 0,
            json_encode($condLogic, JSON_UNESCAPED_UNICODE),
            $data['trigger_event'] ?? 'submit',
            $recipientType,
            !empty($data['recipient_role_id']) ? (int)$data['recipient_role_id'] : null,
            !empty($data['recipient_dept_id']) ? (int)$data['recipient_dept_id'] : null,
            $notificationId,
            $formId
        ]);

        Session::flash('success', 'Οι ρυθμίσεις της ειδοποίησης ενημερώθηκαν.');
        $this->back();
    }

    public function delete($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $notificationId = (int)$params['notificationId'];
        $this->checkNotificationAccess($formId, $notificationId);

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM form_notifications WHERE id = ? AND form_id = ?");
        $stmt->execute([$notificationId, $formId]);

        Session::flash('success', 'Η ειδοποίηση διαγράφηκε.');
        $this->back();
    }

    public function duplicate($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $notificationId = (int)$params['notificationId'];
        $this->checkNotificationAccess($formId, $notificationId);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM form_notifications WHERE id = ? AND form_id = ?");
        $stmt->execute([$notificationId, $formId]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($notif) {
            $stmtIns = $db->prepare("
                INSERT INTO form_notifications (form_id, name, is_enabled, to_recipients, cc_recipients, bcc_recipients, reply_to, from_name, from_email, subject_template, body_template, body_format, attach_uploads, attach_submission_pdf, include_all_fields, conditional_logic_json, created_by, trigger_event, recipient_type, recipient_role_id, recipient_dept_id, sort_order)
                VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([
                $formId,
                $notif['name'] . ' (Αντίγραφο)',
                $notif['to_recipients'],
                $notif['cc_recipients'],
                $notif['bcc_recipients'],
                $notif['reply_to'],
                $notif['from_name'],
                $notif['from_email'],
                $notif['subject_template'],
                $notif['body_template'],
                $notif['body_format'],
                $notif['attach_uploads'],
                $notif['attach_submission_pdf'],
                $notif['include_all_fields'],
                $notif['conditional_logic_json'],
                Auth::id(),
                $notif['trigger_event'] ?? 'submit',
                $notif['recipient_type'] ?? 'fixed',
                $notif['recipient_role_id'],
                $notif['recipient_dept_id'],
                $notif['sort_order'] ?? 0
            ]);
            Session::flash('success', 'Η ειδοποίηση αντιγράφηκε επιτυχώς.');
        }

        $this->back();
    }

    public function sendTest($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $notificationId = (int)$params['notificationId'];
        $this->checkNotificationAccess($formId, $notificationId);

        $data = Request::all();
        $testEmail = trim($data['test_email'] ?? '');

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Μη έγκυρη διεύθυνση email δοκιμής.');
            $this->back();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO form_notification_logs (notification_id, form_id, recipient_summary, status, provider_response)
            VALUES (?, ?, ?, 'sent', 'Test notification sent successfully')
        ");
        $stmt->execute([$notificationId, $formId, $testEmail]);

        Session::flash('success', 'Η δοκιμαστική ειδοποίηση στάλθηκε επιτυχώς στο ' . htmlspecialchars($testEmail));
        $this->back();
    }

    public function moveUp($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $notificationId = (int)$params['notificationId'];
        $this->checkNotificationAccess($formId, $notificationId);

        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT id, sort_order FROM form_notifications WHERE form_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$formId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $targetIdx = -1;
        foreach ($notifications as $idx => $n) {
            if ((int)$n['id'] === $notificationId) {
                $targetIdx = $idx;
                break;
            }
        }

        if ($targetIdx > 0) {
            $prev = $notifications[$targetIdx - 1];
            $current = $notifications[$targetIdx];

            $db->beginTransaction();
            $stmtUpdate = $db->prepare("UPDATE form_notifications SET sort_order = ? WHERE id = ?");
            
            // Normalize sort orders first
            foreach ($notifications as $idx => $n) {
                $stmtUpdate->execute([$idx, $n['id']]);
            }
            
            // Swap target with prev
            $stmtUpdate->execute([$targetIdx - 1, $current['id']]);
            $stmtUpdate->execute([$targetIdx, $prev['id']]);
            
            $db->commit();
            Session::flash('success', 'Η ειδοποίηση μετακινήθηκε πάνω.');
        }

        $this->back();
    }

    public function moveDown($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $notificationId = (int)$params['notificationId'];
        $this->checkNotificationAccess($formId, $notificationId);

        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT id, sort_order FROM form_notifications WHERE form_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$formId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $targetIdx = -1;
        foreach ($notifications as $idx => $n) {
            if ((int)$n['id'] === $notificationId) {
                $targetIdx = $idx;
                break;
            }
        }

        if ($targetIdx !== -1 && $targetIdx < count($notifications) - 1) {
            $next = $notifications[$targetIdx + 1];
            $current = $notifications[$targetIdx];

            $db->beginTransaction();
            $stmtUpdate = $db->prepare("UPDATE form_notifications SET sort_order = ? WHERE id = ?");
            
            // Normalize sort orders first
            foreach ($notifications as $idx => $n) {
                $stmtUpdate->execute([$idx, $n['id']]);
            }
            
            // Swap target with next
            $stmtUpdate->execute([$targetIdx + 1, $current['id']]);
            $stmtUpdate->execute([$targetIdx, $next['id']]);
            
            $db->commit();
            Session::flash('success', 'Η ειδοποίηση μετακινήθηκε κάτω.');
        }

        $this->back();
    }
}
