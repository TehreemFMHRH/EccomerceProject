<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductCustomizableOptionTranslation as ProductCustomizableOptionTranslationContract;

class ProductCustomizableOptionTranslation extends Model implements ProductCustomizableOptionTranslationContract
{
    
    public $timestamps = false;

    
    protected $fillable = ['label'];
}
