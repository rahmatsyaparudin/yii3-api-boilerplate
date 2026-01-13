<?php

declare(strict_types=1);

use App\Api;
use App\Api\V1\Brand as BrandV1;
use App\Shared\Middleware\RequestParamsMiddleware;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

// @var array $params

return [
    Route::get('/')->action(Api\IndexAction::class)->name('app/index'),

    Group::create('/v1')
        ->middleware(RequestParamsMiddleware::class)
        ->routes(
            Route::get('/brand')
                ->action(BrandV1\BrandListAction::class)
                ->name('v1/brand/index')
                ->defaults(['permission' => 'brand.index']),
            Route::post('/brand/data')
                ->action(BrandV1\BrandDataAction::class)
                ->name('v1/brand/data')
                ->defaults(['permission' => 'brand.data']),
            Route::get('/brand/{id:\d+}')
                ->action(BrandV1\BrandViewAction::class)
                ->name('v1/brand/view')
                ->defaults(['permission' => 'brand.view']),
            Route::post('/brand/create')
                ->action(BrandV1\BrandCreateAction::class)
                ->name('v1/brand/create')
                ->defaults(['permission' => 'brand.create']),
            Route::put('/brand/{id:\d+}')
                ->action(BrandV1\BrandUpdateAction::class)
                ->name('v1/brand/update')
                ->defaults(['permission' => 'brand.update']),
            Route::delete('/brand/{id:\d+}')
                ->action(BrandV1\BrandDeleteAction::class)
                ->name('v1/brand/delete')
                ->defaults(['permission' => 'brand.delete']),
        ),
];
