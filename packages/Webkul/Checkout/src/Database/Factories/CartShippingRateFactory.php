<?php

namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\CartShippingRate;

class CartShippingRateFactory extends Factory
{
    
    protected $model = CartShippingRate::class;

    
    public function definition(): array
    {
        return [
            'is_calculate_tax'     => 1,
            'discount_amount'      => 0.0000,
            'base_discount_amount' => 0.0000,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }
}
