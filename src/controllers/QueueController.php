<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\JobQueueService;
use App\Services\JobTypeRegistry;
use PDO;

class QueueController extends Controller {
    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                \App\Core\Auth::id() ?: 1,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }

    public function dispatchTestJob() {
        $this->checkCsrf();
        $jobType = Request::post('job_type', 'email');
        
        $jobClass = JobTypeRegistry::resolve($jobType === 'email' ? 'send_email' : ($jobType === 'pdf' ? 'generate_pdf' : 'generate_excel'));
        $payload = [];
        $queue = 'default';

        if ($jobType === 'email') {
            $payload = ['recipient' => 'test@appform.local', 'subject' => 'Test Email', 'body' => 'Body content'];
            $queue = 'email';
        } elseif ($jobType === 'pdf') {
            $payload = ['document_id' => 1];
            $queue = 'pdf';
        } else {
            $payload = ['report_type' => 'submissions'];
            $queue = 'reports';
        }

        if (!$jobClass) {
            Session::flash('error', 'Μη έγκυρος τύπος εργασίας.');
            $this->redirect('/admin/settings?tab=queue');
            return;
        }

        $jobId = JobQueueService::dispatch($jobClass, $payload, $queue, 'high');
        
        Session::flash('success', 'Η εργασία προστέθηκε στην ουρά (Job ID: ' . $jobId . ').');
        $this->redirect('/admin/settings?tab=queue');
    }

    public function cancelJob(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);

        $db = Database::getInstance();
        $db->prepare("UPDATE jobs SET status = 'cancelled', failed_at = NOW(), last_error = 'Cancelled by administrator' WHERE id = ? AND status = 'pending'")->execute([id]);

        $this->logAudit('job.cancelled', 'jobs', $id, []);
        Session::flash('success', 'Η εργασία ακυρώθηκε.');
        $this->redirect('/admin/settings?tab=queue');
    }

    public function retryJob(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);

        $db = Database::getInstance();
        $db->prepare("UPDATE jobs SET status = 'pending', attempts = 0, last_error = NULL WHERE id = ?")->execute([id]);

        $this->logAudit('job.retried', 'jobs', $id, []);
        Session::flash('success', 'Η εργασία προστέθηκε ξανά στην ουρά για επανεκτέλεση.');
        $this->redirect('/admin/settings?tab=queue');
    }
}
