<?php

namespace Webkul\Shipping\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Shipping\Shipping as BaseShipping;

class Shipping extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseShipping::class;
    }
}
