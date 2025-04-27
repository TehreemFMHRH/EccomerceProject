<?php

namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\Core as BaseCore;

class Core extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseCore::class;
    }
}
