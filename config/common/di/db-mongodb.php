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

return [
    Client::class => [
        'class' => Client::class,
        '__construct()' => [
            'uri' => $mongodb['dsn'],
            'uriOptions' => [
                // Mengurangi waktu tunggu jika node mati
                'connectTimeoutMS' => $mongodb['connectTimeoutMS'] ?? 5000, 
                'socketTimeoutMS' => $mongodb['socketTimeoutMS'] ?? 5000,
                // Sangat penting untuk replika set/atlas
                'readPreference' => $mongodb['readPreference'] ?? 'primary', 
            ],
            'driverOptions' => [
                // Menggunakan persistent connection (seperti pooling)
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ],
            ],
        ],
    ],

    MongoDBService::class => [
        'class' => MongoDBService::class,
        '__construct()' => [
            'client' => Reference::to(Client::class),
            'dbName' => $mongodb['database'],
            'enabled' => $mongodb['enabled'],
        ],
    ],
];