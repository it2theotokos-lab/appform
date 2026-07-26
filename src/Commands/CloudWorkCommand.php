<?php
namespace App\Commands;

use App\Core\Database;
use App\Services\CloudReplicationService;

class CloudWorkCommand {
    public function execute() {
        echo "--- Starting AppForm Cloud Replication Worker ---\n";
        
        $db = Database::getInstance();
        $db->prepare("INSERT INTO worker_heartbeats (worker_name, last_heartbeat) VALUES ('queue_worker', NOW()) ON DUPLICATE KEY UPDATE last_heartbeat = NOW()")->execute();

        $processed = CloudReplicationService::processQueue();
        if ($processed > 0) {
            echo "Processed 1 job from the queue.\n";
        } else {
            echo "No pending jobs found.\n";
        }

        echo "--- Worker Execution Finished ---\n";
    }
}

// CLI Bootstrap
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $config = require __DIR__ . '/../../config/config.php';
    \App\Core\App::$config = $config;

    $cmd = new CloudWorkCommand();
    $cmd->execute();
}
