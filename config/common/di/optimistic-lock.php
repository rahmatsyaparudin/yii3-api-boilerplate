<?php

declare(strict_types=1);

use App\Shared\ValueObject\LockVersionConfig;

// 1. Cek global enable
$globalEnabled = filter_var($_ENV['app.optimistic_lock.disabled.values'] ?? true, FILTER_VALIDATE_BOOLEAN);

// 2. Ambil list dari env dan ubah jadi array
// Contoh: "flag,example" -> ["flag", "example"]
$disabledStr = $_ENV['app.optimistic_lock.disabled.values'] ?? '';
$disabledList = $disabledStr !== '' 
    ? array_map('trim', explode(',', strtolower($disabledStr))) 
    : [];

return [
    LockVersionConfig::class => [
        '__construct()' => [
            'globalEnabled' => $globalEnabled,
            'disabledValidators' => $disabledList,
        ],
    ],
];