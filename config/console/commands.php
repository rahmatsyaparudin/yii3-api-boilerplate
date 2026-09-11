<?php

declare(strict_types=1);

use App\Console;

return [
    'hello'          => Console\HelloCommand::class,
    'migrate:module' => Console\MigrateModuleCommand::class,
    'seed'           => Console\SeederCommand::class,
];
