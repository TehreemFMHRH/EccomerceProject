<?php

namespace Webkul\Product\Listeners;

use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;

class Refund
{
    
    public function afterCreate($refund)
    {
        $productIds = $refund->items
            ->pluck('product_id')
            ->toArray();

        UpdateCreateInventoryIndexJob::dispatch($productIds);
    }
}
