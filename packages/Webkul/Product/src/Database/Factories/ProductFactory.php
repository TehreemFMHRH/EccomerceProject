<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\Product;

class ProductFactory extends Factory
{
    
    protected $model = Product::class;

    
    protected $states = [
        'simple',
        'configurable',
        'virtual',
        'grouped',
        'downloadable',
        'bundle',
    ];

    
    public function definition(): array
    {
        return [
            'sku'                 => $this->faker->uuid,
            'attribute_family_id' => 1,
        ];
    }

    
    public function simple(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'simple',
            ];
        });
    }

    
    public function virtual(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'virtual',
            ];
        });
    }

    
    public function grouped(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'grouped',
            ];
        });
    }

    
    public function configurable(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'configurable',
            ];
        });
    }

    
    public function downloadable(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'downloadable',
            ];
        });
    }

    
    public function bundle(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'bundle',
            ];
        });
    }
}
