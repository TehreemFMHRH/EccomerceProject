<?php

namespace Webkul\CatalogRule\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\CatalogRule\Console\Commands\PriceRuleIndex;

class CatalogRuleServiceProvider extends ServiceProvider
{
    
    public function register()
    {
        $this->registerCommands();
    }

    
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('product:price-rule:index')->dailyAt('00:01');
        });

        $this->app->register(EventServiceProvider::class);
    }

    
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PriceRuleIndex::class]);
        }
    }
}
