<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Example\Entity\Example;

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
        'setLockVersionConfig()' => [Reference::to('App\Shared\ValueObject\LockVersionConfig')],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
];