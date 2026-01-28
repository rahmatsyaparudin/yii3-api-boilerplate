<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Vendor Layer
use Yiisoft\Router\CurrentRoute;

final class ErrorMonitoringMiddleware implements MiddlewareInterface
{
    private array $config;
    private array $errorStorage = [];

    public function __construct(array $config = [])
    {
        $this->config = \array_merge([
            'enabled'                => true,
            'capture_exceptions'     => true,
            'capture_errors'         => true,
            'max_errors_per_request' => 10,
            'include_stack_trace'    => true,
            'include_request_data'   => false,
            'ignore_exceptions'      => [],
            'ignore_error_codes'     => [404, 422],
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->config['enabled']) {
            return $handler->handle($request);
        }

        try {
            $response = $handler->handle($request);

            // Capture non-exception errors if enabled
            if ($this->config['capture_errors'] && $response->getStatusCode() >= 400) {
                $statusCode = $response->getStatusCode();

                if (!\in_array($statusCode, $this->config['ignore_error_codes'], true)) {
                    $this->captureError($request, $response, null);
                }
            }

            return $response;
        } catch (\Throwable $exception) {
            if ($this->config['capture_exceptions']) {
                $this->captureError($request, null, $exception);
            }

            throw $exception;
        }
    }

    private function captureError(
        ServerRequestInterface $request,
        ?ResponseInterface $response,
        ?\Throwable $exception
    ): void {
        $errorId   = $this->generateErrorId();
        $timestamp = \microtime(true);

        $errorData = [
            'id'         => $errorId,
            'timestamp'  => $timestamp,
            'datetime'   => \date('Y-m-d H:i:s', (int) $timestamp),
            'request_id' => $this->getRequestId($request),
            'method'     => $request->getMethod(),
            'uri'        => (string) $request->getUri(),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'ip_address' => $this->getClientIp($request),
            'user_id'    => $this->getUserId($request),
        ];

        if ($exception) {
            $errorData['exception'] = [
                'class'   => $exception::class,
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $this->config['include_stack_trace'] ? $exception->getTraceAsString() : null,
            ];
        }

        if ($response) {
            $errorData['response'] = [
                'status_code'   => $response->getStatusCode(),
                'reason_phrase' => $response->getReasonPhrase(),
            ];
        }

        if ($this->config['include_request_data']) {
            $errorData['request'] = [
                'query_params' => $request->getQueryParams(),
                'parsed_body'  => $request->getParsedBody(),
                'headers'      => $this->sanitizeHeaders($request->getHeaders()),
            ];
        }

        // Store error in memory (for runtime access)
        $this->storeError($errorData);

        // Log error
        $this->logError($errorData);

        // Trigger error handlers
        $this->triggerErrorHandlers($errorData);
    }

    private function generateErrorId(): string
    {
        return \uniqid('err_', true);
    }

    private function getRequestId(ServerRequestInterface $request): string
    {
        return $request->getHeaderLine('X-Request-Id') ?: \uniqid('req_', true);
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
        // Try to get user ID from current user or JWT claims
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

    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'cookie', 'x-api-key'];
        $sanitized = [];

        foreach ($headers as $name => $values) {
            if (\in_array(\strtolower($name), $sensitive, true)) {
                $sanitized[$name] = ['[REDACTED]'];
            } else {
                $sanitized[$name] = $values;
            }
        }

        return $sanitized;
    }

    private function storeError(array $errorData): void
    {
        // Limit stored errors to prevent memory issues
        if (\count($this->errorStorage) >= $this->config['max_errors_per_request']) {
            \array_shift($this->errorStorage);
        }

        $this->errorStorage[] = $errorData;
    }

    private function logError(array $errorData): void
    {
        $logLevel = $this->getLogLevel($errorData);
        $message  = $this->formatLogMessage($errorData);

        \error_log("[$logLevel] " . $message);
    }

    private function getLogLevel(array $errorData): string
    {
        if (isset($errorData['response']['status_code'])) {
            $code = $errorData['response']['status_code'];
            if ($code >= 500) {
                return 'ERROR';
            }
            if ($code >= 400) {
                return 'WARNING';
            }
        }

        if (isset($errorData['exception'])) {
            return 'ERROR';
        }

        return 'INFO';
    }

    private function formatLogMessage(array $errorData): string
    {
        $parts = [
            "ErrorID: {$errorData['id']}",
            "RequestID: {$errorData['request_id']}",
            "Method: {$errorData['method']}",
            "URI: {$errorData['uri']}",
        ];

        if (isset($errorData['exception'])) {
            $parts[] = "Exception: {$errorData['exception']['class']}";
            $parts[] = "Message: {$errorData['exception']['message']}";
        }

        if (isset($errorData['response']['status_code'])) {
            $parts[] = "Status: {$errorData['response']['status_code']}";
        }

        return \implode(' | ', $parts);
    }

    private function triggerErrorHandlers(array $errorData): void
    {
        // Custom error handlers can be triggered here
        // For example: send to external service, trigger alerts, etc.

        // Example: Send critical errors to custom handler
        if ($this->isCriticalError($errorData)) {
            $this->handleCriticalError($errorData);
        }
    }

    private function isCriticalError(array $errorData): bool
    {
        // Define what constitutes a critical error
        if (isset($errorData['response']['status_code']) && $errorData['response']['status_code'] >= 500) {
            return true;
        }

        if (isset($errorData['exception']['class'])) {
            $exceptionClass     = $errorData['exception']['class'];
            $criticalExceptions = [
                'Throwable',
                'Error',
                'RuntimeException',
                'DatabaseException',
            ];

            foreach ($criticalExceptions as $critical) {
                if (\str_contains($exceptionClass, $critical)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function handleCriticalError(array $errorData): void
    {
        // Custom critical error handling
        // Example: Send to monitoring service, trigger alerts, etc.

        // Store critical error in separate storage
        $criticalErrors   = $this->getCriticalErrors();
        $criticalErrors[] = $errorData;

        // Keep only last 100 critical errors
        if (\count($criticalErrors) > 100) {
            $criticalErrors = \array_slice($criticalErrors, -100);
        }

        $this->setCriticalErrors($criticalErrors);
    }

    public function getErrors(): array
    {
        return $this->errorStorage;
    }

    public function getCriticalErrors(): array
    {
        static $criticalErrors = [];

        // @var array $criticalErrors
        return $criticalErrors;
    }

    private function setCriticalErrors(array $errors): void
    {
        static $criticalErrors = [];
        $criticalErrors        = $errors;
    }

    public function getErrorById(string $errorId): ?array
    {
        /** @var array|null $result */
        $result = $this->errorStorage[$errorId] ?? null;

        return $result;
    }

    public function clearErrors(): void
    {
        $this->errorStorage = [];
    }

    public function getErrorStats(): array
    {
        $stats = [
            'total_errors'      => \count($this->errorStorage),
            'by_status_code'    => [],
            'by_exception_type' => [],
            'recent_errors'     => [],
        ];

        foreach ($this->errorStorage as $error) {
            // Count by status code
            if (isset($error['response']['status_code'])) {
                $code                           = $error['response']['status_code'];
                $stats['by_status_code'][$code] = ($stats['by_status_code'][$code] ?? 0) + 1;
            }

            // Count by exception type
            if (isset($error['exception']['class'])) {
                $class                              = $error['exception']['class'];
                $stats['by_exception_type'][$class] = ($stats['by_exception_type'][$class] ?? 0) + 1;
            }

            // Recent errors (last 5 minutes)
            if ($error['timestamp'] > (\microtime(true) - 300)) {
                $stats['recent_errors'][] = $error;
            }
        }

        return $stats;
    }
}
