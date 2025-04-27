<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductReview;

class ProductReviewFactory extends Factory
{
    
    protected $model = ProductReview::class;

    
    public function definition(): array
    {
        return [
            'title'   => $this->faker->words(5, true),
            'rating'  => $this->faker->numberBetween(0, 10),
            'status'  => 'pending',
            'comment' => $this->faker->sentence(20),
        ];
    }
}
