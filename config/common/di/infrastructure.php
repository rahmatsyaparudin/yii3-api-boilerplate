<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Shared\Contract\DateTimeProviderInterface;

// Infrastructure Layer
use App\Infrastructure\Time\AppDateTimeProvider;
use App\Infrastructure\Clock\SystemClock;

// PSR Interfaces
use Psr\Clock\ClockInterface;

return [
    ClockInterface::class => SystemClock::class,
    DateTimeProviderInterface::class => AppDateTimeProvider::class,
];