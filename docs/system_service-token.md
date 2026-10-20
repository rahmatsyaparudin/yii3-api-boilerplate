# System Module

## Service Token — bearer auth untuk service eksternal

`service_tokens` adalah alternatif JWT untuk integrasi machine-to-machine
(POS, script internal, dsb). Perilakunya SSO-like: token yang diterbitkan
langsung bisa dipakai sebagai `Authorization: Bearer <token>` — request
otomatis berjalan atas nama user penerbit (permissions, audit, changelog
`detail_info` semua melihat user tersebut).

### Cara kerja

1. `POST /v1/system/service-token/create` mengeluarkan secret acak
   (64 hex chars). **Plaintext hanya ditampilkan sekali** di
   `data.token` — yang disimpan di DB hanya `SHA-256(token)`.
2. Request selanjutnya: `Authorization: Bearer <plaintext>`.
   `ServiceTokenAuthMiddleware` — entry **terpisah** yang berjalan
   *sebelum* `JwtMiddleware` — mendeteksi token opaque (bukan format
   JWT `x.y.z`), melakukan lookup hash → jika ACTIVE dan belum
   `expires_at`, request berlanjut dengan `Actor` milik penerbit,
   `last_used_at` di-stamp, dan attribute `actor` diset agar
   `JwtMiddleware` skip.
3. Token JWT tetap diproses normal oleh `JwtMiddleware` (entry
   terpisah, tidak diubah): bearer berbentuk JWT, token yang tidak
   ditemukan, atau tanpa header jatuh ke validasi JWT seperti biasa.
   **Opt-in**: fitur dikontrol `app.service_token.enabled` di `.env`
   (default `false`) — saat off, `ServiceTokenAuthMiddleware` langsung
   skip ke `JwtMiddleware` tanpa query DB sama sekali.
4. **Revoke instan**: tidak ada caching lookup — `DELETE` atau update
   `status` berlaku pada request berikutnya.
5. **Fail-safe saat fitur belum terinstall**: kalau tabel
   `service_tokens` belum ada (`migrate:module serviceToken` belum
   dijalankan) atau DB error, `authenticate()` menangkap exception DB
   dan return `null` → request fallback ke JWT. Tidak ada 500; endpoint
   lain jalan normal. Hanya endpoint manajemen
   `/v1/system/service-token/*` yang error — wajar, fiturnya memang
   belum terinstall. Dan saat `app.service_token.enabled=false`,
   middleware bahkan tidak menyentuh DB.

### Penggunaan — pengganti SSO untuk sistem tanpa akun SSO

**Masalahnya:** auth utama omnichannel pakai JWT dari SSO — artinya
setiap klien harus punya akun SSO dan lewat flow login (username +
password → token JWT). Sistem eksternal seperti POS kasir, script cron,
atau service pihak ketiga **tidak punya akun SSO** — mereka tidak bisa
login sebagai user, hanya butuh "kunci API" yang berjalan atas nama
satu user internal.

**Solusinya:** admin/user internal login sekali (SSO/JWT normal) lalu
menerbitkan service token lewat `POST /v1/system/service-token/create`.
Token itu diserahkan ke sistem eksternal dan disimpan di config/env
mereka — selamanya dipakai sebagai `Authorization: Bearer <token>`,
tanpa login, tanpa refresh.

```
Admin (SSO/JWT)                Omnichannel                    Sistem eksternal
      │                              │                                │
      │── POST /service-token/create │                                │
      │   {name:"POS Cabang A"}│                                │
      │◄─────────── data.token ──────│                                │
      │        (plaintext, sekali)   │                                │
      │                              │                                │
      │────── serahkan token ────────┼───────────────────────────────►│
      │                              │                                │
      │                              │◄── GET /v1/report/... ─────────│
      │                              │     Authorization: Bearer xyz   │
      │                              │     → jalan sebagai user admin  │
      │                              │── response ────────────────────►│
```

**Perbandingan dengan SSO/JWT:**

| | SSO / JWT | Service Token |
|---|---|---|
| Siapa pakai | Manusia (login UI/SSO) | Sistem/script eksternal |
| Perlu akun SSO | Ya | Tidak — cukup token |
| Login flow | Username + password → JWT | Tidak ada — bearer langsung |
| Expiry | Pendek (perlu refresh/login ulang) | `expires_at` opsional, bisa permanen |
| Identitas request | User yg login | User penerbit token (`user_id`/`username`) |
| Revoke | Tunggu JWT expired | Instan — soft delete row |
| Rotasi | Login ulang | `PUT {id}` + `regenerate: true` |

**Contoh pemakaian di sisi eksternal:**

```bash
# POS / script cukup simpan satu env var:
OMNI_TOKEN=c9f2...<64 hex>

# lalu semua request pakai header yang sama, tanpa login:
curl -H "Authorization: Bearer $OMNI_TOKEN" \
     "{{url}}/v1/report/inventory/headquarter"
```

```php
// PHP client
$client = new GuzzleHttp\Client([
    'base_uri' => 'https://omnichannel.example.com',
    'headers'  => ['Authorization' => 'Bearer ' . $_ENV['OMNI_TOKEN']],
]);
$client->get('/v1/report/sales/headquarter');
```

**Lifecycle rekomendasi:**

- Satu token per sistem/instalasi (`username` = identitasnya, unique) —
  jangan dipakai bersama supaya `last_used_at` dan audit tetap bermakna.
- Rotasi berkala lewat `regenerate` (bearer lama mati saat itu juga —
  update config eksternal dengan `data.token` baru).
- Kalau sistem dilepas/dicurigai bocor: `DELETE {id}` — efek langsung,
  tanpa menunggu expiry.
- Terbitkan token hanya dari akun dengan scope wewenang yang memang
  dibutuhkan sistem eksternal — token mewarisi identitas penuh
  penerbitnya.

### Tabel `service_tokens`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | int PK | |
| `user_id` | int | ID user internal penerbit (diisi dari actor saat create) |
| `username` | varchar(100), **unique** | Identitas service — dikirim saat create (bukan username penerbit). Unique di semua row termasuk deleted |
| `name` | varchar(100) | Nama layanan, mis. `POS Kasir Cabang A` |
| `token` | varchar(500), unique | **SHA-256 hash**, bukan plaintext |
| `abilities` | text (JSON) | Scope izin, mis. `["read:products"]` — disimpan untuk pemakaian authorization lanjutan |
| `whitelist_host` | json | Host/IP caller yang boleh pakai token. `[]` (default) = allow all. Entry: `10.0.0.5`, `192.168.1.*`, `*.pos.local`, `*` |
| `last_used_at` | datetime NULL | Di-stamp tiap auth sukses |
| `expires_at` | datetime NULL | NULL = tidak pernah kadaluarsa |
| `status` | int | `1` active, `2` deleted (soft delete) |
| `detail_info` | json | Changelog standar (created/updated/deleted by/at) |
| `created_at` | datetime | |

### Endpoints — `/v1/system/service-token`

| Method | Path | Permission | Keterangan |
|---|---|---|---|
| POST | `/data` | `system.service-token.list` | List + pagination |
| GET | `/{id}` | `system.service-token.view` | Detail satu token |
| POST | `/create` | `system.service-token.create` | Issue token baru |
| PUT | `/{id}` | `system.service-token.update` | Update + rotate |
| DELETE | `/{id}` | `system.service-token.delete` | Revoke (soft delete) |

#### `POST /create`

```json
{
  "username": "pos_cabang_a",
  "name": "Aplikasi Kasir Cabang A",
  "abilities": ["read:products", "write:transactions"],
  "whitelist_host": ["103.10.20.30", "192.168.1.*"],
  "expires_at": "2026-12-31 23:59:59"
}
```

- `whitelist_host` opsional, default `[]` = allow all. Kalau diisi,
  bearer hanya bisa dipakai dari host/IP yang cocok (exact, `*` =
  semua, atau wildcard prefix/suffix seperti `192.168.1.*` /
  `*.pos.local`). Host client dideteksi dari `X-Forwarded-For` (hop
  pertama) atau `REMOTE_ADDR`; kalau host tidak terdeteksi dan
  whitelist non-empty → token ditolak (fail closed).
- `username` **wajib dan unique** — ini identitas service (bukan username
  penerbit). Duplikat → 409 `service_token.duplicate`. Berlaku di semua
  row termasuk yang deleted, jadi nama yang sama tidak bisa dibuat ulang —
  re-activate lewat `PUT {id}` + `status: 1` (atau `status: 0` inactive).
- `name` wajib (max 100). `abilities` opsional — array string atau
  JSON/CSV. `expires_at` opsional (`Y-m-d` atau `Y-m-d H:i[:s]`).
- `user_id` **tidak** dikirim — diisi dari actor yang login (penerbit).

Response (satu-satunya saat plaintext terlihat):

```json
{
  "data": {
    "id": 3,
    "user_id": 101,
    "username": "budi_santoso",
    "name": "Aplikasi Kasir Cabang A",
    "token": "c9f2...<64 hex>",
    "abilities": ["read:products", "write:transactions"],
    "expires_at": "2026-12-31 23:59:59"
  }
}
```

Simpan `data.token` — tidak bisa diambil lagi. Kalau hilang: `PUT` dengan
`regenerate: true` (rotasi) atau buat token baru.

#### `POST /data`

```json
{
  "filter": { "username": "budi", "status": 1 },
  "page": 1, "page_size": 20,
  "sort": { "by": "id", "dir": "asc" }
}
```

`filter` exact: `id`, `user_id`, `status`; LIKE: `username`, `name`.
Sortable: `id`, `user_id`, `username`, `name`, `last_used_at`,
`expires_at`, `created_at`, `status`. Hash tidak pernah muncul di
list/view.

#### `PUT /{id}`

```json
{ "username": "...", "name": "...", "abilities": [...], "expires_at": "...", "regenerate": true, "status": 1 }
```

- `username` bisa di-rename — tetap unique (duplikat → 409).
- `regenerate: true` me-rotate secret — bearer lama langsung mati,
  plaintext baru dikembalikan sekali di `data.token`.
- `status`: `1` active (re-activate), `0` inactive, `4` deleted.
- `expires_at` kosong tidak meng-clear expiry (field kosong = tidak
  berubah); untuk menghapus expiry, hapus & buat ulang token.

### Security notes

- Lookup pakai SHA-256 — kebocoran DB tidak membocorkan secret (asumsi
  entropy 256-bit, tidak perlu bcrypt/argon).
- Token opaque (bukan JWT) → middleware tahu harus lookup DB, bukan
  decode JWT.
- `abilities` saat ini metadata saja — belum dienforce per-scope;
  token berperilaku penuh sebagai user penerbit. Batasi penerbitan ke
  user yang memang boleh di-impersonate.
- Tabel `service_tokens` belum dimigrasi / DB error saat lookup →
  auth fallback ke JWT (bukan 500). Endpoint `/v1/system/service-token/*`
  sendiri tetap error karena tabelnya memang belum ada — jalankan
  `migrate:module serviceToken` untuk mengaktifkan fitur.
- Aktivasi: `app.service_token.enabled=true` di `.env` (default
  `false` → middleware skip langsung ke JWT).
