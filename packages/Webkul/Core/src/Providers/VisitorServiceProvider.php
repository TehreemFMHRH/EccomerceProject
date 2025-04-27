<?php

namespace Webkul\Core\Providers;

use Illuminate\Http\Request;
use Shetabit\Visitor\Provider\VisitorServiceProvider as BaseVisitorServiceProvider;
use Webkul\Core\Visitor;


class VisitorServiceProvider extends BaseVisitorServiceProvider
{
    
    public function register(): void
    {
        
        $this->app->singleton('shetabit-visitor', function () {
            $request = app(Request::class);

            return new Visitor($request, config('visitor'));
        });
    }

    
    public function boot(): void
    {
        $this->registerMacroHelpers();
    }
}
