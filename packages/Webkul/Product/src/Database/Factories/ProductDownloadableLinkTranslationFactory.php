<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductDownloadableLinkTranslation;

class ProductDownloadableLinkTranslationFactory extends Factory
{
    
    protected $model = ProductDownloadableLinkTranslation::class;

    
    public function definition(): array
    {
        return [
            'locale' => 'en',
            'title'  => $this->faker->word,
        ];
    }
}
