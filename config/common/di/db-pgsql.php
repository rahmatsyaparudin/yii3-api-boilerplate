<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;

/** @var array $params */
$db     = $params['yiisoft/db'] ?? [];
$driver = $db['driver'] ?? 'pgsql';

if ($driver !== 'pgsql') {
    return [];
}

$dsn = new Dsn(
    'pgsql',
    $db['host'],
    $db['name'],
    $db['port'],
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
