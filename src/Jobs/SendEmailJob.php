<?php
namespace App\Jobs;

class SendEmailJob extends AbstractJob {
    public function handle(): bool {
        $notificationId = (int)($this->payload['notification_id'] ?? 0);
        $submissionId = (int)($this->payload['submission_id'] ?? 0);

        $db = \App\Core\Database::getInstance();

        // 1. Fetch Notification Details
        $stmtNotif = $db->prepare("SELECT * FROM form_notifications WHERE id = ?");
        $stmtNotif->execute([$notificationId]);
        $notification = $stmtNotif->fetch(\PDO::FETCH_ASSOC);

        if (!$notification) {
            throw new \Exception("Notification definition ID {$notificationId} not found.");
        }

        // 2. Fetch Submission Details
        $stmtSub = $db->prepare("SELECT * FROM form_submissions WHERE id = ?");
        $stmtSub->execute([$submissionId]);
        $submission = $stmtSub->fetch(\PDO::FETCH_ASSOC);

        if (!$submission) {
            throw new \Exception("Submission ID {$submissionId} not found.");
        }

        // 3. Fetch Form and Schema Details
        $stmtForm = $db->prepare("SELECT * FROM forms WHERE id = ?");
        $stmtForm->execute([$submission['form_id']]);
        $form = $stmtForm->fetch(\PDO::FETCH_ASSOC);

        $stmtVer = $db->prepare("SELECT schema_json FROM form_versions WHERE id = ?");
        $stmtVer->execute([$submission['form_version_id']]);
        $schemaJson = $stmtVer->fetchColumn();
        $schema = json_decode($schemaJson, true) ?: [];

        $fieldsMap = [];
        foreach ($schema['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] ?? [] as $f) {
                $fieldsMap[$f['key']] = $f;
            }
        }

        // 4. Resolve Recipients
        $to = \App\Services\NotificationTemplateService::resolve($notification['to_recipients'], $submission, $form, $schema, $fieldsMap);
        $cc = $notification['cc_recipients'] ? \App\Services\NotificationTemplateService::resolve($notification['cc_recipients'], $submission, $form, $schema, $fieldsMap) : '';
        $bcc = $notification['bcc_recipients'] ? \App\Services\NotificationTemplateService::resolve($notification['bcc_recipients'], $submission, $form, $schema, $fieldsMap) : '';

        // 5. Resolve Subject and Body templates
        $subject = \App\Services\NotificationTemplateService::resolve($notification['subject_template'], $submission, $form, $schema, $fieldsMap);
        $body = \App\Services\NotificationTemplateService::resolve($notification['body_template'], $submission, $form, $schema, $fieldsMap);

        // 6. Execute real email delivery via EmailService
        $sent = \App\Services\EmailService::sendEmail($to, $subject, $body);

        // Log to notification logs table
        $status = $sent ? 'sent' : 'failed';
        $response = $sent ? 'Email sent successfully via SendEmailJob' : 'Email delivery failed';
        
        $stmtLog = $db->prepare("
            INSERT INTO form_notification_logs (notification_id, form_id, submission_id, job_id, recipient_summary, status, provider_response, sent_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtLog->execute([$notificationId, $form['id'], $submissionId, $this->jobId, $to, $status, $response]);

        return $sent;
    }
}
