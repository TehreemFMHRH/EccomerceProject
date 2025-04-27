<?php

namespace Webkul\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Sales\Models\InvoiceItem;

class InvoiceItemFactory extends Factory
{
    
    protected $model = InvoiceItem::class;

    
    public function definition(): array
    {
        $basePrice = $this->faker->randomFloat(2);

        $q = $this->faker->randomNumber();

        return [
            'name'            => $this->faker->word,
            'sku'             => $this->faker->unique()->ean13,
            'qty'             => $q,
            'price'           => $basePrice,
            'base_price'      => $basePrice,
            'total'           => $q * $basePrice,
            'base_total'      => $q * $basePrice,
            'tax_amount'      => 0,
            'base_tax_amount' => 0,
        ];
    }
}
