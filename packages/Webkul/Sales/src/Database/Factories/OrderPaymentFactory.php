<?php

namespace Webkul\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Sales\Models\OrderPayment;

class OrderPaymentFactory extends Factory
{
    
    protected $model = OrderPayment::class;

    
    public function definition(): array
    {
        return [
            'method' => 'cashondelivery',
        ];
    }
}
