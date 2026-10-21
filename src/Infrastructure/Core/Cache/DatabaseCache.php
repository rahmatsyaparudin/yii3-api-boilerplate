<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Cache;

// PSR Interfaces
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * Database Cache — managed cache for data read from the database.
 *
 * A thin, resilient layer over any PSR-16 store (`FileCache`,
 * `RedisCache`, `ArrayCache`, …). The store is chosen by DI wiring
 * (`config/common/di/cache-di.php`) from `app.cache.driver`; the class
 * itself only speaks PSR-16.
 *
 * Behavior contract:
 * - Values are JSON-encoded, so anything `json_encode()` accepts works
 *   (arrays, scalars, DTO `toArray()` output). Read back as decoded
 *   arrays — not the original object type.
 * - Keys are namespaced with `keyPrefix` and normalized so PSR-16
 *   reserved characters (`{}()/\@:`) become `.` — `product:5` is stored
 *   as `<prefix>.product.5`.
 * - `enabled=false` (or any store failure) degrades gracefully:
 *   `remember()`/`refresh()` just run the callback, `get()` returns the
 *   default, mutators return `false`. A dead cache backend can never
 *   take the request down.
 * - `$ttl`: `null` uses `defaultTtl` seconds; `<= 0` stores forever.
 */
final readonly class DatabaseCache
{
    public function __construct(
        private PsrCacheInterface $store,
        private bool $enabled = true,
        private int $defaultTtl = 3600,
        private string $keyPrefix = 'app',
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get a value, computing and caching it on miss.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $raw = $this->storeGet($this->key($key));

        if ($raw !== null) {
            $ok    = false;
            $value = self::decode($raw, $ok);

            if ($ok) {
                return $value;
            }
        }

        return $this->refresh($key, $callback, $ttl);
    }

    /**
     * Get a value with no expiry, computing and caching it on miss.
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback, ttl: 0);
    }

    /**
     * Force (re)compute and (re)store a value.
     */
    public function refresh(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $callback();

        if ($this->enabled) {
            $this->set($key, $value, ttl: $ttl);
        }

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled) {
            return $default;
        }

        $raw = $this->storeGet($this->key($key));

        if ($raw === null) {
            return $default;
        }

        $ok    = false;
        $value = self::decode($raw, $ok);

        return $ok ? $value : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $raw = \json_encode($value);

        if ($raw === false) {
            return false;
        }

        try {
            return $this->store->set($this->key($key), $raw, $this->ttl($ttl));
        } catch (\Throwable) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return $this->store->has($this->key($key));
        } catch (\Throwable) {
            return false;
        }
    }

    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return $this->store->delete($this->key($key));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Remove several keys. PSR-16 has no pattern delete — invalidate by
     * naming keys deterministically, or `clear()` the whole store.
     */
    public function forgetMultiple(iterable $keys): bool
    {
        $ok = true;

        foreach ($keys as $key) {
            $ok = $this->forget((string) $key) && $ok;
        }

        return $ok;
    }

    /**
     * Flush the whole bound store — only the store this instance was
     * wired with (`@runtime/cache/db` by default), never other stores.
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return $this->store->clear();
        } catch (\Throwable) {
            return false;
        }
    }

    private function key(string $key): string
    {
        $key = \str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '.', $key);

        return $this->keyPrefix . '.' . $key;
    }

    private function ttl(?int $ttl): ?int
    {
        $ttl ??= $this->defaultTtl;

        return $ttl <= 0 ? null : $ttl;
    }

    private function storeGet(string $key): ?string
    {
        try {
            $value = $this->store->get($key);
        } catch (\Throwable) {
            return null;
        }

        return \is_string($value) ? $value : null;
    }

    private static function decode(string $raw, bool &$ok): mixed
    {
        $value = \json_decode($raw, true);
        $ok    = \json_last_error() === \JSON_ERROR_NONE;

        return $value;
    }
}
