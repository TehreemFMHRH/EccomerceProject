<?php

namespace CustomVendor\Stripe\Payment;

use Webkul\Payment\Payment\Payment;

class Stripe extends Payment
{
    protected $code  = 'stripe';

    public function getRedirectUrl()
    {
        return route('stripe.redirect');
    }
}
