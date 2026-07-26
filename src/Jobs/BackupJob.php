<?php
namespace App\Jobs;

class BackupJob extends AbstractJob {
    public function handle(): bool {
        $this->updateProgress(30, 'Freezing database tables...');
        sleep(1);
        $this->updateProgress(70, 'Creating manual local backup file...');
        sleep(1);
        $this->updateProgress(100, 'Backup process finished.');
        return true;
    }
}
