<?php
namespace App\Services;

use App\Core\Database;

class CloudRetryService {
    public static function calculateBackoff(int $attempt): int {
        // Exponential backoff mapping: 1 min, 5 min, 15 min, 60 min
        $delays = [60, 300, 900, 3600];
        $idx = min($attempt - 1, count($delays) - 1);
        $jitter = rand(-10, 10);
        return max(5, $delays[$idx] + $jitter);
    }

    public static function isRetryable(int $httpStatusCode): bool {
        return in_array($httpStatusCode, [408, 429, 500, 502, 503, 504]);
    }
}
