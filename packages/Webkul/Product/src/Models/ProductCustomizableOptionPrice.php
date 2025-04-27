<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductCustomizableOptionPrice as ProductCustomizableOptionPriceContract;

class ProductCustomizableOptionPrice extends Model implements ProductCustomizableOptionPriceContract
{
    
    public $timestamps = false;

    
    protected $fillable = [
        'is_default',
        'is_user_defined',
        'label',
        'price',
        'product_customizable_option_id',
        'qty',
        'sort_order',
    ];

    
    public function customizable_option()
    {
        return $this->belongsTo(ProductBundleOptionProxy::modelClass(), 'product_customizable_option_id');
    }
}
