<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Cache\SchemaCacheInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Aliases\Aliases;

/** @var array $params */

$pgsql = $params['yiisoft/db-pgsql'];

return [
    SchemaCacheInterface::class => SchemaCache::class,
    FileCache::class => [
        'class' => FileCache::class,
        '__construct()' => [
            'cachePath' => DynamicReference::to(static fn (Aliases $aliases) => $aliases->get('@runtime/cache')),
        ],
    ],
    SchemaCache::class => [
        '__construct()' => [
            Reference::to(FileCache::class),
        ],
        'setEnabled()' => [true],
    ],
    ConnectionInterface::class => [
        'class'         => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                $pgsql['dsn'],
                $pgsql['username'],
                $pgsql['password'],
            ),
            'setSchemaCache()' => [Reference::to(SchemaCacheInterface::class)],
            // 'schemaCache' => Reference::to(SchemaCache::class),
        ],
    ],
];
