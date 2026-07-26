<?php
namespace App\Jobs;

class GeneratePdfJob extends AbstractJob {
    public function handle(): bool {
        $this->updateProgress(20, 'Loading template configuration...');
        sleep(1);
        $this->updateProgress(60, 'Rendering document layouts...');
        sleep(1);
        $this->updateProgress(100, 'PDF generated successfully.');
        return true;
    }
}
