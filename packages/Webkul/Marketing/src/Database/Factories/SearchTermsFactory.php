<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketing\Models\SearchTerm;

class SearchTermsFactory extends Factory
{
    
    protected $model = SearchTerm::class;

    
    public function definition()
    {
        $terms = ['jackets', 'phone', 'computers', 'electronics'];

        return [
            'term'         => $terms[array_rand($terms)],
            'redirect_url' => $this->faker->url,
            'channel_id'   => core()->getCurrentChannel()->id,
            'locale'       => core()->getCurrentLocale()->code,
        ];
    }
}
