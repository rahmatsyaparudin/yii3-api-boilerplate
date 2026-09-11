<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Shared\Core\Contract\DateTimeProviderInterface;
// Infrastructure Layer
use App\Infrastructure\Core\Clock\SystemClock;
use App\Infrastructure\Core\Time\AppDateTimeProvider;
// PSR Interfaces
use Psr\Clock\ClockInterface;

return [
    ClockInterface::class            => SystemClock::class,
    DateTimeProviderInterface::class => AppDateTimeProvider::class,
];
