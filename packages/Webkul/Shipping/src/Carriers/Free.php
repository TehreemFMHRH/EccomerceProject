<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Models\CartShippingRate;

class Free extends AbstractShipping
{
    
    protected $code = 'free';

    
    protected $method = 'free_free';

    
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    
    public function getRate(): CartShippingRate
    {
        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = 0;
        $cartShippingRate->base_price = 0;

        return $cartShippingRate;
    }
}
