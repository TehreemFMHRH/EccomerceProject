<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;

class Order extends Product
{
    
    public function afterCancelOrCreate($o)
    {
        foreach ($o->all_items as $item) {
            if (! $item->product) {
                continue;
            }

            $urls = $this->getForgettableUrls($item->product);

            ResponseCache::forget($urls);
        }
    }
}
