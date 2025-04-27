<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\CoreConfig;

class CoreConfigFactory extends Factory
{
    
    protected $model = CoreConfig::class;

    
    public function definition(): array
    {
        return [
            'channel_code' => core()->getCurrentChannelCode(),
        ];
    }
}
