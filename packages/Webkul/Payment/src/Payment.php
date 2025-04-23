<?php

namespace Webkul\Payment;

use Illuminate\Support\Facades\Config;

class Payment
{
    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getSupportedPaymentMethods()
    {
        $methods = [];

        // Example: hardcoded shipping methods for demo purposes
        $availableMethods = ['paypal_standard', 'paypal_smart_button', 'cashondelivery', 'moneytransfer'];

        foreach ($availableMethods as $method) {
            if ($this->isPaymentAvailable($method)) {
                $methods[] = [
                    'method'       => $method,
                    'method_title' => $this->getTitle($method),
                    'description'  => $this->getDescription($method),
                    'sort'         => $this->getSortOrder($method),
                    'image'        => $this->getImage($method),
                ];
            }
        }

        usort($methods, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });

        return ['payment_methods' => $methods];
    }

    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $paymentMethods = [];

        foreach (Config::get('payment_methods') as $paymentMethodConfig) {
            $paymentMethod = app($paymentMethodConfig['class']);

            if ($paymentMethod->isAvailable()) {
                $paymentMethods[] = [
                    'method'       => $paymentMethod->getCode(),
                    'method_title' => $paymentMethod->getTitle(),
                    'description'  => $paymentMethod->getDescription(),
                    'sort'         => $paymentMethod->getSortOrder(),
                    'image'        => $paymentMethod->getImage(),
                ];
            }
        }

        usort($paymentMethods, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $paymentMethods;
    }

    protected function isPaymentAvailable($method)
    {
        switch ($method) {
            case 'paypal_standard':
            case 'paypal_smart_button':
            case 'cashondelivery':
            case 'moneytransfer':
                return true;

            default:
                return false;
        }
    }

    protected function getTitle($method)
    {
        return match ($method) {
            'paypal_standard' => 'PayPal Standard',
            'paypal_smart_button' => 'PayPal Smart Button',
            'cashondelivery'    => 'Cash on Delivery',
            'moneytransfer'    => 'Money Transfer',
            default  => 'Unknown',
        };
    }

    protected function getDescription($method)
    {
        return match ($method) {
            'paypal_standard' => 'Pay securely via PayPal',
            'paypal_smart_button' => 'Pay securely via PayPal Options',
            'cashondelivery'    => 'Pay with cash upon delivery',
            'moneytransfer'    => 'Pay with cash upon delivery',
            default  => '',
        };
    }

    protected function getImage($method)
    {
        return match ($method) {
            'paypal_standard'      => bagisto_asset('images/paypal.png', 'shop'),
            'paypal_smart_button'  => bagisto_asset('images/paypal.png', 'shop'),
            'cashondelivery'                  => bagisto_asset('images/cash-on-delivery.png', 'shop'),
            'moneytransfer'        => bagisto_asset('images/money-transfer.png', 'shop'),
            default                => '',
        };
    }

    protected function getSortOrder($method)
    {
        return match ($method) {
            'paypal_standard' => 3,
            'paypal_smart_button' => 4,
            'cashondelivery'    => 1,
            'moneytransfer'    => 2,
            default  => 99,
        };
    }


    /**
     * Returns payment redirect url if have any
     *
     * @param  \Webkul\Checkout\Contracts\Cart  $cart
     * @return string
     */
    public function getRedirectUrl($cart)
    {
        $method = $cart->payment->method;

        switch ($method) {
            case 'paypal_standard':
                return $this->getPayPalStandardRedirectUrl($cart);

            case 'paypal_smart_button':
                return $this->getPaypalSmartButtonRedirectUrl($cart);

            case 'cashondelivery':
                return null;

            case 'moneytransfer':
                return null;

            default:
                throw new \Exception("Unsupported payment method: {$method}");
        }
    }

    protected function getPayPalStandardRedirectUrl($cart)
    {
        return route('paypal.standard.redirect');
    }

    protected function getPaypalSmartButtonRedirectUrl($cart)
    {
        return null;
    }

    /**
     * Returns payment method additional information
     *
     * @param  string  $code
     * @return array
     */
    public static function getAdditionalDetails($code)
    {
        switch ($code) {
            case 'paypal_standard':
                return ['instructions' => 'Pay via PayPal'];
            case 'paypal_smart_button':
                return ['instructions' => 'Pay via PayPal smart buttons'];
            case 'cashondelivery':
                return ['instructions' => 'Pay via Cash on delivery'];
            case 'moneytransfer':
                return ['instructions' => 'Pay via online money transfer'];


        }
    }
}
