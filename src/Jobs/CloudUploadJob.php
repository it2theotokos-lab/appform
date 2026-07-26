<?php
namespace App\Jobs;

class CloudUploadJob extends AbstractJob {
    public function handle(): bool {
        $backupId = (int)($this->payload['backup_id'] ?? 0);
        $provider = $this->payload['provider'] ?? 'googledrive';

        $this->updateProgress(10, 'Initializing cloud session...');
        sleep(1);
        
        $this->updateProgress(50, 'Uploading chunks...');
        sleep(1);

        \App\Services\CloudBackupService::uploadToCloud($backupId, $provider);

        $this->updateProgress(100, 'Cloud replication sync completed.');
        return true;
    }
}
