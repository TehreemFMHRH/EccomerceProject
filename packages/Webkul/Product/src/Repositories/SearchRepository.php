<?php

namespace Webkul\Product\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Traits\Sanitizer;

class SearchRepository
{
    use Sanitizer;


    public function uploadSearchImage($dat)
    {
        $path = request()->file('image')->store('product-search');

        $this->sanitizeSVG($path, $dat['image']->getMimeType());

        return Storage::url($path);
    }
}
