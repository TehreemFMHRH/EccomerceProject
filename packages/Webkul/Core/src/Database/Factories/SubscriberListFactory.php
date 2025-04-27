<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\SubscribersList;

class SubscriberListFactory extends Factory
{
    
    protected $model = SubscribersList::class;

    
    public function definition(): array
    {
        return [
            'email'         => $this->faker->safeEmail(),
            'channel_id'    => core()->getCurrentChannel()->id,
            'is_subscribed' => 1,
            'token'         => uniqid(),
        ];
    }
}
