<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketing\Models\Template;

class TemplateFactory extends Factory
{
    
    protected $model = Template::class;

    
    public function definition()
    {
        $statusType = ['active', 'inactive', 'draft'];

        return [
            'name'    => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()),
            'status'  => $statusType[array_rand($statusType)],
            'content' => substr($this->faker->paragraph, 0, 50),
        ];
    }
}
