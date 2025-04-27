<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductBundleOptionTranslation as ProductBundleOptionTranslationContract;
use Webkul\Product\Database\Factories\ProductBundleOptionTranslationFactory;

class ProductBundleOptionTranslation extends Model implements ProductBundleOptionTranslationContract
{
    
    public $timestamps = false;

    
    protected $fillable = ['label'];

    
    protected static function newFactory(): Factory
    {
        return ProductBundleOptionTranslationFactory::new();
    }
}
