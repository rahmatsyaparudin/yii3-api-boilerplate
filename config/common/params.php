<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Db\Pgsql\Dsn;

$isDev = $_ENV['APP_ENV'] === 'dev';
$allowed_origins = \json_decode($_ENV['app.cors.allowedOrigins'] ?? '[]', true) ?? [];
$allowedMethods = \json_decode($_ENV['app.cors.allowedMethods'] ?? '[]', true) ?? [];
$allowedHeaders = \json_decode($_ENV['app.cors.allowedHeaders'] ?? '[]', true) ?? [];
$exposedHeaders = \json_decode($_ENV['app.cors.exposedHeaders'] ?? '[]', true) ?? [];
$trustedHosts = \json_decode($_ENV['app.trusted_hosts.allowedHosts'] ?? '[]', true) ?? [];
$disabledValues = \json_decode($_ENV['app.optimistic_lock.disabled.values'] ?? '[]', true) ?? [];

return [
    'application' => require __DIR__ . '/application.php',
    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],
    'yiisoft/translator' => [
        'locale'         => $_ENV['app.config.language'],
        'fallbackLocale' => $_ENV['app.config.language'],
    ],
    'yiisoft/db-pgsql' => [
        'dsn' => new Dsn(
            $_ENV['db.default.driver'],
            $_ENV['db.default.host'],
            $_ENV['db.default.name'],
            $_ENV['db.default.port']
        ),
        'username' => $_ENV['db.default.user'],
        'password' => $_ENV['db.default.password'],
    ],
    'yiisoft/cache-redis' => [
        'host'     => $_ENV['redis.default.host'] ?? '127.0.0.1',
        'port'     => (int)($_ENV['redis.default.port'] ?? 6379),
        'database' => (int)($_ENV['redis.default.db'] ?? 0),
        'password' => $_ENV['redis.default.password'] ?? null,
    ],
    'yiisoft/db-migration' => [
        'newMigrationNamespace' => 'App\\Migration',
        'sourceNamespaces'      => ['App\\Migration'],
    ],
    'mongodb/mongodb' => [
        'enabled' => filter_var($_ENV['db.mongodb.enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'dsn' => "mongodb://{$_ENV['db.mongodb.dsn']}",
        'database' => $_ENV['db.mongodb.name'],
        'connectTimeoutMS' => (int)($_ENV['db.mongodb.connectTimeoutMS'] ?? 2000),
        'socketTimeoutMS' => (int)($_ENV['db.mongodb.socketTimeoutMS'] ?? 2000),
        'readPreference' => $_ENV['db.mongodb.readPreference'] ?? 'primary',
    ],
    'app/config'  => [
        'code'     => $_ENV['app.config.code'] ?? 'code',
        'name'     => $_ENV['app.config.name'] ?? 'name',
        'language' => $_ENV['app.config.language'] ?? 'en',
        'allow_god_mode' => filter_var($_ENV['app.config.allow_god_mode'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],
    'app/optimisticLock' => [
        'enabled' => filter_var($_ENV['app.optimistic_lock.enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'disabledValues' => $disabledValues,
    ],
    'app/pagination' => [
        'defaultPageSize' => $_ENV['app.pagination.defaultPageSize'] ?? 10,
        'maxPageSize'     => $_ENV['app.pagination.maxPageSize'] ?? 200,
    ],
    'app/rateLimit' => [
        'maxRequests' => $_ENV['app.rateLimit.maxRequests'] ?? 100,
        'windowSize'  => $_ENV['app.rateLimit.windowSize'] ?? 60,
    ],
    'app/hsts' => [
        'maxAge'            => (int) ($_ENV['app.hsts.maxAge'] ?? 31536000),
        'includeSubDomains' => filter_var($_ENV['app.hsts.includeSubDomains'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'preload'           => filter_var($_ENV['app.hsts.preload'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],
    'app/time' => [
        'timezone' => $_ENV['app.time.timezone'],
    ],
    'app/cors' => [
        'maxAge'           => (int) $_ENV['app.cors.maxAge'] ?? 86400,
        'allowCredentials' => filter_var($_ENV['app.cors.allowCredentials'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'allowedOrigins'   => $isDev ? ['*'] : $allowed_origins,
        'allowedMethods'   => $allowedMethods,
        'allowedHeaders'   => $allowedHeaders,
        'exposedHeaders'   => $exposedHeaders,
    ],
    'app/jwt' => [
        'secret'    => $_ENV['app.jwt.secret'],
        'algorithm' => $_ENV['app.jwt.algorithm'] ?? 'HS256',
        'issuer'    => $_ENV['app.jwt.issuer'] ?? null,
        'audience'  => $_ENV['app.jwt.audience'] ?? null,
    ],
    'app/trusted_hosts' => [
        'allowedHosts' => $trustedHosts,
    ],
    'app/secureHeaders' => [
        'csp' => [
            'default-src' => "'self'",
            'script-src'  => "'self' 'unsafe-inline'",
            'style-src'   => "'self' 'unsafe-inline'",
            'img-src'     => "'self' data: https:",
            'connect-src' => "'self'",
        ],
        'permissions' => [
            'geolocation' => '()',
            'microphone'  => '()',
            'camera'      => '()',
        ],
        'custom' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-XSS-Protection'       => '1; mode=block',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        ],
    ],
    'app/monitoring' => [
        'provider'          => 'custom',
        'log_file'          => 'runtime/logs/api.log',
        'request_id_header' => 'X-Request-Id',
        'logging'           => [
            'enabled'               => true,
            'log_level'             => 'info',
            'include_request_body'  => false,
            'include_response_body' => false,
            'max_log_size'          => 10000,
            'exclude_paths'         => ['/health', '/metrics'],
            'exclude_status_codes'  => [404],
        ],
        'metrics' => [
            'enabled'             => true,
            'track_response_time' => true,
            'track_request_count' => true,
            'track_status_codes'  => true,
            'track_memory_usage'  => true,
            'track_cpu_usage'     => false,
            'reset_interval'      => 300,
        ],
        'error_monitoring' => [
            'enabled'                => true,
            'capture_exceptions'     => true,
            'capture_errors'         => true,
            'max_errors_per_request' => 10,
            'include_stack_trace'    => true,
            'include_request_data'   => false,
            'ignore_exceptions'      => [],
            'ignore_error_codes'     => [404, 422],
        ],
    ],
];