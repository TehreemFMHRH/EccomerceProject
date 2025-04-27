<?php

namespace Webkul\BookingProduct\Providers;

use Illuminate\Support\ServiceProvider;

class BookingProductServiceProvider extends ServiceProvider
{
    
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(EventServiceProvider::class);
    }
}
