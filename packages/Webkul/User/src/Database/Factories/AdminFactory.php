<?php

namespace Webkul\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\User\Models\Admin;

class AdminFactory extends Factory
{
    
    protected $model = Admin::class;

    
    public function definition()
    {
        return [
            'name'     => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()),
            'email'    => $this->faker->unique()->email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'role_id'  => 1,
            'status'   => 1,
        ];
    }
}
