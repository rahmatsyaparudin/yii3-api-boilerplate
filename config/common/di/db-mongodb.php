<?php

declare(strict_types=1);

// Vendor Layer
use MongoDB\Client;

// Infrastructure Layer
use App\Infrastructure\Database\MongoDB\MongoDBService;

// Vendor Layer
use Yiisoft\Definitions\Reference;

/** @var array $params */

$mongodb = $params['mongodb/mongodb'];

$enabled = ($mongodb['enabled'] ?? false) && extension_loaded('mongodb');

if ($enabled) {
    return [
        Client::class => [
            'class' => Client::class,
            '__construct()' => [
                'uri' => $mongodb['dsn'],
                'uriOptions' => [
                    'connectTimeoutMS' => $mongodb['connectTimeoutMS'] ?? 5000,
                    'socketTimeoutMS'  => $mongodb['socketTimeoutMS'] ?? 5000,
                    'readPreference'   => $mongodb['readPreference'] ?? 'primary',
                ],
                'driverOptions' => [
                    'typeMap' => [
                        'root'     => 'array',
                        'document' => 'array',
                        'array'    => 'array',
                    ],
                ],
            ],
        ],

        MongoDBService::class => [
            'class' => MongoDBService::class,
            '__construct()' => [
                'client'  => Reference::to(Client::class),
                'dbName'  => $mongodb['database'],
                'enabled' => true,
            ],
        ],
    ];
}

return [
    MongoDBService::class => [
        'class' => MongoDBService::class,
        '__construct()' => [
            'client'  => null,
            'dbName'  => $mongodb['database'] ?? '',
            'enabled' => false,
        ],
    ],
];
