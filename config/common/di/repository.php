<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Example\Repository\ExampleRepositoryInterface;

// Infrastructure Layer
use App\Infrastructure\Persistence\Example\ExampleRepository;

// Shared Layer
use App\Shared\Query\QueryConditionApplier;

// Infrastructure Layer
use App\Infrastructure\Security\CurrentUserAwareInterface;
use App\Infrastructure\Security\CurrentUser;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Definitions\Reference;

return [
    ExampleRepositoryInterface::class => [
        'class' => ExampleRepository::class,
        'setOptimisticLockEnabled()' => [$params['app/optimisticLock']['enabled'] ?? true],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
];