<?php

namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\SystemConfig as BaseSystemConfig;

class SystemConfig extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseSystemConfig::class;
    }
}
