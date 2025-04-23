<?php

namespace CustomVendor\Stripe\Providers;

use CustomVendor\Stripe\Payment\Stripe;
use Illuminate\Support\ServiceProvider;
use Webkul\Payment\Payment;

class StripeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('stripe', function () {
            return new Stripe;
        });
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'stripe');

        // Payment::extend('stripe', function ($app) {
        //     return $app->make('stripe');
        // });

        $this->publishes([
            __DIR__.'/../Config/paymentmethods.php' => config_path('stripe_paymentmethods.php'),
        ]);

        $this->mergeConfigFrom(__DIR__.'/../Config/paymentmethods.php', 'stripe_paymentmethods');
    }
}
