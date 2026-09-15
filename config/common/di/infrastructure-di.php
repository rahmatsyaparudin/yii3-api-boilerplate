<?php

declare(strict_types=1);

// Application Layer
use App\Console\MigrateModuleCommand;
// Domain Layer
use App\Domain\Shared\Core\Contract\DateTimeProviderInterface;
// Infrastructure Layer
use App\Infrastructure\Core\Clock\SystemClock;
use App\Infrastructure\Core\Database\ConnectionPool;
use App\Infrastructure\Core\Time\AppDateTimeProvider;
// PSR Interfaces
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
// Vendor Layer
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;

/** @var array $params */

// Core infrastructure bindings. Project-owned bindings are merged
// from config/common/infrastructure.php and may override these.
$projectBindings = \dirname(__DIR__) . '/infrastructure.php';

return array_merge([
    ClockInterface::class            => SystemClock::class,
    DateTimeProviderInterface::class => AppDateTimeProvider::class,
    MigrateModuleCommand::class => static function (ContainerInterface $container) use ($params): MigrateModuleCommand {
        return new MigrateModuleCommand(
            $container->get(ConnectionPool::class),
            $container,
            $container->get(MigrationInformerInterface::class),
            $params['app/migrations']['moduleConnections'] ?? [],
        );
    },
], file_exists($projectBindings) ? require $projectBindings : []);
