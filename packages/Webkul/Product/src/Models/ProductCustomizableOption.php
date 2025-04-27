<?php

namespace Webkul\Product\Models;

use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Product\Contracts\ProductCustomizableOption as ProductCustomizableOptionContract;

class ProductCustomizableOption extends TranslatableModel implements ProductCustomizableOptionContract
{
    
    public $timestamps = false;

    
    public $translatedAttributes = ['label'];

    
    protected $fillable = [
        'is_required',
        'max_characters',
        'product_id',
        'sort_order',
        'supported_file_extensions',
        'type',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function customizable_option_prices()
    {
        return $this->hasMany(ProductCustomizableOptionPriceProxy::modelClass())
            ->orderBy('sort_order');
    }
}
