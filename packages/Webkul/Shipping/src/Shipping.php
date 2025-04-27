<?php

namespace Webkul\Shipping;

use Illuminate\Support\Facades\Config;
use Webkul\Checkout\Facades\Cart;

class Shipping
{
    
    protected $rates = [];

    
    public function collectRates()
    {
        if (! Cart::getCart()) {
            return false;
        }

        $this->removeAllShippingRates();

        $ratesList = [];

        foreach (Config::get('carriers') as $shippingMethod) {
            $object = new $shippingMethod['class'];

            if ($rates = $object->calculate()) {
                if (is_array($rates)) {
                    $ratesList[] = $rates;
                } else {
                    $ratesList[] = [$rates];
                }
            }
        }

        $this->rates = array_merge(...$ratesList);

        $this->saveAllShippingRates();

        return [
            'shippingMethods' => $this->getGroupedAllShippingRates(),
        ];
    }

    
    public function removeAllShippingRates()
    {
        if (! $cart = Cart::getCart()) {
            return;
        }

        $cart->shipping_rates()->delete();

        $this->rates = [];
    }

    
    public function saveAllShippingRates()
    {
        if (! $cart = Cart::getCart()) {
            return;
        }

        $shippingAddress = $cart->shipping_address;

        if (! $shippingAddress) {
            return;
        }

        foreach ($this->rates as $rate) {
            $rate->cart_id = $cart->id;
            $rate->cart_address_id = $shippingAddress->id;
            $rate->price_incl_tax = $rate->price;
            $rate->base_price_incl_tax = $rate->base_price;

            $rate->save();
        }
    }

    
    public function getGroupedAllShippingRates()
    {
        $rates = [];

        foreach ($this->rates as $rate) {
            if (! isset($rates[$rate->carrier])) {
                $rates[$rate->carrier] = [
                    'carrier_title' => $rate->carrier_title,
                    'rates'         => [],
                ];
            }

            $rate['base_formatted_price'] = core()->currency($rate->base_price);

            $rates[$rate->carrier]['rates'][] = $rate;
        }

        return $rates;
    }

    
    public function getShippingMethods()
    {
        $methods = [];

        foreach (Config::get('carriers') as $shippingMethod) {
            $object = new $shippingMethod['class'];

            if (! $object->isAvailable()) {
                continue;
            }

            $methods[] = [
                'code'         => $object->getCode(),
                'method'       => $object->getMethod(),
                'method_title' => $object->getTitle(),
                'description'  => $object->getDescription(),
            ];
        }

        return $methods;
    }

    
    public function isMethodCodeExists($shippingMethodCode)
    {
        $shippingMethods = $this->collectRates()['shippingMethods'] ?? [];

        if (
            empty($shippingMethods)
            || ! $shippingMethods
        ) {
            return false;
        }

        foreach ($shippingMethods as $shippingMethod) {
            foreach ($shippingMethod['rates'] as $rate) {
                if ($rate->method === $shippingMethodCode) {
                    return true;
                }
            }
        }

        return false;
    }
}
