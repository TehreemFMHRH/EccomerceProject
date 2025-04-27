<?php

namespace Webkul\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\CMS\Models\Page;

class PageFactory extends Factory
{
    
    protected $model = Page::class;

    
    public function definition(): array
    {
        return [
            'layout' => null,
        ];
    }
}
