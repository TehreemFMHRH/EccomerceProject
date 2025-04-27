<?php

namespace Webkul\Marketing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\Marketing\Console\Commands\EmailsCommand;

class MarketingServiceProvider extends ServiceProvider
{
    
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([EmailsCommand::class]);
        }
    }

    
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('campaign:process')->daily();
        });

        $this->app->register(EventServiceProvider::class);
    }
}
