<?php

declare(strict_types=1);

// Vendor Layer
use MongoDB\Client;

// Infrastructure Layer
use App\Infrastructure\Database\MongoService;

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

    MongoService::class => [
        'class' => MongoService::class,
        '__construct()' => [
            'client' => Reference::to(Client::class),
            'dbName' => $params['mongodb/mongodb']['database'],
            'enabled' => $params['mongodb/mongodb']['enabled'],
        ],
    ],
];