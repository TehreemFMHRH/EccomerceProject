<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;

class FlatRate extends AbstractShipping
{
    
    protected $code = 'flatrate';

    
    protected $method = 'flatrate_flatrate';

    
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    
    public function getRate(): CartShippingRate
    {
        $cart = Cart::getCart();

        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = 0;
        $cartShippingRate->base_price = 0;

        if ($this->getConfigData('type') == 'per_unit') {
            foreach ($cart->items as $item) {
                if ($item->getTypeInstance()->isStockable()) {
                    $cartShippingRate->price += core()->convertPrice($this->getConfigData('default_rate')) * $item->quantity;
                    $cartShippingRate->base_price += $this->getConfigData('default_rate') * $item->quantity;
                }
            }
        } else {
            $cartShippingRate->price = core()->convertPrice($this->getConfigData('default_rate'));
            $cartShippingRate->base_price = $this->getConfigData('default_rate');
        }

        return $cartShippingRate;
    }
}
