# System Module

## Cache Purge — clear semua cache yang tergenerate

`POST /v1/system/cache/purge` membersihkan semua store cache yang mungkin
terisi oleh aplikasi, dalam satu request. Berguna setelah deploy, saat
mengubah konfigurasi/schema DB, atau untuk memastikan tidak ada data
stale — tanpa perlu menghapus `runtime/cache` manual atau me-restart
server.

### Store yang dibersihkan

Setiap store dicoba secara independen — satu kegagalan tidak menghentikan
yang lain — dan hasilnya dilaporkan per store di `data.results`.

| Key | Store | Keterangan |
|---|---|---|
| `psr_cache` | `Psr\SimpleCache\CacheInterface` | Binding PSR-16 project — default `ArrayCache`; bila project rebind ke FileCache/RedisCache, itulah yang di-clear. |
| `yii_cache` | `Yiisoft\Cache\CacheInterface` | Wrapper `getOrSet` — store PSR di baliknya di-clear (umumnya store yang sama dengan `psr_cache`, dilaporkan terpisah untuk project yang mem-bind `Cache` ke handler berbeda). |
| `file_cache` | `FileCache` | Isi `@runtime/cache` — **termasuk DB schema cache** (`SchemaCache` membungkus FileCache). |
| `redis` | `RedisService` | `flushDB` pada logical database yang dikonfigurasi (`redis.default.*`). Di-skip bila `redis.default.enabled=false` atau Redis tidak terjangkau. |
| `database_cache` | `DatabaseCache` | Store cache data DB (`@runtime/cache/db` default; `RedisCache` bila `app.cache.driver` resolve ke redis). Di-skip bila `app.cache.enabled=false`. |
| `opcache` | Zend OPcache | `opcache_reset()` — di-skip bila extension tidak ter-load atau disabled. |
| `stat_cache` | realpath/stat | `clearstatcache(true)` — selalu dijalankan. |

Status per store:

- `purged` — berhasil dibersihkan.
- `skipped` — store tidak terpasang/disabled (bukan error); alasan di `detail`.
- `failed` — exception saat purge; pesan error di `detail`. Endpoint
  tetap return 200 — periksa `detail` untuk store yang gagal.

### Endpoint

| Method | Path | Permission | Keterangan |
|---|---|---|---|
| POST | `/v1/system/cache/purge` | `system.cache.purge` | Purge semua store cache |

Tidak ada request body.

Response:

```json
{
  "success": true,
  "message": "Cache has been cleared successfully",
  "data": {
    "results": {
      "psr_cache":  { "status": "purged",  "detail": null },
      "yii_cache":  { "status": "purged",  "detail": null },
      "file_cache": { "status": "purged",  "detail": null },
      "redis":      { "status": "skipped", "detail": "redis disabled or unreachable" },
      "opcache":    { "status": "skipped", "detail": "Zend OPcache extension not loaded" },
      "stat_cache": { "status": "purged",  "detail": null }
    },
    "duration_ms": 1
  }
}
```

### Catatan

- **`flushDB` Redis membersihkan seluruh logical database** yang dipakai
  `RedisService` (`redis.default.db`) — termasuk key non-cache bila ada
  yang menaruh data lain di database yang sama. Pisahkan database Redis
  untuk keperluan lain bila ingin purge terbatas pada cache.
- Endpoint tetap di balik JWT/service token + permission
  `system.cache.purge` — pertimbangkan membatasinya ke admin di
  `config/common/access.php` untuk production.
- Tidak ada cache HTTP/response yang di-clear (app tidak memakai
  response cache); `rate_limits` disimpan di tabel DB, bukan cache, dan
  sengaja tidak disentuh.

### Struktur kode

```
src/
├── Api/V1/System/Cache/Action/CachePurgeAction.php     # POST /v1/system/cache/purge
└── Application/System/Cache/CacheApplicationService.php # purgeAll() per-store + report
```

Tidak ada layer Domain — operasi ini murni orkestrasi infrastructure,
tidak punya entity/business state. Untuk cache data aplikasi (bukan
purge), lihat `DatabaseCache` di `docs-guide/27-database-cache.md`.
