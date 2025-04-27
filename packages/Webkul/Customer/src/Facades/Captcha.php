<?php

namespace Webkul\Customer\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Customer\Captcha as BaseCaptcha;

class Captcha extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseCaptcha::class;
    }
}
