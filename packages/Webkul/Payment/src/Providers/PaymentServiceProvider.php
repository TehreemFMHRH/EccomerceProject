<?php

namespace Webkul\Payment\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    
    public function boot()
    {
        include __DIR__.'/../Http/helpers.php';

        $this->app->register(EventServiceProvider::class);
    }

    
    public function register()
    {
        $this->registerConfig();
    }

    
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/paymentmethods.php', 'payment_methods'
        );
    }
}
