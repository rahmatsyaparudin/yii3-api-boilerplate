<?php

declare(strict_types=1);

use Yiisoft\Db\Pgsql\Dsn;

$isDev = $_ENV['APP_ENV'] === 'dev';
$allowed_origins = json_decode($_ENV['app.cors.allowedOrigins'], true);

return [
    'application' => require __DIR__ . '/application.php',

    'app/pagination' => [
        'defaultPageSize' => 50,
        'maxPageSize' => 200,
    ],
    'app/time' => [
        'timezone' => $_ENV['app.timezone'],
    ],
    'app/cors' => [
        'maxAge' => 86400,
        'allowCredentials' => true,
        'allowedOrigins' => $isDev ? $allowed_origins : $allowed_origins,
        'allowedMethods' => [
            'GET', 
            'POST', 
            'PUT', 
            'PATCH', 
            'DELETE', 
            'OPTIONS'
        ],
        'allowedHeaders' => [
            'Content-Type', 
            'Authorization', 
            'X-Requested-With', 
            'Accept', 
            'Origin'
        ],
        'exposedHeaders' => [
            'X-Pagination-Total-Count', 
            'X-Pagination-Page-Count'
        ],
    ],
    'app/jwt' => [
        'secret' => $_ENV['app.jwt.secret'],
        'algorithm' => $_ENV['app.jwt.algorithm'] ?? 'HS256',
        'issuer' => $_ENV['app.jwt.issuer'] ?? 'https://sso.dev-enterkomputer.com',
        'audience' => $_ENV['app.jwt.audience'] ?? 'https://sso.dev-enterkomputer.com',
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
        'locale' => $_ENV['app.language'],
        'fallbackLocale' => $_ENV['app.language'],
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
        'sourceNamespaces' => ['App\\Migration'],
    ],
];
