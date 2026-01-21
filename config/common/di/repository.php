<?php

declare(strict_types=1);

use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Brand\Repository\BrandQueryServiceInterface;
use App\Infrastructure\Persistence\Brand\BrandRepository;
use App\Infrastructure\Persistence\Brand\BrandQueryService;
use App\Shared\Query\QueryConditionApplier;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    // Brand Repository (Yiisoft/Db)
    BrandRepositoryInterface::class => static function (
        QueryConditionApplier $queryConditionApplier,
        ConnectionInterface $db
    ) {
        return new BrandRepository($queryConditionApplier, $db);
    },
    
    // Brand Query Service (Simplified)
    BrandQueryServiceInterface::class => BrandQueryService::class,
];
