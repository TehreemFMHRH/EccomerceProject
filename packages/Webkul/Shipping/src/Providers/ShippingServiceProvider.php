<?php

namespace Webkul\Shipping\Providers;

use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    
    public function register()
    {
        include __DIR__.'/../Http/helpers.php';

        $this->registerConfig();
    }

    
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/carriers.php', 'carriers'
        );
    }
}
