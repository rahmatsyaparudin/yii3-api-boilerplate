<?php

declare(strict_types=1);

use Yiisoft\Db\Pgsql\Dsn;

$isDev           = $_ENV['APP_ENV'] === 'dev';
$allowed_origins = \json_decode($_ENV['app.cors.allowedOrigins'], true);

return [
    'application' => require __DIR__ . '/application.php',
    'app/config'  => [
        'code'     => 'enterEDC',
        'name'     => $_ENV['app.config.name'] ?? 'name',
        'language' => $_ENV['app.config.language'] ?? 'en',
    ],
    'app/pagination' => [
        'defaultPageSize' => 50,
        'maxPageSize'     => 200,
    ],
    'app/rateLimit' => [
        'maxRequests' => 100,
        'windowSize'  => 60, // seconds
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
    'app/hsts' => [
        'maxAge'            => 31536000,
        'includeSubDomains' => true,
        'preload'           => false,
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
    'app/time' => [
        'timezone' => $_ENV['app.time.timezone'],
    ],
    'app/cors' => [
        'maxAge'           => 86400,
        'allowCredentials' => true,
        'allowedOrigins'   => $isDev ? $allowed_origins : $allowed_origins,
        'allowedMethods'   => [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS',
        ],
        'allowedHeaders' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'Origin',
        ],
        'exposedHeaders' => [
            'X-Pagination-Total-Count',
            'X-Pagination-Page-Count',
        ],
    ],
    'app/jwt' => [
        'secret'    => $_ENV['app.jwt.secret'],
        'algorithm' => $_ENV['app.jwt.algorithm'] ?? 'HS256',
        'issuer'    => $_ENV['app.jwt.issuer'] ?? 'https://sso.dev-enterkomputer.com',
        'audience'  => $_ENV['app.jwt.audience'] ?? 'https://sso.dev-enterkomputer.com',
    ],
    'app/trusted_hosts' => [
        'allowedHosts' => [
            '127.0.0.1',
            '::1',
            'localhost',
        ],
    ],
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
    'yiisoft/db-migration' => [
        'newMigrationNamespace' => 'App\\Migration',
        'sourceNamespaces'      => ['App\\Migration'],
    ],
];
