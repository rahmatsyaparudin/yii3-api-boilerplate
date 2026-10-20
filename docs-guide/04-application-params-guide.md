# Panduan Application Parameters

## 📋 Gambaran Umum

Application parameters menyediakan manajemen konfigurasi terpusat untuk aplikasi Yii3 API. Ada dua bentuk:

1. **`App\Shared\ApplicationParams`** — value object readonly kecil yang mengekspos info dasar aplikasi (name, version, language, environment) ke service dan action.
2. **Params group di `config/common/params.php`** — kumpulan yang lebih besar berisi grup konfigurasi `app/*` dan vendor yang diisi dari `.env`, dikonsumsi terutama di dalam definisi DI via `$params`.

Komponen ini memastikan akses parameter yang konsisten dan type safety di seluruh aplikasi.

---

## 🏗️ Arsitektur

### Prinsip Desain
- **Centralization**: Single source of truth — `config/common/params.php` memetakan variabel `.env` ke params group
- **Type Safety**: Pengetikan kuat dengan properti readonly PHP 8+ (`ApplicationParams`)
- **Flexibility**: Override spesifik environment via `config/environments/{dev,test,prod}/params.php`
- **Performance**: Akses parameter efisien dengan overhead minimal

---

## 📁 Komponen: ApplicationParams

`src/Shared/ApplicationParams.php` adalah class `final readonly` yang di-wire di `config/common/di/application.php`:

```php
namespace App\Shared;

final readonly class ApplicationParams
{
    public function __construct(
        public string $name = 'My Project',
        public string $version = '1.0',
        public string $language = 'en',
        public ?string $environment = null,
    ) {
    }
}
```

### Definisi DI

```php
// config/common/di/application.php
$env = $_ENV['APP_ENV'] ?? 'prod';
$environment = null;
if ($env === 'dev' || $env === 'development') {
    $environment = 'development';
}

return [
    ApplicationParams::class => [
        'class' => ApplicationParams::class,
        '__construct()' => [
            'name'        => $params['application']['name'] ?? 'My Project',
            'version'     => $params['application']['version'] ?? '1.0',
            'language'    => $params['application']['language'] ?? 'en',
            'environment' => $environment,
        ],
    ],
];
```

`$params['application']` berasal dari `config/common/application.php`, yang membaca `app.config.name`, `app.config.version` dan `app.config.language` dari `.env`.

### Contoh Penggunaan

```php
// src/Api/IndexAction.php
final class IndexAction
{
    public function __invoke(
        ResponseFactory $responseFactory,
        ApplicationParams $applicationParams,
    ): ResponseInterface {
        $data = [
            'name'     => $applicationParams->name,
            'version'  => $applicationParams->version,
            'language' => $applicationParams->language,
        ];

        if ($applicationParams->environment !== null) {
            $data['environment'] = $applicationParams->environment;
        }

        return $responseFactory->success($data);
    }
}
```

---

## ⚙️ Params Group (`config/common/params.php`)

Alih-alih API `ApplicationParams` yang gemuk, proyek ini mengekspos params group spesifik domain. File DI dan closure config membacanya via `$params['group']['key']`:

| Group | Keys | Sumber (.env) |
|-------|------|---------------|
| `application` | `name`, `version`, `language` | `app.config.*` |
| `yiisoft/aliases` | `aliases` (from `common/aliases.php`) | — |
| `yiisoft/translator` | `locale`, `fallbackLocale` | `app.config.language` |
| `yiisoft/db` | `driver`, `host`, `port`, `name`, `user`, `password`, `charset` | `db.default.*` |
| `yiisoft/cache-redis` | `enabled`, `host`, `port`, `database`, `password` | `redis.default.*` |
| `yiisoft/db-migration` | `newMigrationNamespace`, `sourceNamespaces` | — (`App\Migration`) |
| `mongodb/mongodb` | `enabled`, `dsn`, `database`, `connectTimeoutMS`, `socketTimeoutMS`, `readPreference` | `db.mongodb.*` |
| `app/config` | `code`, `name`, `version`, `language`, `allow_god_mode` | `app.config.*` |
| `app/optimisticLock` | `enabled`, `disabledValues` | `app.optimistic_lock.*` |
| `app/pagination` | `defaultPageSize`, `maxPageSize` | `app.pagination.*` |
| `app/rateLimit` | `maxRequests`, `windowSize` | `app.rateLimit.*` |
| `app/hsts` | `maxAge`, `includeSubDomains`, `preload` | `app.hsts.*` |
| `app/time` | `timezone` | `app.time.timezone` |
| `app/cors` | `maxAge`, `allowCredentials`, `allowedOrigins`, `allowedMethods`, `allowedHeaders`, `exposedHeaders` | `app.cors.*` |
| `app/jwt` | `secret`, `algorithm`, `issuer`, `audience`, `publicPaths` | `app.jwt.*` |
| `app/trusted_hosts` | `allowedHosts` | `app.trusted_hosts.allowedHosts` |
| `app/secureHeaders` | `csp`, `permissions`, `custom` | (hardcoded) |
| `app/monitoring` | `provider`, `log_file`, `request_id_header`, `logging`, `metrics`, `error_monitoring` | (hardcoded) |
| `app/enter-md` | `baseUrl`, `secret`, `algorithm`, `serviceUsername` | `app.md.*` |

Nilai env ber-encode JSON (mis. `app.cors.allowedOrigins=["http://example.com:3000"]`) di-decode di bagian atas `params.php`.

### Environment Override

Override per environment berada di `config/environments/{dev,test,prod}/params.php` (mis. `dev/params.php` mengatur `traceLink` untuk link IDE di error renderer). File-file ini digabung oleh `config/configuration.php` berdasarkan `APP_ENV` (nilai valid: `dev`, `test`, `prod` — ditegakkan oleh `App\Environment`).

### Deteksi Environment

```php
use App\Environment;

Environment::isDev();
Environment::isTest();
Environment::isProd();
Environment::appDebug();
```

---

## 🔧 Contoh Integrasi

### Penggunaan di Action
```php
final class IndexAction
{
    public function __invoke(
        ResponseFactory $responseFactory,
        ApplicationParams $applicationParams,
    ): ResponseInterface {
        return $responseFactory->success([
            'name'    => $applicationParams->name,
            'version' => $applicationParams->version,
        ]);
    }
}
```

### Penggunaan di Definisi DI
```php
// Params groups are consumed inside DI definitions, not via ApplicationParams
return [
    HstsMiddleware::class => static function () use ($params) {
        $hsts = $params['app/hsts'] ?? [];

        return new HstsMiddleware(
            maxAge: (int) ($hsts['maxAge'] ?? 31536000),
            includeSubDomains: (bool) ($hsts['includeSubDomains'] ?? true),
            preload: (bool) ($hsts['preload'] ?? false)
        );
    },
];
```

### Penggunaan di Service
```php
final class ExampleService
{
    public function __construct(
        private ApplicationParams $params,
        private LoggerInterface $logger,
    ) {
    }

    public function report(): void
    {
        $this->logger->info('Running ' . $this->params->name . ' v' . $this->params->version);
    }
}
```

---

## 🚀 Praktik Terbaik

### Akses Parameter
```php
// ✅ Inject ApplicationParams for app info (name/version/language/environment)
$version = $this->params->version;

// ✅ Read params groups inside DI definitions / config closures
$jwtSecret = $params['app/jwt']['secret'] ?? '';

// ✅ Check the environment through App\Environment
if (Environment::isDev()) {
    // dev-only behaviour
}
```

### Konfigurasi
```php
// ✅ Environment-based: .env → params.php → $params groups
// ✅ Per-environment overrides: config/environments/{dev,test,prod}/params.php
```

### Menambahkan Params Group Baru
```php
// 1. Map env vars in config/common/params.php
'app/my-feature' => [
    'enabled' => \filter_var($_ENV['app.my_feature.enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
],

// 2. Consume it in a DI definition
MyMiddleware::class => static fn () => new MyMiddleware(
    enabled: $params['app/my-feature']['enabled'] ?? false,
),
```

---

## 📊 Pertimbangan Performa

- **Initialization**: `params.php` dievaluasi sekali oleh yiisoft/config dan digabung ke dalam container
- **Penggunaan Memori**: `ApplicationParams` adalah instance readonly tunggal yang dibagikan via DI
- **Kecepatan Akses**: Akses properti langsung dengan overhead minimal
- **Konfigurasi**: Variabel environment dimuat sekali saat startup (`src/bootstrap.php` memuat `.env`)

---

## 🎯 Ringkasan

Application parameters menyediakan manajemen konfigurasi terpusat dan type-safe dengan manfaat utama:

- **🎯 Centralization**: Single source of truth di `config/common/params.php`
- **🛡️ Type Safety**: Properti readonly `App\Shared\ApplicationParams` mencegah kesalahan konfigurasi
- **🔄 Immutability**: Properti readonly memastikan konsistensi
- **🌐 Environment Support**: `APP_ENV` + `environments/*/params.php` untuk konfigurasi spesifik environment
- **📦 Integration**: Integrasi DI yang mulus melalui `$params` group
- **⚡ Performance**: Akses cepat dengan overhead minimal

Dengan mengikuti pola-pola ini, Anda dapat membangun manajemen konfigurasi yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
