<?php

declare(strict_types=1);

namespace App\Application\System\Cache;

// Infrastructure Layer
use App\Infrastructure\Core\Cache\DatabaseCache;
use App\Infrastructure\Core\Database\Redis\RedisService;
// PSR Interfaces
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
// Vendor Layer
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\File\FileCache;

/**
 * Cache Application Service.
 *
 * Purges every cache store the app may have populated. Each store is
 * attempted independently — one failure never blocks the rest — and the
 * per-store outcome is reported back to the caller:
 *
 * - `psr_cache`  — the bound PSR-16 store (`Psr\SimpleCache\CacheInterface`;
 *   `ArrayCache` by default, FileCache/RedisCache in projects that rebind).
 * - `yii_cache`  — `Yiisoft\Cache\CacheInterface` (`getOrSet` wrapper); its
 *   backing PSR store is cleared. Normally the same store as `psr_cache`,
 *   but reported separately for projects that bind `Cache` to a
 *   different handler.
 * - `file_cache` — `FileCache` at `@runtime/cache`; this also clears the
 *   DB schema cache (`SchemaCache` wraps it).
 * - `redis`      — `flushDB` on the configured logical database via
 *   `RedisService`. Skipped when Redis is disabled or unreachable.
 * - `database_cache` — the `DatabaseCache` store (`@runtime/cache/db`
 *   by default, RedisCache when `app.cache.driver` resolves to redis).
 *   Skipped when `app.cache.enabled=false`.
 * - `opcache`    — `opcache_reset()` when the extension is loaded and
 *   enabled.
 * - `stat_cache` — `clearstatcache(true)` (realpath/stat). Always runs.
 *
 * Status per store: `purged`, `skipped` (store absent/disabled — not an
 * error), or `failed` (exception — message in `detail`).
 */
final readonly class CacheApplicationService
{
    public function __construct(
        private PsrCacheInterface $psrCache,
        private YiiCacheInterface $yiiCache,
        private RedisService $redis,
        private DatabaseCache $databaseCache,
        private ?FileCache $fileCache = null,
    ) {
    }

    /**
     * @return array{
     *     results: array<string, array{status: string, detail: ?string}>,
     *     duration_ms: int,
     * }
     */
    public function purgeAll(): array
    {
        $started = \microtime(true);

        $results = [
            'psr_cache'  => $this->purge(
                fn (): bool => $this->psrCache->clear(),
            ),
            'yii_cache'  => $this->purge(
                fn (): bool => $this->yiiCache->psr()->clear(),
            ),
            'file_cache' => $this->fileCache === null
                ? self::skipped('FileCache is not bound')
                : $this->purge(
                    fn (): bool => $this->fileCache->clear(),
                ),
            'redis'          => $this->purgeRedis(),
            'database_cache' => !$this->databaseCache->isEnabled()
                ? self::skipped('app.cache.enabled=false')
                : $this->purge(
                    fn (): bool => $this->databaseCache->clear(),
                ),
            'opcache'    => $this->purgeOpcache(),
            'stat_cache' => $this->purge(static function (): bool {
                \clearstatcache(true);

                return true;
            }),
        ];

        return [
            'results'     => $results,
            'duration_ms' => (int) \round((\microtime(true) - $started) * 1000),
        ];
    }

    /**
     * @return array{status: string, detail: ?string}
     */
    private function purgeRedis(): array
    {
        $client = $this->redis->isEnabled() ? $this->redis->getClient() : null;

        if ($client === null) {
            return self::skipped('redis disabled or unreachable');
        }

        return $this->purge(
            static fn (): bool => $client->flushDB(),
        );
    }

    /**
     * @return array{status: string, detail: ?string}
     */
    private function purgeOpcache(): array
    {
        if (!\function_exists('opcache_reset')) {
            return self::skipped('Zend OPcache extension not loaded');
        }

        $status = @\opcache_get_status(false);

        if (!\is_array($status) || ($status['opcache_enabled'] ?? false) !== true) {
            return self::skipped('opcache disabled');
        }

        return $this->purge(
            static fn (): bool => \opcache_reset(),
        );
    }

    /**
     * @return array{status: string, detail: ?string}
     */
    private function purge(callable $purge): array
    {
        try {
            $ok = $purge();
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'detail' => $e->getMessage()];
        }

        return $ok === false
            ? ['status' => 'failed', 'detail' => 'handler returned false']
            : self::purged();
    }

    /**
     * @return array{status: string, detail: ?string}
     */
    private static function purged(): array
    {
        return ['status' => 'purged', 'detail' => null];
    }

    /**
     * @return array{status: string, detail: ?string}
     */
    private static function skipped(string $reason): array
    {
        return ['status' => 'skipped', 'detail' => $reason];
    }
}
