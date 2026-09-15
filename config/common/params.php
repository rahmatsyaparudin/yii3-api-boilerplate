<?php

declare(strict_types=1);

/**
 * Project params.
 *
 * Core params are loaded from params-core.php (skeleton-owned, synced via
 * `composer skeleton:update`). Add project-specific params in the array
 * below — they are merged over the core params via array_replace_recursive,
 * so new keys are added and existing keys (including nested ones) are
 * overridden.
 */
return \array_replace_recursive(
    require __DIR__ . '/params-core.php',
    [
        // 'app/my-feature' => [
        //     'enabled' => true,
        // ],
    ],
);
