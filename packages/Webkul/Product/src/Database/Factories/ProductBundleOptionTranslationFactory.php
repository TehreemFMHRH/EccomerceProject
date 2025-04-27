<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\ProductBundleOptionTranslation;

class ProductBundleOptionTranslationFactory extends Factory
{
    
    protected $model = ProductBundleOptionTranslation::class;

    
    public function definition(): array
    {
        return [
            'label'  => $this->faker->words(3, true),
            'locale' => app()->getLocale(),
        ];
    }
}
