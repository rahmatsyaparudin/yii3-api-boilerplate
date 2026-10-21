# DatabaseCache

## Managed cache untuk data yang dibaca dari database

`App\Infrastructure\Core\Cache\DatabaseCache` adalah Core component
(ikut `skeleton:update`) untuk me-manage cache hasil baca database —
generate, renew, refresh, invalidate — tanpa perlu wiring apa pun di
project consumer. Cukup inject dan atur lewat `.env`.

### Cara pakai

```php
use App\Infrastructure\Core\Cache\DatabaseCache;

final class ProductApplicationService
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private DatabaseCache $cache,
    ) {
    }

    public function view(int $id): array
    {
        // Cache hit → skip query; miss → jalankan callback lalu simpan.
        return $this->cache->remember(
            key: "product:{$id}",
            callback: fn () => $this->repository->findById($id)?->toArray(),
            ttl: 600,
        );
    }

    public function update(int $id, array $input): void
    {
        // ... update repository ...

        // Invalidate — request berikutnya membaca ulang dari DB.
        $this->cache->forget(key: "product:{$id}");

        // Atau langsung regenerate sekarang.
        $this->cache->refresh(
            key: "product:{$id}",
            callback: fn () => $this->repository->findById($id)?->toArray(),
        );
    }
}
```

### API

| Method | Keterangan |
|---|---|
| `remember(key, callback, ttl?)` | Get-or-set. `ttl` null → `default_ttl`; `ttl <= 0` → selamanya. |
| `rememberForever(key, callback)` | `remember` dengan `ttl=0` (tanpa expiry). |
| `refresh(key, callback, ttl?)` | Paksa regenerate + simpan ulang. |
| `get(key, default?)` | Ambil nilai; miss/error → `default`. |
| `set(key, value, ttl?)` | Simpan nilai. |
| `has(key)` | Cek keberadaan key. |
| `forget(key)` | Hapus satu key. |
| `forgetMultiple(keys)` | Hapus banyak key (loop `forget`). |
| `clear()` | Flush seluruh store DatabaseCache. |
| `isEnabled()` | `app.cache.enabled`. |

Catatan:

- **Nilai di-JSON-encode** — cocok untuk array, scalar, dan output DTO
  `toArray()`. Yang dibaca kembali adalah array, bukan object aslinya.
- **Key otomatis diberi prefix** `key_prefix` dan karakter reserved
  PSR-16 (`{}()/\@:`) dinormalisasi menjadi `.` — `product:5` tersimpan
  sebagai `app.product.5` (default prefix `app`).
- **Fail-safe**: semua operasi store dibungkus try/catch. Cache mati =
  `remember`/`refresh` langsung jalankan callback, `get` return default —
  request tidak pernah gagal karena cache.
- `enabled=false` → semua operasi pass-through (callback langsung,
  `get` → default, mutator → `false`).

### Konfigurasi (`.env` / `app/cache` params)

| Key | Default | Keterangan |
|---|---|---|
| `app.cache.enabled` | `true` | `false` → semua operasi no-op/pass-through |
| `app.cache.driver` | `auto` | `auto` \| `redis` \| `file` \| `psr` |
| `app.cache.default_ttl` | `3600` | Detik; dipakai saat `ttl` tidak diberikan |
| `app.cache.key_prefix` | `app` | Prefix semua key |

Driver:

| `driver` | Store yang dipakai |
|---|---|
| `auto` | `redis.default.enabled=true` **dan** Redis bisa di-connect (probe `Predis`) → `RedisCache`; selain itu → `FileCache` di `@runtime/cache/db`. |
| `redis` | `Yiisoft\Cache\Redis\RedisCache` (via `Predis\Client` dari `redis.default.*`). Paksa — tanpa probe. |
| `file` | `FileCache` di `@runtime/cache/db` (terpisah dari schema cache di `@runtime/cache`). |
| `psr` | Store `Psr\SimpleCache\CacheInterface` yang di-bind project (default vendor: `ArrayCache` — in-memory per request, umumnya tidak berguna untuk cache antar-request). |

Semua store berwujud `Psr\SimpleCache\CacheInterface` — pindah backend
hanya mengubah `.env`, tanpa mengubah kode.

Override binding secara total (misalnya Redis dengan parameter custom)
bisa dilakukan di `config/common/redis.php` atau file DI project:
`DatabaseCache::class` adalah PSR-16-agnostic.

```php
// config/common/redis.php
DatabaseCache::class => static fn (Aliases $aliases) => new DatabaseCache(
    store: new FileCache($aliases->get('@runtime/cache/db')),
    enabled: true,
    defaultTtl: 7200,
    keyPrefix: 'tenant1',
),
```

### Terkait

- `POST /v1/system/cache/purge` — report `database_cache` di respons
  membersihkan store ini secara eksplisit (selain store global lainnya).
- `redis.default.*` — koneksi Redis yang dipakai `driver=auto|redis`
  (client `Predis`, independen dari `RedisService` yang pakai ext redis).
