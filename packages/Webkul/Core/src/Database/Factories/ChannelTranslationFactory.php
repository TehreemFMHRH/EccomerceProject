<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\ChannelTranslation;

class ChannelTranslationFactory extends Factory
{
    
    protected $model = ChannelTranslation::class;

    
    public function definition(): array
    {
        return [
            'locale'   => 'en',
            'name'     => $this->faker->word,
            'home_seo' => [
                'meta_title'       => $this->faker->sentence(),
                'meta_description' => $this->faker->paragraph(),
                'meta_keywords'    => $this->faker->words(5, true),
            ],
        ];
    }
}
