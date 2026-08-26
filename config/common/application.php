<?php

declare(strict_types=1);

return [
    'name'    => $_ENV['app.config.name'] ?? 'My Project',
    'version' => $_ENV['app.config.version'] ?? '1.0',
];
