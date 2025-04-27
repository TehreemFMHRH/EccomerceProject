<?php

namespace Webkul\Shop\CacheFilters;

use Illuminate\Support\Str;
use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Small implements FilterInterface
{
    
    public function applyFilter(Image $image)
    {
        
        if (Str::contains(url()->current(), '/product')) {
            $width = core()->getConfigData('catalog.products.cache_small_image.width')
                ? core()->getConfigData('catalog.products.cache_small_image.width')
                : 100;

            $height = core()->getConfigData('catalog.products.cache_small_image.height')
                ? core()->getConfigData('catalog.products.cache_small_image.height')
                : 100;

            return $image->fit($width, $height);
        } elseif (Str::contains(url()->current(), '/category')) {
            return $image->fit(80, 80);
        } elseif (Str::contains(url()->current(), '/attribute_option')) {
            return $image->fit(60, 60);
        }

        
        return $image->fit(525, 191);
    }
}
