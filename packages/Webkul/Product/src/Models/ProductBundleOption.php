<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Product\Contracts\ProductBundleOption as ProductBundleOptionContract;
use Webkul\Product\Database\Factories\ProductBundleOptionsFactory;

class ProductBundleOption extends TranslatableModel implements ProductBundleOptionContract
{
    use HasFactory;

    
    public $timestamps = false;

    
    public $translatedAttributes = ['label'];

    
    protected $fillable = [
        'type',
        'is_required',
        'sort_order',
        'product_id',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function bundle_option_products()
    {
        return $this->hasMany(ProductBundleOptionProductProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return ProductBundleOptionsFactory::new();
    }
}
