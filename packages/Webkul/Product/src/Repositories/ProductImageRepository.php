<?php

namespace Webkul\Product\Repositories;

class ProductImageRepository extends ProductMediaRepository
{
    
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductImage';
    }
}
