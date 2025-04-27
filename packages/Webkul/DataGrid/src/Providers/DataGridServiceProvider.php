<?php

namespace Webkul\DataGrid\Providers;

use Illuminate\Support\ServiceProvider;

class DataGridServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        include __DIR__.'/../Http/helpers.php';
    }

    
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
