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

        $notifiedUserIds = [];

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

                // 5. Send emails to resolved recipient addresses & create internal notifications
                foreach ($recipients as $recipientEmail) {
                    $sent = false;
                    $errMsg = '';
                    try {
                        $sent = \App\Services\EmailService::sendEmail($recipientEmail, $subject, $body);
                    } catch (\Exception $eEmail) {
                        $errMsg = $eEmail->getMessage();
                    }

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
                        $sent ? 'Real SMTP Delivery Confirmed' : ($errMsg ?: 'SMTP Error encountered')
                    ]);

                    // Internal notification record creation for system users matching recipient email
                    $cleanEmail = trim($recipientEmail);
                    if (!empty($cleanEmail)) {
                        $stmtUser = $db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND is_active = 1");
                        $stmtUser->execute([$cleanEmail]);
                        $targetUserId = (int)$stmtUser->fetchColumn();

                        if ($targetUserId > 0 && !in_array($targetUserId, $notifiedUserIds, true)) {
                            $notifiedUserIds[] = $targetUserId;
                            $subUuid    = $submissionDetails['uuid'] ?? '';
                            $formTitle  = $submissionDetails['form_title'] ?? '';
                            $subName    = !empty($submissionDetails['submitter_name'])
                                ? $submissionDetails['submitter_name']
                                : 'Επισκέπτης';
                            $subEmail   = $submissionDetails['user_email'] ?? '';
                            $linkUrl    = $subUuid ? '/admin/submissions/' . $subUuid : '/my-submissions';

                            $notifTitle   = $formTitle
                                ? 'Νέα υποβολή: ' . $formTitle
                                : 'Νέα υποβολή φόρμας';

                            $notifMessage = $subEmail
                                ? 'Ο χρήστης ' . $subName . ' (' . $subEmail . ') υπέβαλε τη φόρμα.'
                                : $subName . ' υπέβαλε τη φόρμα.';
                            if ($subUuid) {
                                $notifMessage .= ' Υποβολή: ' . $subUuid;
                            }

                            \App\Services\NotificationService::notify(
                                $targetUserId,
                                'form_notification',
                                $notifTitle,
                                mb_substr($notifMessage, 0, 255),
                                $linkUrl
                            );
                        }
                    }
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
            return is_array($val) ? implode(', ', $val) : (string)$val;
        }, $result);

        return $result;
    }

    /**
     * Trigger a global (system-level) notification by slug.
     *
     * @param string $slug     The notification_templates.slug to look up.
     * @param string $toEmail  The recipient email address.
     * @param array  $context  Key/value pairs used both for rule evaluation and Smart Tag replacement.
     *                         Expected keys: user_name, user_email, user_role, site_name, site_url, action_url, etc.
     */
    public static function triggerGlobal(string $slug, string $toEmail, array $context = []): void {
        if (empty($slug) || empty($toEmail)) {
            return;
        }

        try {
            $db = Database::getInstance();

            // 1. Load the template
            $stmt = $db->prepare("SELECT * FROM notification_templates WHERE slug = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$slug]);
            $tpl = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$tpl) {
                return; // Template not found or inactive — silently skip
            }

            // Decode conditional logic and settings metadata
            $condJson = $tpl['conditional_logic_json'] ?? '';
            $cond = json_decode($condJson, true) ?: [];

            // If $toEmail is empty, attempt to resolve recipient from template configuration
            if (empty($toEmail)) {
                $recipType = $cond['recipient_type'] ?? 'event_user';
                if ($recipType === 'fixed' && !empty($cond['to_recipients'])) {
                    $toEmail = $cond['to_recipients'];
                } elseif ($recipType === 'role' && !empty($cond['recipient_role_id'])) {
                    $stmtRoles = $db->prepare("SELECT email FROM users WHERE role_id = ? AND is_active = 1");
                    $stmtRoles->execute([$cond['recipient_role_id']]);
                    $emails = $stmtRoles->fetchAll(\PDO::FETCH_COLUMN);
                    if ($emails) {
                        $toEmail = implode(',', array_filter($emails));
                    }
                } elseif ($recipType === 'event_user' && !empty($context['user_email'])) {
                    $toEmail = $context['user_email'];
                }
            }

            if (empty($toEmail)) {
                return;
            }

            // 2. Evaluate conditional logic rules (reuse existing engine)
            if (!empty($cond['enabled'])) {
                // For global notifications the "answers" map IS the context array.
                // fieldsMap is empty — global fields don't need schema lookup.
                if (!self::evaluateConditions($cond, $context, [])) {
                    // Rules say do not send — log and skip
                    $logPath = 'storage/logs/mail_delivery.log';
                    $logMsg = sprintf("[%s] GLOBAL NOTIFICATION '%s' SKIPPED BY RULES for recipient '%s'\n", date('Y-m-d H:i:s'), $slug, $toEmail);
                    @file_put_contents($logPath, $logMsg, FILE_APPEND);
                    return;
                }
            }

            // 3. Replace Smart Tags in subject and body
            $subject = self::replaceGlobalSmartTags($tpl['subject'] ?? '', $context);
            $bodyHtml = self::replaceGlobalSmartTags($tpl['body_html'] ?? '', $context);
            $bodyText = self::replaceGlobalSmartTags($tpl['body_text'] ?? '', $context);

            // 4. Send email (prefer HTML, fall back to plain text)
            $body = $bodyHtml ?: $bodyText;
            try {
                \App\Services\EmailService::sendEmail($toEmail, $subject, $body);
            } catch (\Exception $eEmail) {}

            // 5. Internal notification record creation for matching active AppForm users
            $rawRecipients = array_filter(array_map('trim', explode(',', $toEmail)));
            $notifiedGlobalUsers = [];
            foreach ($rawRecipients as $cleanRecipient) {
                if (empty($cleanRecipient)) {
                    continue;
                }

                $stmtUser = $db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND is_active = 1");
                $stmtUser->execute([$cleanRecipient]);
                $targetUserId = (int)$stmtUser->fetchColumn();

                if ($targetUserId > 0 && !in_array($targetUserId, $notifiedGlobalUsers, true)) {
                    $notifiedGlobalUsers[] = $targetUserId;
                    $actionUrl = !empty($context['action_url']) ? $context['action_url'] : '/notifications';
                    \App\Services\NotificationService::notify(
                        $targetUserId,
                        'global_notification',
                        $subject,
                        mb_substr(strip_tags($body), 0, 255),
                        $actionUrl
                    );
                }
            }

        } catch (\Exception $e) {
            try {
                $logPath = 'storage/logs/mail_delivery.log';
                $logMsg = sprintf("[%s] GLOBAL NOTIFICATION '%s' ERROR: %s\n", date('Y-m-d H:i:s'), $slug, $e->getMessage());
                @file_put_contents($logPath, $logMsg, FILE_APPEND);
            } catch (\Exception $eLog) {}
        }
    }

    /**
     * Replace Smart Tags in a global notification template string.
     * Supports: {user_name}, {user_email}, {user_role}, {site_name}, {site_url}, {action_url}
     */
    protected static function replaceGlobalSmartTags(string $template, array $context): string {
        $tags = [
            '{user_name}'  => $context['user_name']  ?? '',
            '{user_email}' => $context['user_email']  ?? '',
            '{user_role}'  => $context['user_role']   ?? '',
            '{site_name}'  => $context['site_name']   ?? '',
            '{site_url}'   => $context['site_url']    ?? '',
            '{action_url}' => $context['action_url']  ?? '',
        ];

        return str_replace(array_keys($tags), array_values($tags), $template);
    }
}
