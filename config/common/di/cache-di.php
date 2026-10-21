<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Cache\DatabaseCache;
// PSR Interfaces
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
// Vendor Layer
use Predis\Client as PredisClient;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Cache\Redis\RedisCache;
use Yiisoft\Definitions\DynamicReference;

/** @var array $params */
$cache = $params['app/cache'] ?? [];
$redis = $params['yiisoft/cache-redis'] ?? [];

// Store selection, driven by `app.cache.driver`:
//   auto  — RedisCache when `redis.default.enabled=true` (and predis is
//           installed), FileCache @runtime/cache/db otherwise
//   redis — RedisCache, always
//   file  — FileCache @runtime/cache/db
//   psr   — whatever PSR-16 store the project binds (ArrayCache default)
// All of them implement Psr\SimpleCache\CacheInterface — switching
// backend is an .env change, never a code change.
$driver  = (string) ($cache['driver'] ?? 'auto');
$redisOn = \filter_var($redis['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)
    && \class_exists(PredisClient::class)
    && \interface_exists(\Predis\ClientInterface::class);

$predisParameters = static function (array $redis): array {
    $parameters = [
        'scheme'  => 'tcp',
        'host'    => (string) ($redis['host'] ?? '127.0.0.1'),
        'port'    => (int) ($redis['port'] ?? 6379),
        // Short connect timeout keeps the `auto` probe (and a dead
        // Redis under driver=redis) cheap instead of hanging requests.
        'timeout' => (float) ($redis['timeout'] ?? 1.0),
    ];

    if ((int) ($redis['database'] ?? 0) > 0) {
        $parameters['database'] = (int) $redis['database'];
    }

    if (!empty($redis['password'])) {
        $parameters['password'] = (string) $redis['password'];
    }

    return $parameters;
};

return [
    DatabaseCache::class => [
        'class'         => DatabaseCache::class,
        '__construct()' => [
            'store' => DynamicReference::to(
                static function (Aliases $aliases, PsrCacheInterface $psrStore) use (
                    $driver,
                    $redis,
                    $redisOn,
                    $predisParameters
                ): PsrCacheInterface {
                    if ($driver === 'redis') {
                        return new RedisCache(
                            new PredisClient($predisParameters($redis))
                        );
                    }

                    if ($driver === 'auto' && $redisOn) {
                        // Probe once per request: Redis flag on but the
                        // server unreachable → fall back to FileCache
                        // instead of failing every cache call.
                        try {
                            $client = new PredisClient($predisParameters($redis));
                            $client->connect();

                            return new RedisCache($client);
                        } catch (\Throwable) {
                            // fall through to FileCache
                        }
                    }

                    if ($driver === 'psr') {
                        return $psrStore;
                    }

                    // 'file', 'auto' fallback, and any unknown driver.
                    return new FileCache(
                        $aliases->get('@runtime/cache/db')
                    );
                }
            ),
            'enabled'    => \filter_var($cache['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'defaultTtl' => (int) ($cache['defaultTtl'] ?? 3600),
            'keyPrefix'  => (string) ($cache['keyPrefix'] ?? 'app'),
        ],
    ],
];
