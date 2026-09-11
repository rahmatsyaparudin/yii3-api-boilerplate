<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Shared\Core\Contract\DateTimeProviderInterface;
// Infrastructure Layer
use App\Infrastructure\Core\Clock\SystemClock;
use App\Infrastructure\Core\Time\AppDateTimeProvider;
// PSR Interfaces
use Psr\Clock\ClockInterface;

/** @var array $params */

// Core infrastructure bindings. Project-owned bindings are merged
// from config/common/infrastructure.php and may override these.
$projectBindings = \dirname(__DIR__) . '/infrastructure.php';

return array_merge([
    ClockInterface::class            => SystemClock::class,
    DateTimeProviderInterface::class => AppDateTimeProvider::class,
], file_exists($projectBindings) ? require $projectBindings : []);
