<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Sitemap\Models\Sitemap;

class SitemapFactory extends Factory
{
    
    protected $model = Sitemap::class;

    
    public function definition()
    {
        return [
            'file_name' => strtolower(fake()->word()).'.xml',
            'path'      => '/',
        ];
    }
}
