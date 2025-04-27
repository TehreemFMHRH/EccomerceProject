<?php

namespace Webkul\Payment\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Payment\Payment as BasePayment;

class Payment extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BasePayment::class;
    }
}
