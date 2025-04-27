<?php

namespace Webkul\Checkout\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    protected $subscribe = [
        'Webkul\Checkout\Listeners\CustomerEventsHandler',
    ];
}
