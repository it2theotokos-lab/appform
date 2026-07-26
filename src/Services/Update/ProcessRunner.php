<?php
namespace App\Services\Update;

class ProcessRunner {
    /**
     * Spawns a background PHP CLI process on Windows/IIS.
     */
    public static function runBackgroundWorker(int $updateId): bool {
        $phpPath = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
        if (stripos(PHP_OS, 'WIN') === 0) {
            $phpPath = str_ireplace('php-cgi.exe', 'php.exe', $phpPath);
        }
        $workerScript = realpath(__DIR__ . '/../../../tools/release/worker.php');

        if (!$workerScript) {
            return false;
        }

        $cmd = escapeshellcmd($phpPath) . ' -f ' . escapeshellarg($workerScript) . ' -- ' . escapeshellarg((string)$updateId);

        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows detached background execution bypass
            $handle = popen("start /B " . $cmd . " > NUL 2>&1", "r");
            if ($handle !== false) {
                pclose($handle);
                return true;
            }
        } else {
            // Unix fallback
            exec($cmd . " > /dev/null 2>&1 &");
            return true;
        }

        return false;
    }
}
