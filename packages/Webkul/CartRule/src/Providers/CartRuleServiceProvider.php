<?php

namespace Webkul\CartRule\Providers;

use Illuminate\Support\ServiceProvider;

class CartRuleServiceProvider extends ServiceProvider
{
    
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(EventServiceProvider::class);
    }
}
