<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductGroupedProduct;

class ProductGroupedProductFactory extends Factory
{
    
    protected $model = ProductGroupedProduct::class;

    
    public function definition(): array
    {
        return [
            'qty'        => rand(10, 50),
            'sort_order' => 0,
        ];
    }
}
