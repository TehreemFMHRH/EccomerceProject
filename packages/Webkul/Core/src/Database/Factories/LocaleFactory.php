<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Locale;

class LocaleFactory extends Factory
{
    
    protected $model = Locale::class;

    
    protected $states = [
        'rtl',
    ];

    
    public function definition(): array
    {
        do {
            $languageCode = $this->faker->languageCode;
        } while (Locale::query()
            ->where('code', $languageCode)
            ->exists());

        return [
            'code'      => $languageCode,
            'name'      => $this->faker->country,
            'direction' => 'ltr',
        ];
    }

    public function rtl(): LocaleFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'direction' => 'rtl',
            ];
        });
    }
}
