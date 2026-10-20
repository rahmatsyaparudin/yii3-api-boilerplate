# Checklist Setelah Membuat Module Baru

Guide ini merangkum apa yang sudah otomatis dibuat oleh `skeleton-generate-module` dan langkah-langkah manual yang masih harus dilakukan sampai modul baru benar-benar siap dipakai.

## 1. Generate Modul

```bash
# via composer script
composer skeleton:generate-module -- --module=Product --table=product_management

# atau langsung
php scripts/skeleton-generate-module.php --module=Product --table=product_management

# migrasi di koneksi non-default (blok env db.<nama>.*)
php scripts/skeleton-generate-module.php --module=AuditLog --db=audit
```

## 2. Yang Sudah Otomatis

Generator membuat file dan mengupdate konfigurasi berikut — tidak perlu disentuh manual:

| Item | Lokasi |
|---|---|
| Action + InputValidator | `src/Api/V1/<Module>/` |
| Application service, Command, DTO | `src/Application/<Module>/` |
| Entity, repository interface, domain service | `src/Domain/<Module>/` |
| Implementasi repository | `src/Infrastructure/Common/Persistence/<Module>/` |
| File migrasi | `src/Migration/<Module>/` |
| Seeder + fixture | `src/Seeder/` |
| Route `/v1/<module>/*` | `config/common/routes.php` |
| Permission `<module>.*` | `config/common/access.php` |
| Binding repository | `config/common/repository.php` |
| Mapping migrasi | `config/common/migration.php` (`moduleConnections`) |
| DI seeder | `config/common/di/seed.php` |

## 3. Checklist Manual

Urut dari yang wajib sampai opsional.

### 3.1 Sesuaikan migrasi SEBELUM dijalankan

File migrasi di `src/Migration/<Module>/` masih menyalin kolom dari modul Example (`name`, `status`, `detail_info`, `sync_mdb`, `lock_version`, `deleted_at`, dst.). Edit kolom, tipe data, index, dan foreign key sesuai kebutuhan bisnis **sebelum** migrasi dijalankan:

```bash
./yii migrate:module <module>
```

> Tanpa mapping di `moduleConnections` command akan gagal — generator sudah mendaftarkannya otomatis (default koneksi `default`, atau nilai `--db`).

### 3.2 Sesuaikan aturan validasi

`src/Api/V1/<Module>/Validation/<Module>InputValidator.php` masih memakai aturan contoh (`name` required + unique, `status` in enum, `lock_version` saat update). Sesuaikan per `ValidationContext` (`CREATE`, `UPDATE`, `DELETE`, `RESTORE`) dengan field modulmu.

### 3.3 Review permission di `config/common/access.php`

Rule yang di-generate menyalin closure role milik Example (`$isKasir`, `$isSuperAdmin`, dst.). Sesuaikan siapa yang boleh mengakses tiap permission `<module>.index/.view/.data/.create/.update/.delete/.restore`.

### 3.4 Sesuaikan entity & business logic

- `src/Domain/<Module>/Entity/<Module>.php` — tambah/ubah field domain, factory method `create()`, guard status awal.
- `src/Domain/<Module>/Service/<Module>DomainService.php` — business rule (unique check, dependency check, dst.).
- `src/Application/<Module>/` — Command dan `Dto/<Module>Response.php` agar field request/response sesuai.

### 3.5 detail_info (opsional)

Kalau modul butuh struktur `detail_info` khusus (misal embed data parent seperti AnotherExample), buat factory custom sendiri — lihat `AnotherExampleDetailInfoFactory` sebagai referensi. Kalau cukup `DetailInfo` generik, tidak ada yang perlu diubah.

### 3.6 Sesuaikan seeder (opsional, dev only)

Edit `src/Seeder/Fixtures/<module>.yaml` agar field fixture cocok dengan entity, lalu:

```bash
./yii seed --module=<module>          # hanya jalan saat APP_ENV=dev
```

### 3.7 Tambah translation key (opsional)

Pesan error/success custom modul masuk ke `resources/messages/{en,id}/app.php` (file project — tidak pernah ditimpa `skeleton:update`). Jangan taruh di `error.php`/`success.php`/`validation.php` karena itu milik skeleton.

### 3.8 Optimistic lock (opsional)

Entity hasil generate sudah membawa `LockVersion`. Kalau modul ini tidak butuh optimistic locking, tambahkan nama validatornya:

```bash
app.optimistic_lock.disabled.values=["<module>"]
```

### 3.9 Public path (opsional)

Endpoint hasil generate dilindungi JWT secara default. Kalau ada endpoint yang harus publik, tambahkan path-nya ke `app.jwt.publicPaths` di `.env`.

### 3.10 Test

Tambahkan test minimal:

- `tests/Unit/` — entity/domain service
- `tests/Api/` — endpoint create/list/view/update/delete

### 3.11 Postman collection

Tambahkan request modul ke `docs-guide/Yii3-API.postman_collection.json` beserta deskripsinya, agar ikut tampil di viewer dokumentasi.

## 4. Verifikasi

```bash
# lint file yang berubah
php -l src/Api/V1/<Module>/Action/<Module>CreateAction.php

# quality check
php quality quality:check

# smoke test endpoint
curl -X POST http://localhost:8080/v1/<module>/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"name": "Test", "status": 1}'
```
