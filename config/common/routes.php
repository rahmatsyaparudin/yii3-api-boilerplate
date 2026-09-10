<?php

declare(strict_types=1);

// Domain Layer
use App\Api;

// Api Layer
use App\Api\V1\Example\Action as ExampleV1;
use App\Api\V1\AnotherExample\Action as AnotherExampleV1;

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

        ),
];

