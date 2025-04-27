<?php

namespace Webkul\Product\Repositories;

class ProductVideoRepository extends ProductMediaRepository
{
    
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductVideo';
    }
}
