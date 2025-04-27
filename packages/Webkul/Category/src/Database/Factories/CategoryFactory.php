<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\Category;

class CategoryFactory extends Factory
{
    
    protected $model = Category::class;

    
    protected $states = [
        'inactive',
        'rtl',
    ];

    
    public function definition(): array
    {
        return [
            'status'    => 1,
            'position'  => $this->faker->randomDigit(),
            'parent_id' => 1,
        ];
    }

    public function inactive(): CategoryFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 0,
            ];
        });
    }

    
    public function rtl(): CategoryFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'direction' => 'rtl',
            ];
        });
    }
}
