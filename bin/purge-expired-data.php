<?php
// Retention purger utility for expired export files

require_once __DIR__ . '/../vendor/autoload.php';
new \App\Core\App();

echo "Executing data retention purge...\n";

try {
    $db = \App\Core\Database::getInstance();
    
    // Purge expired export jobs files reference
    $stmt = $db->query("SELECT file_path FROM export_jobs WHERE expires_at < NOW() AND file_path IS NOT NULL");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $purged = 0;
    foreach ($jobs as $job) {
        if (file_exists($job['file_path'])) {
            unlink($job['file_path']);
            $purged++;
        }
    }

    // Update status to expired
    $db->query("UPDATE export_jobs SET status = 'expired' WHERE expires_at < NOW()");

    echo "[+] Purged $purged expired export files.\n";
    exit(0);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
