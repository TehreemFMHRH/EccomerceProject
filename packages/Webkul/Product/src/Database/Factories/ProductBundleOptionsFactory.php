<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductBundleOption;

class ProductBundleOptionsFactory extends Factory
{
    
    protected $model = ProductBundleOption::class;

    
    public function definition(): array
    {
        return [
            'type'        => $this->faker->randomElement(['select', 'radio', 'checkbox', 'multiselect']),
            'is_required' => 0,
            'sort_order'  => 0,
        ];
    }
}
