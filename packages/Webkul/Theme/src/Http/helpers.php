<?php

use Webkul\Theme\Facades\Themes;
use Webkul\Theme\ViewRenderEventManager;

if (! function_exists('themes')) {
    
    function themes()
    {
        return Themes::getFacadeRoot();
    }
}

if (! function_exists('bagisto_asset')) {
    
    function bagisto_asset(string $path, ?string $namespace = null)
    {
        return themes()->url($path, $namespace);
    }
}

if (! function_exists('view_render_event')) {
    
    function view_render_event($eventName, $params = null)
    {
        app()->singleton(ViewRenderEventManager::class);

        $viewEventManager = app()->make(ViewRenderEventManager::class);

        $viewEventManager->handleRenderEvent($eventName, $params);

        return $viewEventManager->render();
    }
}
