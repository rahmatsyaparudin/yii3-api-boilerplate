<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use App\Infrastructure\Clock\SystemClock;
use App\Shared\Contract\TimestampProviderInterface;

final class SystemTimestampProvider implements TimestampProviderInterface
{
    public function __construct(
        private SystemClock $clock,
    ) {
    }

    public function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }

    public function utcNow(): string
    {
        return $this->clock->now()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }
}
