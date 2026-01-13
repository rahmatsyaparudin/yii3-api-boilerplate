<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

interface MonitoringServiceInterface
{
    public function logRequest(array $data): void;

    public function logError(\Throwable $exception, array $context = []): void;

    public function incrementMetric(string $name, float $value = 1.0): void;

    public function setGauge(string $name, float $value): void;

    public function getMetrics(): array;
}
