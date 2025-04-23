<?php

namespace Webkul\ExpressShipping\Providers;

use Illuminate\Support\ServiceProvider;

class ExpressShippingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/carriers.php',
            'carriers'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php',
            'core'
        );
    }
}
