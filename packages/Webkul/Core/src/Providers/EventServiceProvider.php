<?php

namespace Webkul\Core\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        'Prettus\Repository\Events\RepositoryEntityCreated' => [
            'Webkul\Core\Listeners\CleanCacheRepository',
        ],
        'Prettus\Repository\Events\RepositoryEntityUpdated' => [
            'Webkul\Core\Listeners\CleanCacheRepository',
        ],
        'Prettus\Repository\Events\RepositoryEntityDeleted' => [
            'Webkul\Core\Listeners\CleanCacheRepository',
        ],
        'Spatie\ResponseCache\Events\ResponseCacheHit' => [
            'Webkul\Core\Listeners\ResponseCacheHit',
        ],
    ];
}
