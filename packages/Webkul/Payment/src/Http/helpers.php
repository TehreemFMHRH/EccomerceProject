<?php

use Webkul\Payment\Facades\Payment;

if (! function_exists('payment')) {
    
    function payment()
    {
        return Payment::getFacadeRoot();
    }
}
