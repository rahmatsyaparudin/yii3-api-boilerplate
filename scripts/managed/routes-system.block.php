            // === SKELETON MANAGED ROUTES — do not edit between markers ===
            // System module routes (service token, cache purge). Synced by
            // `composer skeleton:update`; FQCN is used so the block is
            // self-contained and needs no `use` statements in this file.
            \Yiisoft\Router\Route::post('/system/service-token/data')
                ->action(\App\Api\V1\System\ServiceToken\Action\ServiceTokenDataAction::class)
                ->name('v1/system/service-token/data')
                ->defaults(['permission' => 'system.service-token.list']),
            \Yiisoft\Router\Route::get('/system/service-token/{id:\d+}')
                ->action(\App\Api\V1\System\ServiceToken\Action\ServiceTokenViewAction::class)
                ->name('v1/system/service-token/view')
                ->defaults(['permission' => 'system.service-token.view']),
            \Yiisoft\Router\Route::post('/system/service-token/create')
                ->action(\App\Api\V1\System\ServiceToken\Action\ServiceTokenCreateAction::class)
                ->name('v1/system/service-token/create')
                ->defaults(['permission' => 'system.service-token.create']),
            \Yiisoft\Router\Route::put('/system/service-token/{id:\d+}')
                ->action(\App\Api\V1\System\ServiceToken\Action\ServiceTokenUpdateAction::class)
                ->name('v1/system/service-token/update')
                ->defaults(['permission' => 'system.service-token.update']),
            \Yiisoft\Router\Route::delete('/system/service-token/{id:\d+}')
                ->action(\App\Api\V1\System\ServiceToken\Action\ServiceTokenDeleteAction::class)
                ->name('v1/system/service-token/delete')
                ->defaults(['permission' => 'system.service-token.delete']),
            \Yiisoft\Router\Route::post('/system/cache/purge')
                ->action(\App\Api\V1\System\Cache\Action\CachePurgeAction::class)
                ->name('v1/system/cache/purge')
                ->defaults(['permission' => 'system.cache.purge']),
            // === END SKELETON MANAGED ROUTES ===
