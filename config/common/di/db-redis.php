<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Database\Redis\RedisService;

/** @var array $params */

// Core Redis service binding. Project-owned repository bindings are
// merged from config/common/redis.php and may override these.
$projectBindings = \dirname(__DIR__) . '/redis.php';

return array_merge([
    RedisService::class => [
        '__construct()' => [
            'host' => $_ENV['redis.default.host'],
            'port' => (int) $_ENV['redis.default.port'],
        ],
    ],
], file_exists($projectBindings) ? require $projectBindings : []);
