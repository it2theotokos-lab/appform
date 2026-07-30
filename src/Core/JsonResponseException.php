<?php
namespace App\Core;

/**
 * JsonResponseException
 *
 * Thrown by controller methods that need to emit a JSON response and stop
 * execution (what previously called `exit` directly).
 *
 * In production, the Router catches this and performs the actual header/echo/exit.
 * In unit/acceptance tests, the test helper catches it and reads the payload
 * without the process terminating — enabling full assertion coverage.
 */
class JsonResponseException extends \RuntimeException {
    private array $payload;
    private int $statusCode;

    public function __construct(array $payload, int $statusCode = 200) {
        parent::__construct('JSON response: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->payload  = $payload;
        $this->statusCode = $statusCode;
    }

    public function getPayload(): array {
        return $this->payload;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    /** Convenience: emit headers + JSON + exit (used by the Router in production). */
    public function emit(): never {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
