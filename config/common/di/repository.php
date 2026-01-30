<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Category\Repository\CategoryRepositoryInterface;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;

// Infrastructure Layer
use App\Infrastructure\Persistence\Example\ExampleRepository;
use App\Infrastructure\Persistence\Category\CategoryRepository;
use App\Infrastructure\Persistence\Brand\BrandRepository;
use App\Infrastructure\Persistence\Product\ProductRepository;

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
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
    ProductRepositoryInterface::class => [
        'class' => ProductRepository::class,
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
    BrandRepositoryInterface::class => [
        'class' => BrandRepository::class,
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
    CategoryRepositoryInterface::class => [
        'class' => CategoryRepository::class,
        'setCurrentUser()' => [Reference::to(CurrentUser::class)],
    ],
];