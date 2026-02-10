<?php

declare(strict_types=1);

use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Infrastructure\Persistence\Example\ExampleRepository;
use App\Shared\ValueObject\LockVersionConfig;
use App\Infrastructure\Security\CurrentUser;
use Yiisoft\Definitions\Reference;

return [
    ExampleRepositoryInterface::class => [
        'class' => ExampleRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
        '__construct()' => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
];