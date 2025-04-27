<?php

namespace Webkul\Shop\Http\Middleware;

use Closure;

class Theme
{
    
    public function handle($request, Closure $next)
    {
        $themes = themes();

        $channel = core()->getCurrentChannel();

        if (
            $channel
            && $channelThemeCode = $channel->theme
        ) {
            $themes->exists($channelThemeCode)
                ? $themes->set($channelThemeCode)
                : $themes->set(config('themes.shop-default'));
        } else {
            $themes->set(config('themes.shop-default'));
        }

        return $next($request);
    }
}
