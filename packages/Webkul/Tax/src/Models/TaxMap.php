<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Tax\Contracts\TaxMap as TaxMapContract;
use Webkul\Tax\Database\Factories\TaxMapFactory;

class TaxMap extends Model implements TaxMapContract
{
    use HasFactory;

    
    protected $table = 'tax_categories_tax_rates';

    protected $fillable = [
        'tax_category_id',
        'tax_rate_id',
    ];

    
    protected static function newFactory(): Factory
    {
        return TaxMapFactory::new();
    }
}
