<?php

namespace Webkul\Theme\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Theme\Themes as BaseThemes;

class Themes extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseThemes::class;
    }
}
