<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Enums\CurrencyPositionEnum;
use Webkul\Core\Models\Currency;

class CurrencyFactory extends Factory
{
    
    protected $model = Currency::class;

    
    public function definition(): array
    {
        return [
            'code'              => $this->faker->unique()->currencyCode,
            'name'              => $this->faker->word,
            'decimal'           => 2,
            'group_separator'   => ',',
            'decimal_separator' => '.',
            'currency_position' => CurrencyPositionEnum::LEFT->value,
        ];
    }
}
