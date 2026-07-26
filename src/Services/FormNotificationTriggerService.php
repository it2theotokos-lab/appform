<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use App\Models\Submission;
use PDO;

class FormNotificationTriggerService {
    public static function trigger(int $formId, string $event, array $submissionDetails, array $answers) {
        $db = Database::getInstance();

        // Map alternative event trigger keys for compatibility
        $eventsToFetch = [$event];
        if ($event === 'approved') {
            $eventsToFetch[] = 'approve';
            $eventsToFetch[] = 'final_approval';
        } elseif ($event === 'rejected') {
            $eventsToFetch[] = 'reject';
        } elseif ($event === 'returned') {
            $eventsToFetch[] = 'return';
        }

        $inClause = implode(',', array_fill(0, count($eventsToFetch), '?'));
        $stmt = $db->prepare("
            SELECT * FROM form_notifications 
            WHERE form_id = ? AND trigger_event IN ($inClause) AND is_enabled = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $params = array_merge([$formId], $eventsToFetch);
        $stmt->execute($params);
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rules)) {
            return;
        }

        // Fetch schema to parse field labels and keys configuration
        $stmtSchema = $db->prepare("
            SELECT v.schema_json FROM form_versions v
            JOIN forms f ON v.form_id = f.id AND v.version_number = f.current_version
            WHERE f.id = ?
        ");
        $stmtSchema->execute([$formId]);
        $schemaJson = $stmtSchema->fetchColumn();
        $schema = json_decode($schemaJson, true) ?: [];
        $fieldsMap = [];
        foreach ($schema['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] ?? [] as $f) {
                $fieldsMap[$f['key']] = $f;
            }
        }

        foreach ($rules as $rule) {
            try {
                // 2. Validate optional conditional logic rules
                $condJson = $rule['conditional_logic_json'] ?? '';
                $cond = json_decode($condJson, true);
                if ($cond && !empty($cond['enabled'])) {
                    if (!self::evaluateConditions($cond, $answers, $fieldsMap)) {
                        // Log skipped notification rule
                        $stmtLog = $db->prepare("
                            INSERT INTO form_notification_logs (notification_id, form_id, submission_id, recipient_summary, status)
                            VALUES (?, ?, ?, ?, 'skipped')
                        ");
                        $stmtLog->execute([$rule['id'], $formId, $submissionDetails['id'], $rule['to_recipients']]);
                        continue;
                    }
                }

                // 3. Resolve recipients based on recipient type
                $recipients = self::resolveRecipients($rule, $submissionDetails, $answers);
                if (empty($recipients)) {
                    continue;
                }

                // 4. Resolve template smart tags in subject and body templates
                $subject = self::replaceSmartTags($rule['subject_template'], $submissionDetails, $answers);
                $body = self::replaceSmartTags($rule['body_template'], $submissionDetails, $answers);

                // 5. Send emails to resolved recipient addresses
                foreach ($recipients as $recipientEmail) {
                    $sent = \App\Services\EmailService::sendEmail($recipientEmail, $subject, $body);

                    $stmtLog = $db->prepare("
                        INSERT INTO form_notification_logs (notification_id, form_id, submission_id, recipient_summary, status, provider_response)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtLog->execute([
                        $rule['id'],
                        $formId,
                        $submissionDetails['id'],
                        $recipientEmail,
                        $sent ? 'sent' : 'failed',
                        $sent ? 'Real SMTP Delivery Confirmed' : 'SMTP Error encountered'
                    ]);
                }

            } catch (\Exception $e) {
                // Error logged but must NOT block submission/workflow execution
                try {
                    $logPath = 'storage/logs/mail_delivery.log';
                    $logMsg = sprintf("[%s] CUSTOM NOTIFICATION RULE '%s' FAULT: %s\n", date('Y-m-d H:i:s'), $rule['name'], $e->getMessage());
                    file_put_contents($logPath, $logMsg, FILE_APPEND);
                } catch (\Exception $eLog) {}
            }
        }
    }

    protected static function evaluateConditions(array $cond, array $answers, array $fieldsMap): bool {
        $action = $cond['action'] ?? 'show';
        $match = $cond['match'] ?? 'all';
        $rules = $cond['rules'] ?? [];

        if (empty($rules)) {
            return true;
        }

        $results = [];
        foreach ($rules as $rule) {
            $fieldKey = $rule['field'];
            $operator = $rule['operator'];
            $expected = $rule['value'];

            $val = $answers[$fieldKey] ?? null;

            switch ($operator) {
                case 'equals':
                    $results[] = (string)$val === (string)$expected;
                    break;
                case 'not_equals':
                    $results[] = (string)$val !== (string)$expected;
                    break;
                case 'contains':
                    $results[] = stripos((string)$val, (string)$expected) !== false;
                    break;
                case 'not_contains':
                    $results[] = stripos((string)$val, (string)$expected) === false;
                    break;
                case 'greater_than':
                    $results[] = (float)$val > (float)$expected;
                    break;
                case 'less_than':
                    $results[] = (float)$val < (float)$expected;
                    break;
                case 'is_empty':
                    $results[] = ($val === null || trim((string)$val) === '');
                    break;
                case 'is_not_empty':
                    $results[] = ($val !== null && trim((string)$val) !== '');
                    break;
                case 'checkbox_contains':
                    if (is_array($val)) {
                        $results[] = in_array($expected, $val);
                    } else {
                        $results[] = stripos((string)$val, (string)$expected) !== false;
                    }
                    break;
                default:
                    $results[] = false;
            }
        }

        if ($match === 'all') {
            return !in_array(false, $results, true);
        } else {
            return in_array(true, $results, true);
        }
    }

    protected static function resolveRecipients(array $rule, array $submissionDetails, array $answers): array {
        $type = $rule['recipient_type'] ?? 'fixed';
        $db = Database::getInstance();

        switch ($type) {
            case 'fixed':
                $toStr = $rule['to_recipients'] ?? '';
                // Resolve dynamic placeholders inside fixed recipient field (e.g. {field:email})
                $toStr = self::replaceSmartTags($toStr, $submissionDetails, $answers);
                return array_filter(array_map('trim', explode(',', $toStr)));

            case 'submitter':
                $userEmail = $submissionDetails['user_email'] ?? '';
                if (!$userEmail && !empty($submissionDetails['user_id'])) {
                    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                    $stmt->execute([$submissionDetails['user_id']]);
                    $userEmail = $stmt->fetchColumn();
                }
                return $userEmail ? [$userEmail] : [];

            case 'manager':
                // Fetch manager of the submitter user
                if (!empty($submissionDetails['user_id'])) {
                    $stmtUser = $db->prepare("SELECT manager_id FROM users WHERE id = ?");
                    $stmtUser->execute([$submissionDetails['user_id']]);
                    $managerId = $stmtUser->fetchColumn();

                    if ($managerId) {
                        $stmtMgr = $db->prepare("SELECT email FROM users WHERE id = ?");
                        $stmtMgr->execute([$managerId]);
                        $mgrEmail = $stmtMgr->fetchColumn();
                        if ($mgrEmail) {
                            return [$mgrEmail];
                        }
                    }
                }
                return [];

            case 'role':
                $roleId = $rule['recipient_role_id'];
                if ($roleId) {
                    $stmt = $db->prepare("SELECT email FROM users WHERE role_id = ? AND is_active = 1 AND email != ''");
                    $stmt->execute([$roleId]);
                    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                }
                return [];

            case 'dept':
                $deptId = $rule['recipient_dept_id'];
                if ($deptId !== null && $deptId !== '') {
                    // Map dept index back to distinct name
                    $depts = $db->query("SELECT DISTINCT directory_department FROM users WHERE directory_department != '' ORDER BY directory_department ASC")->fetchAll(PDO::FETCH_COLUMN);
                    $deptName = $depts[$deptId] ?? null;
                    if ($deptName) {
                        $stmt = $db->prepare("SELECT email FROM users WHERE directory_department = ? AND is_active = 1 AND email != ''");
                        $stmt->execute([$deptName]);
                        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    }
                }
                return [];
        }

        return [];
    }

    protected static function replaceSmartTags(string $template, array $submissionDetails, array $answers): string {
        $db = Database::getInstance();
        $managerName = '';
        $managerEmail = '';

        // Resolve manager name/email if user_id present
        if (!empty($submissionDetails['user_id'])) {
            $stmtUser = $db->prepare("SELECT manager_id FROM users WHERE id = ?");
            $stmtUser->execute([$submissionDetails['user_id']]);
            $managerId = $stmtUser->fetchColumn();
            if ($managerId) {
                $stmtMgr = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
                $stmtMgr->execute([$managerId]);
                $mgr = $stmtMgr->fetch(PDO::FETCH_ASSOC);
                if ($mgr) {
                    $managerName = $mgr['full_name'] ?: '';
                    $managerEmail = $mgr['email'] ?: '';
                }
            }
        }

        $tags = [
            '{form_name}' => $submissionDetails['form_title'] ?? '',
            '{form_id}' => $submissionDetails['form_id'] ?? '',
            '{submission_id}' => $submissionDetails['id'] ?? '',
            '{submission_uuid}' => $submissionDetails['uuid'] ?? '',
            '{submission_date}' => $submissionDetails['submitted_at'] ?? $submissionDetails['created_at'] ?? '',
            '{submitter_name}' => $submissionDetails['submitter_name'] ?? $submissionDetails['username'] ?? 'External User',
            '{submitter_email}' => $submissionDetails['user_email'] ?? '',
            '{manager_name}' => $managerName,
            '{manager_email}' => $managerEmail,
            '{submission_status}' => $submissionDetails['status'] ?? 'submitted',
            '{submitted_at}' => $submissionDetails['submitted_at'] ?? '',
            '{manager_comments}' => $submissionDetails['review_notes'] ?? '',
            '{status}' => $submissionDetails['status'] ?? 'submitted'
        ];

        $result = str_replace(array_keys($tags), array_values($tags), $template);

        // Resolve dynamic field-based placeholders {field:FIELD_KEY}
        $result = preg_replace_callback('/\{field:([a-zA-Z0-9_]+)\}/i', function($m) use ($answers) {
            $key = $m[1];
            $val = $answers[$key] ?? '';
            return is_array($val) ? (is_array($val) ? implode(', ', $val) : $val) : '';
        }, $result);

        return $result;
    }
}
