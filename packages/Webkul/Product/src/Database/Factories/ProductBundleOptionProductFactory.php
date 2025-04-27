<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductBundleOptionProduct;

class ProductBundleOptionProductFactory extends Factory
{
    
    protected $model = ProductBundleOptionProduct::class;

    
    public function definition(): array
    {
        return [
            'qty'             => 1,
            'is_user_defined' => 1,
            'is_default'      => 0,
            'sort_order'      => 0,
        ];
    }
}
