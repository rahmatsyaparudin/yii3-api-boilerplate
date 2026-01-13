<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

final class CustomMonitoringService implements MonitoringServiceInterface
{
    private array $counters = [];
    private array $gauges   = [];
    private array $logs     = [];
    private string $logFile;

    public function __construct(array $config = [])
    {
        $this->logFile = $config['log_file'] ?? 'runtime/logs/api.log';
        $this->ensureLogDirectory();
    }

    public function logRequest(array $data): void
    {
        $logEntry = [
            'timestamp' => \date('Y-m-d H:i:s'),
            'level'     => 'INFO',
            'message'   => 'HTTP Request',
            'context'   => $data,
        ];

        $this->writeLog($logEntry);
        $this->logs[] = $logEntry;
    }

    public function logError(\Throwable $exception, array $context = []): void
    {
        $logEntry = [
            'timestamp' => \date('Y-m-d H:i:s'),
            'level'     => 'ERROR',
            'message'   => $exception->getMessage(),
            'context'   => \array_merge([
                'exception' => $exception::class,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => $exception->getTraceAsString(),
            ], $context),
        ];

        $this->writeLog($logEntry);
        $this->logs[] = $logEntry;
    }

    public function incrementMetric(string $name, float $value = 1.0): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
    }

    public function setGauge(string $name, float $value): void
    {
        $this->gauges[$name] = $value;
    }

    public function getMetrics(): array
    {
        return [
            'counters' => $this->counters,
            'gauges'   => $this->gauges,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }

    public function resetMetrics(): void
    {
        $this->counters = [];
        $this->gauges   = [];
    }

    private function writeLog(array $logEntry): void
    {
        $logLine = \json_encode($logEntry) . PHP_EOL;
        \file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectory(): void
    {
        $logDir = \dirname($this->logFile);
        if (!\is_dir($logDir)) {
            \mkdir($logDir, 0o755, true);
        }
    }
}
