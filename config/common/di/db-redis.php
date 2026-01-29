<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Example\ExampleRepositoryInterface;

// Infrastructure Layer
use App\Infrastructure\Database\Redis\RedisService;
use App\Infrastructure\Database\Redis\RedisExampleRepository;

return [
    RedisService::class => [
        '__construct()' => [
            'host' => $_ENV['redis.default.host'],
            'port' => (int)$_ENV['redis.default.port'],
        ],
    ],
    // Mapping Interface ke Implementasi (Penting untuk DDD)
    ExampleRepositoryInterface::class => RedisExampleRepository::class,
];