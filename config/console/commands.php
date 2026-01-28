<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'simple-generate' => Console\SimpleGenerateCommand::class,
    'template:generate' => Console\TemplateGeneratorCommand::class,
];
