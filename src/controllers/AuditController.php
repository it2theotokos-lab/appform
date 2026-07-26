<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Database;
use PDO;

class AuditController extends Controller {
    public function index() {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT a.*, u.username 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
            LIMIT 100
        ");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('audit/index', [
            'title' => 'Audit Log Viewer',
            'logs' => $logs
        ]);
    }
}
