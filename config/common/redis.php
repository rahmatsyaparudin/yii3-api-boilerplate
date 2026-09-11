<?php

declare(strict_types=1);

/**
 * Redis bindings (project-owned).
 *
 * Redis is normally used as a side service (cache, queue, session, etc.).
 * You may also use it as an alternative storage layer for a specific domain
 * by binding the repository interface here. This file is merged into
 * config/common/di/db-redis.php and is loaded AFTER common/repository.php,
 * so any repository binding here will override the SQL default.
 *
 * Examples:
 *
 *   // Optional: override one repository to use Redis
 *   // ExampleRepositoryInterface::class => RedisExampleRepository::class,
 *
 *   // Additional Redis service (cache, queue, etc.)
 *   // MyRedisCache::class => [
 *   //     'class' => MyRedisCache::class,
 *   //     '__construct()' => [
 *   //         'prefix' => 'myapp',
 *   //     ],
 *   // ],
 */

return [];
