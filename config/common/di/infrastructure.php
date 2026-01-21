<?php

declare(strict_types=1);

use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Infrastructure\Time\AppDateTimeProvider;
use Psr\Clock\ClockInterface;
use App\Infrastructure\Clock\SystemClock;

return [
    ClockInterface::class => SystemClock::class,
    DateTimeProviderInterface::class => AppDateTimeProvider::class,
];