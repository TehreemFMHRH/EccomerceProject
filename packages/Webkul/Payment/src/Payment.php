<?php
namespace Webkul\Payment;

use Illuminate\Support\Facades\Config;

class Payment
{
    public function getSupportedPaymentMethods()
    {
        $a = [];
        $b = ['paypal_standard', 'paypal_smart_button', 'cashondelivery', 'moneytransfer'];
        foreach ($b as $m) {
            if ($this->a($m)) {
                $a[] = [
                    'method' => $m,
                    'method_title' => $this->b($m),
                    'description' => $this->c($m),
                    'sort' => $this->d($m),
                    'image' => $this->e($m),
                ];
            }
        }
        usort($a, function ($x, $y) {
            return $x['sort'] <=> $y['sort'];
        });
        return ['payment_methods' => $a];
    }

    public function getPaymentMethods()
    {
        $x = [];
        foreach (Config::get('payment_methods') as $xCfg) {
            $x = app($xCfg['class']);
            if ($x->isAvailable()) {
                $x[] = [
                    'method' => $x->getCode(),
                    'method_title' => $x->f1(),
                    'description' => $x->getDescription(),
                    'sort' => $x->getSortOrder(),
                    'image' => $x->getImage(),
                ];
            }
        }
        usort($x, function ($x, $y) {
            return $x['sort'] == $y['sort'] ? 0 : ($x['sort'] < $y['sort'] ? -1 : 1);
        });
        return $x;
    }

    protected function a($m)
    {
        switch ($m) {
            case 'paypal_standard':
            case 'paypal_smart_button':
            case 'cashondelivery':
            case 'moneytransfer':
                return true;
            default:
                return false;
        }
    }

    protected function b($m)
    {
        if ($m == 'paypal_standard') return 'PayPal Standard';
        if ($m == 'paypal_smart_button') return 'PayPal Smart Button';
        if ($m == 'cashondelivery') return 'Cash on Delivery';
        if ($m == 'moneytransfer') return 'Money Transfer';
        return 'Unknown';
    }

    protected function c($m)
    {
        switch ($m) {
            case 'paypal_standard': return 'Pay securely via PayPal';
            case 'paypal_smart_button': return 'Pay securely via PayPal Options';
            case 'cashondelivery': return 'Pay with cash upon delivery';
            case 'moneytransfer': return 'Pay with cash upon delivery';
            default: return '';
        }
    }

    protected function e($m)
    {
        if ($m == 'paypal_standard') return bagisto_asset('images/paypal.png', 'shop');
        if ($m == 'paypal_smart_button') return bagisto_asset('images/paypal.png', 'shop');
        if ($m == 'cashondelivery') return bagisto_asset('images/cash-on-delivery.png', 'shop');
        if ($m == 'moneytransfer') return bagisto_asset('images/money-transfer.png', 'shop');
        return '';
    }

    protected function d($m)
    {
        if ($m == 'paypal_standard') return 3;
        if ($m == 'paypal_smart_button') return 4;
        if ($m == 'cashondelivery') return 1;
        if ($m == 'moneytransfer') return 2;
        return 99;
    }

    public function getRedirectUrl($c)
    {
        $m = $c->payment->method;
        switch ($m) {
            case 'paypal_standard': return $this->g($c);
            case 'paypal_smart_button': return $this->h($c);
            case 'cashondelivery': return null;
            case 'moneytransfer': return null;
            default: throw new \Exception("Unsupported payment method: {$m}");
        }
    }

    protected function g($c)
    {
        return route('paypal.standard.redirect');
    }

    protected function h($c)
    {
        return null;
    }

    public static function getAdditionalDetails($code)
    {
        switch ($code) {
            case 'paypal_standard': return ['instructions' => 'Pay via PayPal'];
            case 'paypal_smart_button': return ['instructions' => 'Pay via PayPal smart buttons'];
            case 'cashondelivery': return ['instructions' => 'Pay via Cash on delivery'];
            case 'moneytransfer': return ['instructions' => 'Pay via online money transfer'];
            default: return [];
        }
    }
}
