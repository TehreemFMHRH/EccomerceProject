<?php

namespace Webkul\RentalProduct\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class RentalProductServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/product_types.php', 'product_types');
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'rental_product');

        Event::listen('bagisto.admin.catalog.product.create_form_accordian.general.before', function () {
            echo view('rental_product::admin.catalog.products.type')->render();
        });
    }
}
