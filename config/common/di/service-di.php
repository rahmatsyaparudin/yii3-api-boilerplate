<?php

declare(strict_types=1);

/** @var array $params */

// Core service DI configuration. Add project-wide service bindings here.
// Project-owned bindings may be merged from config/common/service.php and
// can override these defaults.
$projectBindings = \dirname(__DIR__) . '/service.php';

return array_merge([
    // Service DI configuration
    // Add your service definitions here
], file_exists($projectBindings) ? require $projectBindings : []);
