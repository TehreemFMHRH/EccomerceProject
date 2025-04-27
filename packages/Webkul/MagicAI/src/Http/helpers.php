<?php

use Webkul\MagicAI\Facades\MagicAI;

if (! function_exists('magic_ai')) {
    
    function magic_ai()
    {
        return MagicAI::getFacadeRoot();
    }
}
