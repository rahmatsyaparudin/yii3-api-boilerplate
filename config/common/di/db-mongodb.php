<?php

declare(strict_types=1);

// Vendor Layer
use MongoDB\Client;

// Infrastructure Layer
use App\Infrastructure\Database\MongoDB\MongoDBService;

// Vendor Layer
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    Client::class => [
        'class' => Client::class,
        '__construct()' => [
            'uri' => $params['mongodb/mongodb']['dsn'],
        ],
    ],

    MongoDBService::class => [
        'class' => MongoDBService::class,
        '__construct()' => [
            'client' => Reference::to(Client::class),
            'dbName' => $params['mongodb/mongodb']['database'],
            'enabled' => $params['mongodb/mongodb']['enabled'],
        ],
    ],
];