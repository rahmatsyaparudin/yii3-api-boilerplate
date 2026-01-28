<?php

declare(strict_types=1);

use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Infrastructure\Persistence\Example\ExampleRepository;
use App\Shared\Query\QueryConditionApplier;
use Yiisoft\Db\Connection\ConnectionInterface;
use App\Infrastructure\Security\CurrentUserAwareInterface;
use App\Infrastructure\Security\CurrentUser;
use Yiisoft\Definitions\Reference;

return [
    ExampleRepositoryInterface::class => [
        'class' => ExampleRepository::class,
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
];
