<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

// Vendor Layer
use DateTimeImmutable;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Domain Layer
use App\Domain\Shared\Contract\DateTimeProviderInterface;

final class AppDateTimeProvider implements DateTimeProviderInterface
{
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function object(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function database(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }

    public function iso8601(): string
    {
        return $this->clock->now()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }
}
