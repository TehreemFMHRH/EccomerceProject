<?php

namespace Webkul\Checkout\Providers;

use Illuminate\Support\ServiceProvider;

class CheckoutServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        include __DIR__.'/../Http/helpers.php';
    }

    
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(EventServiceProvider::class);
        $this->app->register(ModuleServiceProvider::class);
    }
}
