<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketing\Models\URLRewrite;

class UrlRewriteFactory extends Factory
{
    
    protected $model = URLRewrite::class;

    
    public function definition()
    {
        $entityTypes = ['product', 'category', 'cms_page'];
        $redirecType = [302, 301];

        return [
            'entity_type'    => $entityTypes[array_rand($entityTypes)],
            'request_path'   => $this->faker->url,
            'target_path'    => $this->faker->url,
            'redirect_type'  => $redirecType[array_rand($redirecType)],
            'locale'         => core()->getCurrentLocale()->code,
        ];
    }
}
