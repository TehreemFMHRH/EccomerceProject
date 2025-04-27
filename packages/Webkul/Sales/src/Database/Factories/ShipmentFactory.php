<?php

namespace Webkul\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Sales\Models\Shipment;

class ShipmentFactory extends Factory
{
    
    protected $model = Shipment::class;

    
    public function definition(): array
    {
        return [
            'total_qty'           => $this->faker->numberBetween(1, 20),
        ];
    }
}
