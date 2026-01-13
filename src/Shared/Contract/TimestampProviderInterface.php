<?php

declare(strict_types=1);

namespace App\Shared\Contract;

interface TimestampProviderInterface
{
    public function now(): string;

    public function utcNow(): string;
}
