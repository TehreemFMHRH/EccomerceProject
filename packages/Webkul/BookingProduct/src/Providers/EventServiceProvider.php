<?php

namespace Webkul\BookingProduct\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        'checkout.order.save.after' => [
            'Webkul\BookingProduct\Listeners\Order@afterPlaceOrder',
        ],
    ];
}
