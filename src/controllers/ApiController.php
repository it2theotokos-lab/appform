<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

class ApiController extends Controller {
    /**
     * Authenticates the API request using simple Bearer token check.
     */
    protected function authenticate(): bool {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (empty($authHeader)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Missing token']);
            return false;
        }

        // Mock personal access token for verification demonstration
        $token = str_replace('Bearer ', '', $authHeader);
        if ($token !== 'test-api-token-value') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Invalid API token']);
            return false;
        }

        return true;
    }

    protected function jsonResponse($data, int $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET /api/v2/forms
     */
    public function getForms() {
        if (!$this->authenticate()) return;

        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, title, slug, status, current_version FROM forms ORDER BY id DESC");
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'success' => true,
            'data' => $forms
        ]);
    }

    /**
     * GET /api/v2/submissions
     */
    public function getSubmissions() {
        if (!$this->authenticate()) return;

        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, uuid, form_id, user_id, status, submitted_at FROM form_submissions ORDER BY id DESC");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'success' => true,
            'data' => $submissions
        ]);
    }
}
