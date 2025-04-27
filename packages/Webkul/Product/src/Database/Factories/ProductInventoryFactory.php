<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductInventory;

class ProductInventoryFactory extends Factory
{
    
    protected $model = ProductInventory::class;

    
    public function definition(): array
    {
        return [
            'qty' => $this->faker->numberBetween(100, 200),
        ];
    }
}
