<?php

declare(strict_types=1);

use App\Console;
use App\Environment;

$commands = [
    'hello'          => Console\Core\HelloCommand::class,
    'migrate:module' => Console\Core\MigrateModuleCommand::class,
    'seed'           => Console\Core\SeederCommand::class,
];

if (Environment::isProd()) {
    $commands['migrate:down'] = Console\Core\MigrationGuardCommand::class;
    $commands['migrate:redo'] = Console\Core\MigrationGuardCommand::class;
}

return $commands;
