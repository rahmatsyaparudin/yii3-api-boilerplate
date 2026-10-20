<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Database\Redis\RedisService;

/** @var array $params */
$redis = $params['yiisoft/cache-redis'] ?? [];

// Core Redis service binding. Project-owned Redis bindings (services, cache,
// queue, or optional repository override) are merged from config/common/redis.php.
// Redis is normally a side service, but you may bind a repository interface here
// if a specific domain should use Redis as its storage. Bindings in redis.php
// are loaded after common/repository.php, so they will override SQL defaults.
$projectBindings = \dirname(__DIR__) . '/redis.php';
$project         = \file_exists($projectBindings) ? require $projectBindings : [];

$enabled = ($redis['enabled'] ?? false) && \extension_loaded('redis');

if ($enabled) {
    return \array_merge([
        RedisService::class => [
            'class'         => RedisService::class,
            '__construct()' => [
                'host'     => $redis['host'] ?? '127.0.0.1',
                'port'     => (int) ($redis['port'] ?? 6379),
                'database' => (int) ($redis['database'] ?? 0),
                'password' => $redis['password'] ?? null,
                'enabled'  => true,
            ],
        ],
    ], $project);
}

return \array_merge([
    RedisService::class => [
        'class'         => RedisService::class,
        '__construct()' => [
            'enabled' => false,
        ],
    ],
], $project);
