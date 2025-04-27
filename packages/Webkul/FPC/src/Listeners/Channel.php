<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;

class Channel
{
    
    public function afterUpdate($a)
    {
        ResponseCache::clear();
    }
}
