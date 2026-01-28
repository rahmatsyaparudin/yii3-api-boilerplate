<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Monitoring\CustomMonitoringService;
use App\Infrastructure\Monitoring\MonitoringServiceInterface;

// Vendor Layer
use Yiisoft\Di\Container;

return [
    MonitoringServiceInterface::class => static function (Container $container) use ($params) {
        $monitoringConfig = $params['app/monitoring'] ?? [];

        return new CustomMonitoringService([
            'log_file' => $monitoringConfig['log_file'] ?? 'runtime/logs/api.log',
        ]);
    },
];
