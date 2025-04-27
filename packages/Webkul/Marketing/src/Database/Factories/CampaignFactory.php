<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketing\Models\Campaign;

class CampaignFactory extends Factory
{
    
    protected $model = Campaign::class;

    
    public function definition()
    {
        return [
            'name'    => fake()->name(),
            'subject' => fake()->title(),
        ];
    }
}
