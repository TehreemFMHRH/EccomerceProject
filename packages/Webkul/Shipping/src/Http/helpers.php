<?php

use Webkul\Shipping\Facades\Shipping;

if (! function_exists('shipping')) {
    
    function shipping()
    {
        return Shipping::getFacadeRoot();
    }
}
