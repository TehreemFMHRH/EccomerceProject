<?php

namespace Webkul\Theme\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    
    public function register()
    {
        include __DIR__.'/../Http/helpers.php';

        $this->app->singleton('view.finder', function ($app) {
            return new \Webkul\Theme\ThemeViewFinder(
                $app['files'],
                $app['config']['view.paths'],
                null
            );
        });
    }

    
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Blade::directive('bagistoVite', function ($expression) {
            return "<?php echo themes()->setBagistoVite({$expression})->toHtml(); ?>";
        });
    }
}
