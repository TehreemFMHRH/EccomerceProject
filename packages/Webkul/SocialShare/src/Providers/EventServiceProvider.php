<?php

namespace Webkul\SocialShare\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    public function boot()
    {
        Event::listen('bagisto.shop.products.view.compare.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('social_share::share');
        });
    }
}
