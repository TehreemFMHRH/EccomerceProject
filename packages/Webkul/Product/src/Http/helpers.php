<?php

use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Facades\ProductVideo;
use Webkul\Product\Helpers\Toolbar;

if (! function_exists('product_image')) {
    
    function product_image()
    {
        return ProductImage::getFacadeRoot();
    }
}

if (! function_exists('product_video')) {
    
    function product_video()
    {
        return ProductVideo::getFacadeRoot();
    }
}

if (! function_exists('product_toolbar')) {
    
    function product_toolbar()
    {
        return app()->make(Toolbar::class);
    }
}
