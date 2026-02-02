<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

final class LockVersionConfig
{
    private readonly array $normalizedDisabledList;

    public function __construct(
        private readonly bool $globalEnabled,
        array $disabledValidators = []
    ) {
        $this->normalizedDisabledList = array_map(
            fn($val) => $this->normalize($val), 
            $disabledValidators
        );
    }

    public function isEnabledFor(string $validatorName): bool
    {
        if (!$this->globalEnabled) {
            return false;
        }

        return !in_array($this->normalize($validatorName), $this->normalizedDisabledList, true);
    }

    private function normalize(string $name): string
    {
        $name = str_ireplace('InputValidator', '', $name);
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($name)));
    }
}