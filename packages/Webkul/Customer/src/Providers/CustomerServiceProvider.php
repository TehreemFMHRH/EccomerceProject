<?php

namespace Webkul\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Customer\Facades\Captcha;

class CustomerServiceProvider extends ServiceProvider
{
    
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'customer');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'customer');

        $this->app['validator']->extend('captcha', function ($attribute, $va, $parameters) {
            return Captcha::getFacadeRoot()->validateResponse($va);
        });
    }
}
