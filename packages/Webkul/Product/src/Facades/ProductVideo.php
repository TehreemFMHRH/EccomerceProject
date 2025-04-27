<?php

namespace Webkul\Product\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Product\ProductVideo as BaseProductVideo;

class ProductVideo extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseProductVideo::class;
    }
}
