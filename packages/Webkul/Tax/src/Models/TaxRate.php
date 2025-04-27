<?php

namespace Webkul\Tax\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Tax\Contracts\TaxRate as TaxRateContract;
use Webkul\Tax\Database\Factories\TaxRateFactory;

class TaxRate extends Model implements TaxRateContract
{
    use HasFactory;

    
    protected $table = 'tax_rates';

    protected $fillable = [
        'identifier',
        'is_zip',
        'zip_code',
        'zip_from',
        'zip_to',
        'state',
        'country',
        'tax_rate',
    ];

    public function tax_categories(): BelongsToMany
    {
        return $this->belongsToMany(TaxCategoryProxy::modelClass(), 'tax_categories_tax_rates', 'tax_rate_id', 'id');
    }

    
    protected static function newFactory(): Factory
    {
        return TaxRateFactory::new();
    }
}
