<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\CategoryTranslation;

class CategoryTranslationFactory extends Factory
{
    
    protected $model = CategoryTranslation::class;

    
    public function definition(): array
    {
        return [
            'name'        => $this->faker->word,
            'slug'        => $this->faker->unique()->slug,
            'description' => $this->faker->sentence(),
            'locale'      => 'en',
            'locale_id'   => 1,
        ];
    }
}
