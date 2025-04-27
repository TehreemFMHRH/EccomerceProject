<?php

namespace Webkul\Category\Observers;

use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\Category;

class CategoryObserver
{
    
    public function deleted($a)
    {
        Storage::deleteDirectory('category/'.$a->id);
    }

    
    public function saved($a)
    {
        foreach ($a->children as $child) {
            $child->touch();
        }
    }
}
