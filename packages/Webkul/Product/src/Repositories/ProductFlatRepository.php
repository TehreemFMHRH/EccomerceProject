<?php

namespace Webkul\Product\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProductFlatRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductFlat';
    }
}
