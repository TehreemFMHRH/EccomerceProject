<?php

namespace Webkul\Product\Observers;

use Illuminate\Support\Facades\Storage;

class ProductObserver
{
    
    public function deleted($product)
    {
        Storage::deleteDirectory('product/'.$product->id);
    }
}
