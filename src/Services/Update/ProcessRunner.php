<?php
namespace App\Services\Update;

use App\Core\Logger;

class ProcessRunner {
    /**
     * Resolves the PHP CLI executable path suitable for background process spawning.
     * On Windows/IIS, PHP_BINARY typically points to php-cgi.exe (FastCGI handler)
     * which cannot be used as a CLI. We must locate php.exe instead.
     *
     * Resolution order:
     * 1. Same directory as PHP_BINARY but with php.exe filename (Windows)
     * 2. Known IIS/PHP installation paths (Windows fallback)
     * 3. PHP_BINARY as-is (Unix / already php.exe)
     *
     * @return string|null Absolute path to php.exe, or null if not found
     */
    public static function resolvePhpCli(): ?string {
        $binary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : '';
        $isWindows = (stripos(PHP_OS, 'WIN') === 0);

        if ($isWindows) {
            // Attempt 1: replace cgi binary name in same directory
            $dir = dirname($binary);
            $candidate = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'php.exe';
            if (file_exists($candidate)) {
                Logger::log("[ProcessRunner] Resolved PHP CLI (same dir): {$candidate}");
                return $candidate;
            }

            // Attempt 2: known IIS / Windows PHP install locations
            $knownPaths = [
                'C:\\Antigravity-PRJ\\Tools\\PHP\\php.exe',
                'C:\\PHP\\php.exe',
                'C:\\php\\php.exe',
                'C:\\Program Files\\PHP\\php.exe',
                'C:\\Program Files (x86)\\PHP\\php.exe',
                'C:\\inetpub\\php\\php.exe',
            ];
            foreach ($knownPaths as $path) {
                if (file_exists($path)) {
                    Logger::log("[ProcessRunner] Resolved PHP CLI (known path): {$path}");
                    return $path;
                }
            }

            // Attempt 3: use 'where php.exe' via cmd (does not require git)
            $whereOutput = [];
            exec('where php.exe 2>NUL', $whereOutput);
            foreach ($whereOutput as $line) {
                $line = trim($line);
                if ($line && file_exists($line) && !stristr($line, 'cgi')) {
                    Logger::log("[ProcessRunner] Resolved PHP CLI (where): {$line}");
                    return $line;
                }
            }

            Logger::log("[ProcessRunner] ERROR: Could not locate php.exe on Windows. Binary was: {$binary}");
            return null;
        }

        // Unix: PHP_BINARY should already be the CLI binary
        if ($binary && file_exists($binary)) {
            Logger::log("[ProcessRunner] Resolved PHP CLI (Unix): {$binary}");
            return $binary;
        }

        return 'php'; // fallback for Unix PATH lookup
    }

    /**
     * Spawns a detached background PHP CLI process to run the update worker.
     * Logs: executable, script path, update ID, working directory, exit signal.
     */
    public static function runBackgroundWorker(int $updateId): bool {
        $phpPath = self::resolvePhpCli();

        if (!$phpPath) {
            Logger::log("[ProcessRunner] ABORT: No PHP CLI executable found. Cannot spawn worker for update #{$updateId}.");
            return false;
        }

        // Verify php.exe is not a FastCGI binary
        if (stripos(PHP_OS, 'WIN') === 0 && stristr($phpPath, 'php-cgi')) {
            Logger::log("[ProcessRunner] ABORT: Resolved path is php-cgi.exe, refusing to use as CLI: {$phpPath}");
            return false;
        }

        $workerScript = realpath(__DIR__ . '/../../../tools/release/worker.php');
        if (!$workerScript) {
            Logger::log("[ProcessRunner] ABORT: worker.php not found at expected path.");
            return false;
        }

        $workingDir = realpath(__DIR__ . '/../../../');
        $isWindows = (stripos(PHP_OS, 'WIN') === 0);

        // Quote paths properly for Windows (handles spaces)
        $phpQuoted = '"' . $phpPath . '"';
        $scriptQuoted = '"' . $workerScript . '"';
        $updateIdArg = (string)(int)$updateId; // sanitize

        Logger::log("[ProcessRunner] Spawning worker for update #{$updateId}:");
        Logger::log("[ProcessRunner]   Executable: {$phpPath}");
        Logger::log("[ProcessRunner]   Script    : {$workerScript}");
        Logger::log("[ProcessRunner]   Update ID : {$updateIdArg}");
        Logger::log("[ProcessRunner]   WorkingDir: {$workingDir}");

        if ($isWindows) {
            // Use cmd /c start /B to detach process from IIS worker
            $cmd = 'cmd /c start /B ' . $phpQuoted . ' -f ' . $scriptQuoted . ' -- ' . $updateIdArg . ' >NUL 2>&1';
            Logger::log("[ProcessRunner]   Command   : {$cmd}");

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = proc_open($cmd, $descriptors, $pipes, $workingDir);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
                Logger::log("[ProcessRunner] Worker launch exit code: {$exitCode}, stderr: " . trim($stderr));
                // exit code 0 from cmd /c start means the start command executed, not the worker itself
                return $exitCode === 0;
            }
            Logger::log("[ProcessRunner] proc_open failed on Windows.");
            return false;
        } else {
            // Unix: redirect to /dev/null and background with &
            $cmd = $phpQuoted . ' -f ' . $scriptQuoted . ' -- ' . $updateIdArg . ' >/dev/null 2>&1 &';
            Logger::log("[ProcessRunner]   Command   : {$cmd}");
            exec($cmd);
            return true;
        }
    }
}
