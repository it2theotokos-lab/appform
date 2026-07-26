<?php
namespace App\Jobs;

abstract class AbstractJob {
    protected $payload;
    protected $jobId;

    public function __construct(array $payload = [], ?int $jobId = null) {
        $this->payload = $payload;
        $this->jobId = $jobId;
    }

    public function getPayload(): array {
        return $this->payload;
    }

    public function getJobId(): ?int {
        return $this->jobId;
    }

    public function setJobId(int $jobId) {
        $this->jobId = $jobId;
    }

    abstract public function handle(): bool;
    
    public function failed(\Throwable $e) {
        // Safe logging or cleanups callback
    }

    protected function updateProgress(int $percentage, ?string $result = null) {
        if ($this->jobId) {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("UPDATE jobs SET progress = ?, result = ? WHERE id = ?");
            $stmt->execute([$percentage, $result, $this->jobId]);
        }
    }
}
