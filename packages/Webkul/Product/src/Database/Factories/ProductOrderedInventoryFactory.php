<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductOrderedInventory;

class ProductOrderedInventoryFactory extends Factory
{
    
    protected $model = ProductOrderedInventory::class;

    
    public function definition(): array
    {
        return [
            'qty'        => $this->faker->numberBetween(100, 200),
            'channel_id' => 1,
        ];
    }
}
