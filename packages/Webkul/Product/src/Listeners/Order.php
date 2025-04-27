<?php

namespace Webkul\Product\Listeners;

use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;

class Order
{
    
    public function afterCancelOrCreate($o)
    {
        $productIds = $o->all_items
            ->pluck('product_id')
            ->toArray();

        UpdateCreateInventoryIndexJob::dispatch($productIds);
    }
}
