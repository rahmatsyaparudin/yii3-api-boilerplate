<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class StructuredLoggingMiddleware implements MiddlewareInterface
{
    private array $config;
    private array $logs = [];

    public function __construct(array $config = [])
    {
        $this->config = \array_merge([
            'enabled'               => true,
            'log_level'             => 'info', // debug, info, warning, error
            'include_request_body'  => false,
            'include_response_body' => false,
            'max_log_size'          => 10000, // characters
            'exclude_paths'         => ['/health', '/metrics'],
            'exclude_status_codes'  => [404],
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->config['enabled']) {
            return $handler->handle($request);
        }

        $startTime = \microtime(true);
        $requestId = $request->getHeaderLine('X-Request-Id');

        // Log request
        $this->logRequest($request, $requestId, $startTime);

        try {
            $response = $handler->handle($request);

            $endTime  = \microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Log response
            $this->logResponse($request, $response, $requestId, $duration);

            return $response;
        } catch (\Throwable $exception) {
            $endTime  = \microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Log exception
            $this->logException($request, $exception, $requestId, $duration);

            throw $exception;
        }
    }

    private function logRequest(ServerRequestInterface $request, string $requestId, float $startTime): void
    {
        $path = $request->getUri()->getPath();

        // Skip logging for excluded paths
        if ($this->shouldSkipPath($path)) {
            return;
        }

        $logData = [
            'timestamp'      => \date('Y-m-d H:i:s'),
            'request_id'     => $requestId,
            'type'           => 'request',
            'method'         => $request->getMethod(),
            'path'           => $path,
            'query'          => $request->getQueryParams(),
            'user_agent'     => $this->truncate($request->getHeaderLine('User-Agent')),
            'ip_address'     => $this->getClientIp($request),
            'user_id'        => $this->getUserId($request),
            'content_length' => $request->getHeaderLine('Content-Length'),
        ];

        if ($this->config['include_request_body']) {
            $body = $request->getParsedBody();
            if ($body !== null) {
                $logData['body'] = $this->sanitizeBody($body);
            }
        }

        $this->writeLog('info', 'HTTP Request', $logData);
    }

    private function logResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $requestId,
        float $duration
    ): void {
        $path = $request->getUri()->getPath();

        // Skip logging for excluded paths or status codes
        if ($this->shouldSkipPath($path) || \in_array($response->getStatusCode(), $this->config['exclude_status_codes'], true)) {
            return;
        }

        $logData = [
            'timestamp'      => \date('Y-m-d H:i:s'),
            'request_id'     => $requestId,
            'type'           => 'response',
            'status_code'    => $response->getStatusCode(),
            'reason_phrase'  => $response->getReasonPhrase(),
            'duration_ms'    => \round($duration, 2),
            'content_length' => $response->getHeaderLine('Content-Length'),
        ];

        if ($this->config['include_response_body']) {
            $body = $response->getBody()->getContents();
            if (!empty($body)) {
                $response->getBody()->rewind(); // Reset pointer
                $logData['body'] = $this->truncate($body);
            }
        }

        $level = $this->getLogLevel($response->getStatusCode());
        $this->writeLog($level, 'HTTP Response', $logData);
    }

    private function logException(
        ServerRequestInterface $request,
        \Throwable $exception,
        string $requestId,
        float $duration
    ): void {
        $logData = [
            'timestamp'       => \date('Y-m-d H:i:s'),
            'request_id'      => $requestId,
            'type'            => 'exception',
            'exception_class' => $exception::class,
            'message'         => $exception->getMessage(),
            'code'            => $exception->getCode(),
            'file'            => $exception->getFile(),
            'line'            => $exception->getLine(),
            'duration_ms'     => \round($duration, 2),
            'trace'           => $exception->getTraceAsString(),
        ];

        $this->writeLog('error', 'HTTP Exception', $logData);
    }

    private function shouldSkipPath(string $path): bool
    {
        foreach ($this->config['exclude_paths'] as $excludePath) {
            if (\str_starts_with($path, $excludePath)) {
                return true;
            }
        }

        return false;
    }

    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }
        if ($statusCode >= 400) {
            return 'warning';
        }

        return 'info';
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ips = \explode(',', $serverParams[$header]);

                return \trim($ips[0]);
            }
        }

        return 'unknown';
    }

    private function getUserId(ServerRequestInterface $request): ?string
    {
        try {
            $currentUser = $request->getAttribute(App\Infrastructure\Security\CurrentUser::class);
            if ($currentUser && \method_exists($currentUser, 'getActor')) {
                $actor = $currentUser->getActor();

                // @var object $actor
                return isset($actor->id) ? (string) $actor->id : null;
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return null;
    }

    private function sanitizeBody(mixed $body): array|string
    {
        if (\is_array($body)) {
            return $this->sanitizeArray($body);
        }

        if (\is_string($body)) {
            // Remove sensitive data from JSON
            $sensitive = ['password', 'token', 'secret', 'key', 'auth'];
            foreach ($sensitive as $field) {
                $body = \preg_replace('/"' . $field . '":\s*"[^"]*"/i', '"' . $field . '":"[REDACTED]"', $body);
            }
        }

        return (string) $body;
    }

    private function sanitizeArray(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'auth'];

        foreach ($data as $key => $value) {
            if (\in_array(\strtolower($key), $sensitive, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (\is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            }
        }

        return $data;
    }

    private function truncate(string $value): string
    {
        if (\strlen($value) > $this->config['max_log_size']) {
            return \substr($value, 0, $this->config['max_log_size']) . '... [TRUNCATED]';
        }

        return $value;
    }

    private function writeLog(string $level, string $message, array $context): void
    {
        $logEntry = \json_encode([
            'level'   => \strtoupper($level),
            'message' => $message,
            'context' => $context,
        ]);

        if ($logEntry !== false) {
            \error_log($logEntry);
        }

        // Also store in memory for runtime access
        $this->logs[] = [
            'timestamp' => \microtime(true),
            'level'     => $level,
            'message'   => $message,
            'context'   => $context,
        ];

        // Keep only last 1000 logs
        if (\count($this->logs) > 1000) {
            $this->logs = \array_slice($this->logs, -1000);
        }
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getLogsByLevel(string $level): array
    {
        return \array_filter($this->logs, fn ($log) => $log['level'] === \strtoupper($level));
    }

    public function getLogsByRequestId(string $requestId): array
    {
        return \array_filter($this->logs, fn ($log) => ($log['context']['request_id'] ?? null) === $requestId);
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }

    public function getLogStats(): array
    {
        $stats = [
            'total_logs'          => \count($this->logs),
            'by_level'            => [],
            'recent_logs'         => [],
            'requests_per_minute' => 0,
        ];

        $oneMinuteAgo = \microtime(true) - 60;
        $requestCount = 0;

        foreach ($this->logs as $log) {
            // Count by level
            $level                     = $log['level'];
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;

            // Recent logs (last minute)
            if ($log['timestamp'] > $oneMinuteAgo) {
                $stats['recent_logs'][] = $log;

                // Count requests in last minute
                if ($log['message'] === 'HTTP Request') {
                    ++$requestCount;
                }
            }
        }

        $stats['requests_per_minute'] = $requestCount;

        return $stats;
    }
}
