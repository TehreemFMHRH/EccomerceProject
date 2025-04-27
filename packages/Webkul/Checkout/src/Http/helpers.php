<?php

use Webkul\Checkout\Facades\Cart;

if (! function_exists('cart')) {
    
    function cart()
    {
        return Cart::getFacadeRoot();
    }
}
