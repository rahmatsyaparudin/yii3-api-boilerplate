<?php

declare(strict_types=1);

use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Infrastructure\Persistence\Brand\BrandRepository;
use App\Shared\Query\QueryConditionApplier;
use Yiisoft\Db\Connection\ConnectionInterface;
use App\Infrastructure\Security\CurrentUserAwareInterface;
use App\Infrastructure\Security\CurrentUser;
use Yiisoft\Definitions\Reference;

return [
    BrandRepositoryInterface::class => [
        'class' => BrandRepository::class,
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
];
