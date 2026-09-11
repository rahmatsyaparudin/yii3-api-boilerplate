<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Database\Redis\RedisService;

/** @var array $params */

// Core Redis service binding. Project-owned Redis bindings (services, cache,
// queue, or optional repository override) are merged from config/common/redis.php.
// Redis is normally a side service, but you may bind a repository interface here
// if a specific domain should use Redis as its storage. Bindings in redis.php
// are loaded after common/repository.php, so they will override SQL defaults.
$projectBindings = \dirname(__DIR__) . '/redis.php';

return array_merge([
    RedisService::class => [
        '__construct()' => [
            'host' => $_ENV['redis.default.host'],
            'port' => (int) $_ENV['redis.default.port'],
        ],
    ],
], file_exists($projectBindings) ? require $projectBindings : []);
