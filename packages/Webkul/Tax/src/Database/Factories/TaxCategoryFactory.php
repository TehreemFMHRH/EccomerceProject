<?php

namespace Webkul\Tax\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Tax\Models\TaxCategory;

class TaxCategoryFactory extends Factory
{
    
    protected $model = TaxCategory::class;

    
    public function definition(): array
    {
        return [
            'code'        => $this->faker->uuid,
            'name'        => $this->faker->words(2, true),
            'description' => $this->faker->sentence(10),
        ];
    }
}
