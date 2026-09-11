<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;

/** @var array $params */
$db     = $params['yiisoft/db'] ?? [];
$driver = $db['driver'] ?? 'pgsql';

if (!\in_array($driver, ['mysql', 'mariadb'], true)) {
    return [];
}

$dsn = new Dsn(
    'mysql',
    $db['host'],
    $db['name'],
    $db['port'],
    ['charset' => $db['charset'] ?? 'utf8mb4'],
);

return [
    FileCache::class => [
        'class'         => FileCache::class,
        '__construct()' => [
            'cachePath' => DynamicReference::to(static fn (Aliases $aliases) => $aliases->get('@runtime/cache')),
        ],
    ],
    SchemaCache::class => [
        'class'         => SchemaCache::class,
        '__construct()' => [
            Reference::to(FileCache::class),
        ],
        'setEnabled()' => [true],
    ],
    ConnectionInterface::class => static fn (SchemaCache $schemaCache) => new Connection(
        new Driver($dsn, $db['user'] ?? '', $db['password'] ?? ''),
        $schemaCache,
    ),
];
