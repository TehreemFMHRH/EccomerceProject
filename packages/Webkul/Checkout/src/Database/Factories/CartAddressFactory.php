<?php

namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\CartAddress;

class CartAddressFactory extends Factory
{
    
    protected $model = CartAddress::class;

    
    public function definition(): array
    {
        return [
            'address'          => implode(PHP_EOL, [$this->faker->address()]),
            'company_name'     => $this->faker->company(),
            'first_name'       => preg_replace('/[^a-zA-Z ]/', '', $this->faker->firstName()),
            'last_name'        => preg_replace('/[^a-zA-Z ]/', '', $this->faker->lastName()),
            'email'            => $this->faker->safeEmail(),
            'country'          => $this->faker->countryCode(),
            'state'            => $this->faker->randomElement(['Delhi', 'Mumbai', 'Kolkata', 'Rajasthan']),
            'city'             => $this->faker->city(),
            'postcode'         => $this->faker->numerify('######'),
            'phone'            => $this->faker->e164PhoneNumber(),
            'address_type'     => CartAddress::ADDRESS_TYPE_BILLING,
        ];
    }
}
