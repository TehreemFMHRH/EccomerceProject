<?php

namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\Acl as BaseAcl;

class Acl extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseAcl::class;
    }
}
