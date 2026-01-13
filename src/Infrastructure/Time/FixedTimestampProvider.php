<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use App\Shared\Contract\TimestampProviderInterface;

/**
 * For testing purposes - returns fixed timestamp.
 */
final class FixedTimestampProvider implements TimestampProviderInterface
{
    public function __construct(
        private string $fixedTimestamp = '2025-01-13 18:30:00',
        private string $fixedUtcTimestamp = '2025-01-13T11:30:00Z',
    ) {
    }

    public function now(): string
    {
        return $this->fixedTimestamp;
    }

    public function utcNow(): string
    {
        return $this->fixedUtcTimestamp;
    }
}
