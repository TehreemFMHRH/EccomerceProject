<?php

namespace Webkul\FPC\Providers;

use Illuminate\Support\ServiceProvider;

class FPCServiceProvider extends ServiceProvider
{
    
    public function boot()
    {
        $this->app->register(EventServiceProvider::class);
    }
}
