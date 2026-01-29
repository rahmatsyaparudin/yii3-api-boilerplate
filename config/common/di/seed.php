<?php

declare(strict_types=1);

use App\Console\SeedExampleCommand;
use Yiisoft\Definitions\Reference;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    SeedExampleCommand::class => [
        'class' => SeedExampleCommand::class,
        '__construct()' => [
            Reference::to(ClockInterface::class),
            Reference::to(ConnectionInterface::class),
        ],
    ],
];
