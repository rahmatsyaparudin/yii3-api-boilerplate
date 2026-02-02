<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

/**
 * LockVersionConfig - Value Object yang merepresentasikan aturan
 * konfigurasi Optimistic Lock di seluruh aplikasi.
 */
final class LockVersionConfig
{
    public function __construct(
        private readonly bool $globalEnabled,
        private readonly array $disabledValidators = []
    ) {}

    public function isEnabledFor(string $validatorName): bool
    {
        if (!$this->globalEnabled) {
            return false;
        }

        // Contoh: FlagInputValidator -> flag
        $key = strtolower(str_replace('InputValidator', '', $validatorName));

        // Jika ada di dalam list disabled, maka return false
        return !in_array($key, $this->disabledValidators, true);
    }
}