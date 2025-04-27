<?php

namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\Menu as BaseMenu;

class Menu extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseMenu::class;
    }
}
