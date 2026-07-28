<?php
namespace App\Commands;

use App\Core\Database;
use App\Services\CloudReplicationService;

class CloudWorkCommand {
    public function execute() {
        echo "--- Starting AppForm Cloud Replication Worker ---\n";
        echo "WARNING: The command cloud:work is deprecated. Background tasks are managed via queue:work.\n";
        
        // No execution under deprecated command
        $processed = CloudReplicationService::processQueue();
        
        echo "No pending jobs processed (deprecated worker active).\n";
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
