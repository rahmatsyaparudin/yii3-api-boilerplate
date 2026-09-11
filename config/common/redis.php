<?php

declare(strict_types=1);

/**
 * Redis repository bindings (project-owned).
 *
 * Map each domain repository interface to its Redis implementation here.
 * This file is merged into config/common/di/db-redis.php, so anything
 * you add ends up in the "di" config group automatically.
 *
 * Examples:
 *
 *   // Simple interface -> implementation binding
 *   UserRepositoryInterface::class => RedisUserRepository::class,
 *
 *   // Binding with constructor arguments and method injection
 *   ProductRepositoryInterface::class => [
 *       'class'                  => RedisProductRepository::class,
 *       'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
 *       '__construct()'          => [
 *           'prefix' => 'product',
 *       ],
 *   ],
 */

// Domain Layer
use App\Domain\Example\ExampleRepositoryInterface;
// Infrastructure Layer
use App\Infrastructure\Core\Database\Redis\RedisExampleRepository;

return [
    ExampleRepositoryInterface::class => RedisExampleRepository::class,
];
