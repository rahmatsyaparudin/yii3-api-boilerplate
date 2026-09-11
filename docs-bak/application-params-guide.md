# Application Parameters Guide

## 📋 Overview

Application parameters provide centralized configuration management for the Yii3 API application. They come in two forms:

1. **`App\Shared\ApplicationParams`** — a small readonly value object exposing basic application info (name, version, language, environment) to services and actions.
2. **Params groups in `config/common/params.php`** — the larger set of `app/*` and vendor configuration groups populated from `.env`, consumed mainly inside DI definitions via `$params`.

This component ensures consistent parameter access and type safety across the application.

---

## 🏗️ Architecture

### Design Principles
- **Centralization**: Single source of truth — `config/common/params.php` maps `.env` variables to params groups
- **Type Safety**: Strong typing with PHP 8+ readonly properties (`ApplicationParams`)
- **Flexibility**: Environment-specific overrides via `config/environments/{dev,test,prod}/params.php`
- **Performance**: Efficient parameter access with minimal overhead

---

## 📁 Component: ApplicationParams

`src/Shared/ApplicationParams.php` is a `final readonly` class wired in `config/common/di/application.php`:

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

### DI Definition

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

`$params['application']` comes from `config/common/application.php`, which reads `app.config.name`, `app.config.version` and `app.config.language` from `.env`.

### Usage Example

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

## ⚙️ Params Groups (`config/common/params.php`)

Instead of a fat `ApplicationParams` API, the project exposes domain-specific params groups. DI files and config closures read them via `$params['group']['key']`:

| Group | Keys | Source (.env) |
|-------|------|---------------|
| `application` | `name`, `version`, `language` | `app.config.*` |
| `yiisoft/aliases` | `aliases` (from `common/aliases.php`) | — |
| `yiisoft/translator` | `locale`, `fallbackLocale` | `app.config.language` |
| `yiisoft/db` | `driver`, `host`, `port`, `name`, `user`, `password`, `charset` | `db.default.*` |
| `yiisoft/cache-redis` | `host`, `port`, `database`, `password` | `redis.default.*` |
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

JSON-encoded env values (e.g. `app.cors.allowedOrigins=["http://example.com:3000"]`) are decoded at the top of `params.php`.

### Environment Overrides

Per-environment overrides live in `config/environments/{dev,test,prod}/params.php` (e.g. `dev/params.php` sets `traceLink` for IDE links in the error renderer). They are merged by `config/configuration.php` based on `APP_ENV` (valid values: `dev`, `test`, `prod` — enforced by `App\Environment`).

### Environment Detection

```php
use App\Environment;

Environment::isDev();
Environment::isTest();
Environment::isProd();
Environment::appDebug();
```

---

## 🔧 Integration Examples

### Action Usage
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

### DI Definition Usage
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

### Service Usage
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

## 🚀 Best Practices

### Parameter Access
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

### Configuration
```php
// ✅ Environment-based: .env → params.php → $params groups
// ✅ Per-environment overrides: config/environments/{dev,test,prod}/params.php
```

### Adding a New Params Group
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

## 📊 Performance Considerations

- **Initialization**: `params.php` is evaluated once by yiisoft/config and merged into the container
- **Memory Usage**: `ApplicationParams` is a single readonly instance shared via DI
- **Access Speed**: Direct property access with minimal overhead
- **Configuration**: Environment variables loaded once at startup (`src/autoload.php` loads `.env`)

---

## 🎯 Summary

Application parameters provide centralized, type-safe configuration management with key benefits:

- **🎯 Centralization**: Single source of truth in `config/common/params.php`
- **🛡️ Type Safety**: `App\Shared\ApplicationParams` readonly properties prevent configuration errors
- **🔄 Immutability**: Readonly properties ensure consistency
- **🌐 Environment Support**: `APP_ENV` + `environments/*/params.php` for environment-specific configuration
- **📦 Integration**: Seamless DI integration through `$params` groups
- **⚡ Performance**: Fast access with minimal overhead

By following these patterns, you can build robust, maintainable configuration management for your Yii3 API application! 🚀
