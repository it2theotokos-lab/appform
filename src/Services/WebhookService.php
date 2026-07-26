<?php
namespace App\Services;

use App\Core\Database;

class WebhookService {
    /**
     * Triggers all enabled webhooks for a form after a successful submission.
     */
    public static function trigger(int $formId, int $submissionId, array $data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM form_webhooks WHERE form_id = ? AND is_enabled = 1");
        $stmt->execute([$formId]);
        $webhooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($webhooks as $wh) {
            self::executeWebhook($wh, $submissionId, $data);
        }
    }

    private static function executeWebhook(array $wh, int $submissionId, array $data) {
        $startTime = microtime(true);
        $url = $wh['url'];
        $method = strtoupper($wh['method']);
        $headers = [];

        // Add authorization headers
        if ($wh['auth_type'] === 'bearer' && !empty($wh['auth_token'])) {
            $headers[] = 'Authorization: Bearer ' . $wh['auth_token'];
        }

        // Custom headers
        $custom = json_decode($wh['custom_headers'] ?? '{}', true);
        if (is_array($custom)) {
            foreach ($custom as $k => $v) {
                $headers[] = "$k: $v";
            }
        }

        $headers[] = 'Content-Type: application/json';
        $payload = json_encode([
            'submission_id' => $submissionId,
            'data' => $data
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $duration = (int)((microtime(true) - $startTime) * 1000);
        $status = ($code >= 200 && $code < 300) ? 'success' : 'failed';

        // Write execution logs
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO form_webhook_logs (webhook_id, submission_id, response_code, response_body, duration_ms, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$wh['id'], $submissionId, $code, substr($response, 0, 500), $duration, $status]);
    }
}
