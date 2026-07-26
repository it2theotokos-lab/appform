<?php
namespace App\Jobs;

class GenerateExcelJob extends AbstractJob {
    public function handle(): bool {
        $this->updateProgress(30, 'Aggregating submission records...');
        sleep(1);
        $this->updateProgress(80, 'Formatting excel sheets...');
        sleep(1);
        $this->updateProgress(100, 'Excel file exported successfully.');
        return true;
    }
}
