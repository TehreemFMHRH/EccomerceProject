<?php

namespace Webkul\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Sales\Models\Invoice;

class InvoiceFactory extends Factory
{
    
    protected $model = Invoice::class;

    
    protected $states = [
        'pending',
        'paid',
        'refunded',
    ];

    
    public function definition(): array
    {
        return [];
    }

    public function pending(): InvoiceFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    public function paid(): InvoiceFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
            ];
        });
    }

    public function refunded(): InvoiceFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'refunded',
            ];
        });
    }
}
