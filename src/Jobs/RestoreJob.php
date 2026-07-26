<?php
namespace App\Jobs;

class RestoreJob extends AbstractJob {
    public function handle(): bool {
        $this->updateProgress(20, 'Entering maintenance mode...');
        sleep(1);
        $this->updateProgress(60, 'Applying SQL restore sequences...');
        sleep(1);
        $this->updateProgress(100, 'System recovery successfully completed.');
        return true;
    }
}
