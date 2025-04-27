<?php

namespace Webkul\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\CurrencyExchangeRate;

class CurrencyExchangeRateFactory extends Factory
{
    
    protected $model = CurrencyExchangeRate::class;

    
    public function definition()
    {
        return [
            'rate' => rand(1, 100),
        ];
    }
}
