<?php

namespace Webkul\CatalogRule\Listeners;

use Webkul\CatalogRule\Jobs\UpdateCreateProductIndex as UpdateCreateProductIndexJob;

class Product
{
    
    public function afterUpdate($product)
    {
        UpdateCreateProductIndexJob::dispatch($product);
    }
}
