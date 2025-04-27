<?php

namespace Webkul\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\User\Models\Role;

class RoleFactory extends Factory
{
    
    protected $model = Role::class;

    
    public function definition()
    {
        return [
            'name'            => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()),
            'permission_type' => $this->faker->randomElement(['custom', 'all']),
        ];
    }
}
