<?php

namespace Webkul\MagicAI\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\MagicAI\MagicAI as BaseMagicAI;

class MagicAI extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseMagicAI::class;
    }
}
