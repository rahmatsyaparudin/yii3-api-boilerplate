<?php

declare(strict_types=1);

// Domain Layer
use App\Api;

// Api Layer
use App\Api\V1\Example\Action as ExampleAction;

// Shared Layer
use App\Shared\Middleware\RequestParamsMiddleware;

// Vendor Layer
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

// @var array $params

return [
    Route::get('/')->action(Api\IndexAction::class)->name('app/index'),

    Group::create('/v1')
        ->middleware(RequestParamsMiddleware::class)
        ->routes(
            Route::get('/example')
                ->action(ExampleAction\ExampleDataAction::class)
                ->name('v1/example/index')
                ->defaults(['permission' => 'example.index']),
            Route::post('/example/data')
                ->action(ExampleAction\ExampleDataAction::class)
                ->name('v1/example/data')
                ->defaults(['permission' => 'example.data']),
            Route::get('/example/{id:\d+}')
                ->action(ExampleAction\ExampleViewAction::class)
                ->name('v1/example/view')
                ->defaults(['permission' => 'example.view']),
            Route::post('/example/create')
                ->action(ExampleAction\ExampleCreateAction::class)
                ->name('v1/example/create')
                ->defaults(['permission' => 'example.create']),
            Route::put('/example/{id:\d+}')
                ->action(ExampleAction\ExampleUpdateAction::class)
                ->name('v1/example/update')
                ->defaults(['permission' => 'example.update']),
            Route::delete('/example/{id:\d+}')
                ->action(ExampleAction\ExampleDeleteAction::class)
                ->name('v1/example/delete')
                ->defaults(['permission' => 'example.delete']),
            Route::post('/example/{id:\d+}/restore')
                ->action(ExampleAction\ExampleRestoreAction::class)
                ->name('v1/example/restore')
                ->defaults(['permission' => 'example.restore']),
        ),
];

