<?php

declare(strict_types=1);

use App\Shared\ValueObject\LockVersionConfig;

return [
    LockVersionConfig::class => [
        '__construct()' => [
            'globalEnabled' => $params['app/optimisticLock']['enabled'],
            'disabledValidators' => $params['app/optimisticLock']['disabledValues'],
        ],
    ],
];