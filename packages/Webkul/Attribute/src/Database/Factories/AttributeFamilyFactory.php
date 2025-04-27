<?php

namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\AttributeFamily;

class AttributeFamilyFactory extends Factory
{
    
    protected $model = AttributeFamily::class;

    
    public function definition(): array
    {
        return [
            'name'            => $this->faker->word(),
            'code'            => $this->faker->word(),
            'is_user_defined' => random_int(0, 1),
            'status'          => 0,
        ];
    }
}
