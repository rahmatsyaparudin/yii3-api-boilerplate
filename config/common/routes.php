<?php

declare(strict_types=1);

// Domain Layer
use App\Api;

// Vendor Layer
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

// Shared Layer
use App\Presentation\Core\Http\Middleware\RequestParamsMiddleware;

// Api Layer
use App\Api\V1\Example\Action as ExampleV1;
use App\Api\V1\AnotherExample\Action as AnotherExampleV1;
use App\Api\V1\System\Token\Action as ServiceTokenV1;

// @var array $params

return [
    Route::get('/')->action(Api\IndexAction::class)->name('app/index'),

    Group::create('/v1')
        ->middleware(RequestParamsMiddleware::class)
        ->routes(
            Route::get('/example')
                ->action(ExampleV1\ExampleDataAction::class)
                ->name('v1/example/index')
                ->defaults(['permission' => 'example.index']),
            Route::post('/example/data')
                ->action(ExampleV1\ExampleDataAction::class)
                ->name('v1/example/data')
                ->defaults(['permission' => 'example.data']),
            Route::get('/example/{id:\d+}')
                ->action(ExampleV1\ExampleViewAction::class)
                ->name('v1/example/view')
                ->defaults(['permission' => 'example.view']),
            Route::post('/example/create')
                ->action(ExampleV1\ExampleCreateAction::class)
                ->name('v1/example/create')
                ->defaults(['permission' => 'example.create']),
            Route::put('/example/{id:\d+}')
                ->action(ExampleV1\ExampleUpdateAction::class)
                ->name('v1/example/update')
                ->defaults(['permission' => 'example.update']),
            Route::delete('/example/{id:\d+}')
                ->action(ExampleV1\ExampleDeleteAction::class)
                ->name('v1/example/delete')
                ->defaults(['permission' => 'example.delete']),
            Route::post('/example/{id:\d+}/restore')
                ->action(ExampleV1\ExampleRestoreAction::class)
                ->name('v1/example/restore')
                ->defaults(['permission' => 'example.restore']),

            // AnotherExample Routes
            Route::get('/another-example')
                ->action(AnotherExampleV1\AnotherExampleDataAction::class)
                ->name('v1/another-example/index')
                ->defaults(['permission' => 'another-example.index']),
            Route::post('/another-example/data')
                ->action(AnotherExampleV1\AnotherExampleDataAction::class)
                ->name('v1/another-example/data')
                ->defaults(['permission' => 'another-example.data']),
            Route::get('/another-example/{id:\d+}')
                ->action(AnotherExampleV1\AnotherExampleViewAction::class)
                ->name('v1/another-example/view')
                ->defaults(['permission' => 'another-example.view']),
            Route::post('/another-example/create')
                ->action(AnotherExampleV1\AnotherExampleCreateAction::class)
                ->name('v1/another-example/create')
                ->defaults(['permission' => 'another-example.create']),
            Route::put('/another-example/{id:\d+}')
                ->action(AnotherExampleV1\AnotherExampleUpdateAction::class)
                ->name('v1/another-example/update')
                ->defaults(['permission' => 'another-example.update']),
            Route::delete('/another-example/{id:\d+}')
                ->action(AnotherExampleV1\AnotherExampleDeleteAction::class)
                ->name('v1/another-example/delete')
                ->defaults(['permission' => 'another-example.delete']),
            Route::post('/another-example/{id:\d+}/restore')
                ->action(AnotherExampleV1\AnotherExampleRestoreAction::class)
                ->name('v1/another-example/restore')
                ->defaults(['permission' => 'another-example.restore']),

            // System — Service Token Routes
            Route::post('/system/service-token/data')
                ->action(ServiceTokenV1\ServiceTokenDataAction::class)
                ->name('v1/system/service-token/data')
                ->defaults(['permission' => 'system.service-token.list']),
            Route::get('/system/service-token/{id:\d+}')
                ->action(ServiceTokenV1\ServiceTokenViewAction::class)
                ->name('v1/system/service-token/view')
                ->defaults(['permission' => 'system.service-token.view']),
            Route::post('/system/service-token/create')
                ->action(ServiceTokenV1\ServiceTokenCreateAction::class)
                ->name('v1/system/service-token/create')
                ->defaults(['permission' => 'system.service-token.create']),
            Route::put('/system/service-token/{id:\d+}')
                ->action(ServiceTokenV1\ServiceTokenUpdateAction::class)
                ->name('v1/system/service-token/update')
                ->defaults(['permission' => 'system.service-token.update']),
            Route::delete('/system/service-token/{id:\d+}')
                ->action(ServiceTokenV1\ServiceTokenDeleteAction::class)
                ->name('v1/system/service-token/delete')
                ->defaults(['permission' => 'system.service-token.delete']),
        ),
];
