<?php

namespace Webkul\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Sales\Models\OrderAddress;

class OrderAddressFactory extends Factory
{
    
    protected $model = OrderAddress::class;

    
    protected $states = [
        'shipping',
    ];

    
    public function definition(): array
    {
        return [
            'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        ];
    }

    public function shipping(): void
    {
        $this->state(function () {
            return [
                'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
            ];
        });
    }
}
