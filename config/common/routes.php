<?php

declare(strict_types=1);

use App\Api;
use App\Api\V1\Brand\Action as BrandAction;
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
                ->action(BrandAction\BrandDataAction::class)
                ->name('v1/brand/index')
                ->defaults(['permission' => 'brand.index']),
            Route::post('/brand/data')
                ->action(BrandAction\BrandDataAction::class)
                ->name('v1/brand/data')
                ->defaults(['permission' => 'brand.data']),
            Route::get('/brand/{id:\d+}')
                ->action(BrandAction\BrandViewAction::class)
                ->name('v1/brand/view')
                ->defaults(['permission' => 'brand.view']),
            Route::post('/brand/create')
                ->action(BrandAction\BrandCreateAction::class)
                ->name('v1/brand/create')
                ->defaults(['permission' => 'brand.create']),
            Route::put('/brand/{id:\d+}')
                ->action(BrandAction\BrandUpdateAction::class)
                ->name('v1/brand/update')
                ->defaults(['permission' => 'brand.update']),
            Route::delete('/brand/{id:\d+}')
                ->action(BrandAction\BrandDeleteAction::class)
                ->name('v1/brand/delete')
                ->defaults(['permission' => 'brand.delete']),
            Route::post('/brand/{id:\d+}/restore')
                ->action(BrandAction\BrandRestoreAction::class)
                ->name('v1/brand/restore')
                ->defaults(['permission' => 'brand.restore']),
        ),
];
