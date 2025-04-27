<?php

namespace Webkul\Checkout\Listeners;

use Webkul\Checkout\Facades\Cart;

class CustomerEventsHandler
{
    
    public function onCustomerLogin($k)
    {
        
        Cart::mergeCart($k);
    }

    
    public function subscribe($events)
    {
        $events->listen('customer.after.login', 'Webkul\Checkout\Listeners\CustomerEventsHandler@onCustomerLogin');
    }
}
