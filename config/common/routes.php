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
            // --help Routes
            Route::get('/--help')
                ->action(--helpAction\--helpDataAction::class)
                ->name('v1/--help/index')
                ->defaults(['permission' => '--help.index']),
            Route::post('/--help/data')
                ->action(--helpAction\--helpDataAction::class)
                ->name('v1/--help/data')
                ->defaults(['permission' => '--help.data']),
            Route::get('/--help/{id:\d+}')
                ->action(--helpAction\--helpViewAction::class)
                ->name('v1/--help/view')
                ->defaults(['permission' => '--help.view']),
            Route::post('/--help/create')
                ->action(--helpAction\--helpCreateAction::class)
                ->name('v1/--help/create')
                ->defaults(['permission' => '--help.create']),
            Route::put('/--help/{id:\d+}')
                ->action(--helpAction\--helpUpdateAction::class)
                ->name('v1/--help/update')
                ->defaults(['permission' => '--help.update']),
            Route::delete('/--help/{id:\d+}')
                ->action(--helpAction\--helpDeleteAction::class)
                ->name('v1/--help/delete')
                ->defaults(['permission' => '--help.delete']),
            Route::post('/--help/{id:\d+}/restore')
                ->action(--helpAction\--helpRestoreAction::class)
                ->name('v1/--help/restore')
                ->defaults(['permission' => '--help.restore']),

        ),
];
