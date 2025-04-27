<?php

namespace Webkul\Tax\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Tax\Tax as BaseTax;

class Tax extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseTax::class;
    }
}
