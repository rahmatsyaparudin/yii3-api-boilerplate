<?php

declare(strict_types=1);

/**
 * Application infrastructure bindings (project-owned).
 *
 * Entries returned here are merged into config/common/di/infrastructure-di.php
 * and may override the core bindings. Use this file for project-specific
 * infrastructure services such as external API clients.
 *
 * Example:
 *
 *   ApiClient::class => static function () use ($params): ApiClient {
 *       $config = $params['app/api-client'] ?? [];
 *
 *       return new ApiClient(
 *           baseUrl: $config['baseUrl'] ?? 'https://api.example.com/v1',
 *           jwtSecret: $config['secret'] ?? '',
 *           algorithm: $config['algorithm'] ?? 'HS256',
 *           serviceUsername: $config['serviceUsername'] ?? 'app-service',
 *       );
 *   },
 */

return [
    // Add your project infrastructure bindings here
];
