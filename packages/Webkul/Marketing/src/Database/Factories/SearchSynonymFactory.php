<?php

namespace Webkul\Marketing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketing\Models\SearchSynonym;

class SearchSynonymFactory extends Factory
{
    
    protected $model = SearchSynonym::class;

    
    public function definition()
    {
        $terms = ['jackets', 'shoes', 'footwear',  'phone', 'computers', 'electronics'];

        return [
            'terms' => $terms[array_rand($terms)],
            'name'  => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()),
        ];
    }
}
