<?php

namespace Webkul\Tax\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Tax\Models\TaxRate;

class TaxRateFactory extends Factory
{
    
    protected $model = TaxRate::class;

    
    public function definition(): array
    {
        return [
            'identifier' => $this->faker->uuid,
            'is_zip'     => 0,
            'zip_code'   => '*',
            'zip_from'   => null,
            'zip_to'     => null,
            'state'      => '',
            'country'    => $this->faker->countryCode,
            'tax_rate'   => $this->faker->randomFloat(2, 3, 25),
        ];
    }
}
