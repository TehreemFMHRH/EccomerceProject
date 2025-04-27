<?php

namespace Webkul\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Marketing\Contracts\SearchSynonym as SearchSynonymContract;
use Webkul\Marketing\Database\Factories\SearchSynonymFactory;

class SearchSynonym extends Model implements SearchSynonymContract
{
    use HasFactory;

    
    protected $fillable = [
        'name',
        'terms',
    ];

    
    protected static function newFactory(): Factory
    {
        return SearchSynonymFactory::new();
    }
}
