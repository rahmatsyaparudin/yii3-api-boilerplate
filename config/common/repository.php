<?php

declare(strict_types=1);

use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\System\Token\Repository\ServiceTokenRepositoryInterface;
use App\Infrastructure\Common\Persistence\AnotherExample\AnotherExampleRepository;
use App\Infrastructure\Common\Persistence\Example\ExampleRepository;
use App\Infrastructure\Common\Persistence\System\ServiceTokenRepository;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Shared\Core\ValueObject\LockVersionConfig;
use Yiisoft\Definitions\Reference;

return [
    ExampleRepositoryInterface::class => [
        'class'                  => ExampleRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()'       => [Reference::to(CurrentUser::class)],
        '__construct()'          => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AnotherExampleRepositoryInterface::class => [
        'class'                  => AnotherExampleRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()'       => [Reference::to(CurrentUser::class)],
        '__construct()'          => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    ServiceTokenRepositoryInterface::class => [
        'class'                  => ServiceTokenRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()'       => [Reference::to(CurrentUser::class)],
    ],
];
