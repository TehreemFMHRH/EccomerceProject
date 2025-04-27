<?php

namespace Webkul\Product\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\Console\Commands\Indexer;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Observers\ProductObserver;

class ProductServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        include __DIR__.'/../Http/helpers.php';

        $this->registerConfig();

        $this->registerCommands();
    }

    
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'product');

        ProductProxy::observe(ProductObserver::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('indexer:index --type=price')->dailyAt('00:01');
        });

        $this->app->register(EventServiceProvider::class);
    }

    
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/product_types.php', 'product_types');
    }

    
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Indexer::class]);
        }
    }
}
