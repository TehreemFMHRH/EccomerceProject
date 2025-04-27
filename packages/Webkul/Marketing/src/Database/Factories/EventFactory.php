<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketing\Models\Event;

class EventFactory extends Factory
{
    
    protected $model = Event::class;

    
    public function definition()
    {
        return [
            'name'        => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()),
            'description' => substr($this->faker->paragraph, 0, 50),
            'date'        => $this->faker->date,
        ];
    }
}
