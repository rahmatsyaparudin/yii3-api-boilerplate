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

final class MetricsMiddleware implements MiddlewareInterface
{
    private array $metrics = [];
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = \array_merge([
            'enabled'             => true,
            'track_response_time' => true,
            'track_request_count' => true,
            'track_status_codes'  => true,
            'track_memory_usage'  => true,
            'track_cpu_usage'     => false, // Requires additional extensions
            'reset_interval'      => 300, // Reset metrics every 5 minutes
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->config['enabled']) {
            return $handler->handle($request);
        }

        $startTime   = \microtime(true);
        $startMemory = \memory_get_usage(true);

        try {
            $response = $handler->handle($request);

            $endTime   = \microtime(true);
            $endMemory = \memory_get_usage(true);

            // Record metrics
            $this->recordMetrics($request, $response, $startTime, $endTime, $startMemory, $endMemory);

            return $response;
        } catch (\Throwable $exception) {
            $endTime   = \microtime(true);
            $endMemory = \memory_get_usage(true);

            // Record error metrics
            $this->recordErrorMetrics($request, $exception, $startTime, $endTime, $startMemory, $endMemory);

            throw $exception;
        }
    }

    private function recordMetrics(
        ServerRequestInterface $request,
        ResponseInterface $response,
        float $startTime,
        float $endTime,
        int $startMemory,
        int $endMemory
    ): void {
        $path        = $this->normalizePath($request->getUri()->getPath());
        $method      = $request->getMethod();
        $statusCode  = $response->getStatusCode();
        $duration    = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;

        // Response time metrics
        if ($this->config['track_response_time']) {
            $this->metrics['response_time'][$path][$method][] = $duration;
            $this->metrics['response_time']['all'][]          = $duration;
        }

        // Request count metrics
        if ($this->config['track_request_count']) {
            $key                                     = "{$method} {$path}";
            $this->metrics['request_count'][$key]    = ($this->metrics['request_count'][$key] ?? 0) + 1;
            $this->metrics['request_count']['total'] = ($this->metrics['request_count']['total'] ?? 0) + 1;
        }

        // Status code metrics
        if ($this->config['track_status_codes']) {
            $this->metrics['status_codes'][$statusCode] = ($this->metrics['status_codes'][$statusCode] ?? 0) + 1;
        }

        // Memory usage metrics
        if ($this->config['track_memory_usage']) {
            $this->metrics['memory_usage'][$path][]   = $memoryUsage;
            $this->metrics['memory_usage']['current'] = \memory_get_usage(true);
            $this->metrics['memory_usage']['peak']    = \memory_get_peak_usage(true);
        }

        // Clean old metrics periodically
        $this->cleanupOldMetrics();
    }

    private function recordErrorMetrics(
        ServerRequestInterface $request,
        \Throwable $exception,
        float $startTime,
        float $endTime,
        int $startMemory,
        int $endMemory
    ): void {
        $path           = $this->normalizePath($request->getUri()->getPath());
        $method         = $request->getMethod();
        $duration       = ($endTime - $startTime) * 1000;
        $exceptionClass = $exception::class;

        // Error metrics
        $this->metrics['errors'][$path][$method][$exceptionClass] = ($this->metrics['errors'][$path][$method][$exceptionClass] ?? 0) + 1;

        $this->metrics['errors']['total'] = ($this->metrics['errors']['total'] ?? 0) + 1;

        // Error response time
        if ($this->config['track_response_time']) {
            $this->metrics['error_response_time'][] = $duration;
        }
    }

    private function normalizePath(string $path): string
    {
        // Replace dynamic segments with placeholders
        $path = \preg_replace('/\d+/', '{id}', $path);
        $path = \preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', '{uuid}', $path);

        return $path ?? '';
    }

    private function cleanupOldMetrics(): void
    {
        $now           = \microtime(true);
        $resetInterval = $this->config['reset_interval'];

        // Check if it's time to reset metrics
        if (isset($this->metrics['last_reset'])) {
            if ($now - $this->metrics['last_reset'] < $resetInterval) {
                return;
            }
        }

        // Reset metrics
        $this->metrics = [
            'last_reset'          => $now,
            'response_time'       => [],
            'request_count'       => [],
            'status_codes'        => [],
            'memory_usage'        => [],
            'errors'              => [],
            'error_response_time' => [],
        ];
    }

    public function getMetrics(): array
    {
        return [
            'response_time' => $this->getResponseTimeStats(),
            'request_count' => $this->getRequestCountStats(),
            'status_codes'  => $this->getStatusCodeStats(),
            'memory_usage'  => $this->getMemoryUsageStats(),
            'errors'        => $this->getErrorStats(),
            'system'        => $this->getSystemStats(),
        ];
    }

    private function getResponseTimeStats(): array
    {
        $stats = [];

        foreach ($this->metrics['response_time'] as $key => $times) {
            if (empty($times)) {
                continue;
            }

            \sort($times);
            $count = \count($times);
            $sum   = \array_sum($times);

            $stats[$key] = [
                'count' => $count,
                'avg'   => \round($sum / $count, 2),
                'min'   => $times ? \min($times) : 0,
                'max'   => $times ? \max($times) : 0,
                'p50'   => $this->percentile($times, 50),
                'p95'   => $this->percentile($times, 95),
                'p99'   => $this->percentile($times, 99),
            ];
        }

        return $stats;
    }

    private function getRequestCountStats(): array
    {
        $stats = $this->metrics['request_count'] ?? [];

        // Calculate requests per minute
        if (isset($this->metrics['last_reset'])) {
            $elapsed                      = \microtime(true) - $this->metrics['last_reset'];
            $stats['requests_per_minute'] = \round(($stats['total'] ?? 0) / ($elapsed / 60), 2);
        }

        // @var array $stats
        return $stats;
    }

    private function getStatusCodeStats(): array
    {
        $stats = [];
        $total = \array_sum($this->metrics['status_codes'] ?? []);

        foreach ($this->metrics['status_codes'] as $code => $count) {
            $stats[$code] = [
                'count'      => $count,
                'percentage' => $total > 0 ? \round(($count / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    private function getMemoryUsageStats(): array
    {
        $stats = [];

        foreach ($this->metrics['memory_usage'] as $key => $values) {
            if ($key === 'current' || $key === 'peak') {
                $stats[$key] = $this->formatBytes($values);
                continue;
            }

            if (empty($values)) {
                continue;
            }

            \sort($values);
            $count = \count($values);
            $sum   = \array_sum($values);

            $stats[$key] = [
                'count' => $count,
                'avg'   => $this->formatBytes($sum / $count),
                'min'   => $values ? $this->formatBytes(\min($values)) : $this->formatBytes(0),
                'max'   => $values ? $this->formatBytes(\max($values)) : $this->formatBytes(0),
            ];
        }

        return $stats;
    }

    private function getErrorStats(): array
    {
        $stats = [];

        foreach ($this->metrics['errors'] as $path => $methods) {
            if ($path === 'total') {
                continue;
            }

            foreach ($methods as $method => $exceptions) {
                foreach ($exceptions as $exception => $count) {
                    $stats["{$method} {$path}"][$exception] = $count;
                }
            }
        }

        $stats['total'] = $this->metrics['errors']['total'] ?? 0;

        if (!empty($this->metrics['error_response_time'])) {
            $times = $this->metrics['error_response_time'];
            \sort($times);
            $count = \count($times);
            $sum   = \array_sum($times);

            $stats['error_response_time'] = [
                'count' => $count,
                'avg'   => \round($sum / $count, 2),
                'min'   => $times ? \min($times) : 0,
                'max'   => $times ? \max($times) : 0,
            ];
        }

        return $stats;
    }

    private function getSystemStats(): array
    {
        return [
            'memory_current' => $this->formatBytes(\memory_get_usage(true)),
            'memory_peak'    => $this->formatBytes(\memory_get_peak_usage(true)),
            'uptime'         => $this->getUptime(),
            'timestamp'      => \date('Y-m-d H:i:s'),
        ];
    }

    private function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $index  = ($percentile / 100) * (\count($values) - 1);
        $sorted = $values;
        \sort($sorted);

        if (\is_int($index)) {
            return (float) ($sorted[$index] ?? 0.0);
        }

        $lower = (float) ($sorted[(int) \floor($index)] ?? 0.0);
        $upper = (float) ($sorted[(int) \ceil($index)] ?? 0.0);

        return $lower + (($upper - $lower) * ($index - \floor($index)));
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = \max($bytes, 0);
        $pow   = \floor(($bytes ? \log($bytes) : 0) / \log(1024));
        $pow   = \min($pow, \count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return \round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getUptime(): string
    {
        if (\function_exists('sys_getloadavg')) {
            $load = \sys_getloadavg();
            if (\is_array($load) && \count($load) >= 3) {
                return $load[0] . ' (1min), ' . $load[1] . ' (5min), ' . $load[2] . ' (15min)';
            }
        }

        return 'N/A';
    }

    public function resetMetrics(): void
    {
        $this->metrics = [
            'last_reset'          => \microtime(true),
            'response_time'       => [],
            'request_count'       => [],
            'status_codes'        => [],
            'memory_usage'        => [],
            'errors'              => [],
            'error_response_time' => [],
        ];
    }
}
