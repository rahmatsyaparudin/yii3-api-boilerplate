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
        private readonly array $validatorOverrides = []
    ) {}

    /**
     * Logika pengecekan status aktif/non-aktif
     */
    public function isEnabledFor(string $validatorName): bool
    {
        if (!$this->globalEnabled) {
            return false;
        }

        // Contoh: FlagInputValidator -> flaginputvalidator
        $key = strtolower($validatorName);

        return $this->validatorOverrides[$key] ?? true;
    }
}