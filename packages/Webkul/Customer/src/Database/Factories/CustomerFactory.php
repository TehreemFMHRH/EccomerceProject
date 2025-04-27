<?php

namespace Webkul\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Webkul\Customer\Models\Customer;

class CustomerFactory extends Factory
{
    
    protected $model = Customer::class;

    
    protected $states = [
        'male',
        'female',
    ];

    
    public function definition(): array
    {
        return [
            'first_name'        => preg_replace('/[^a-zA-Z ]/', '', $this->faker->firstName()),
            'last_name'         => preg_replace('/[^a-zA-Z ]/', '', $this->faker->lastName()),
            'gender'            => Arr::random(['male', 'female', 'other']),
            'email'             => $this->faker->safeEmail(),
            'status'            => 1,
            'password'          => Hash::make($this->faker->password),
            'customer_group_id' => 2,
            'channel_id'        => 1,
            'is_verified'       => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    
    public function male(): CustomerFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'gender' => 'Male',
            ];
        });
    }

    
    public function female(): CustomerFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'gender' => 'Female',
            ];
        });
    }
}
