<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductFlat as ProductFlatContract;

class ProductFlat extends Model implements ProductFlatContract
{
    
    protected $table = 'product_flat';

    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    protected $ignorableAttributes = [
        'pivot',
        'parent_id',
        'attribute_family_id',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    
    public function variants()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    
    public function getTypeInstance()
    {
        return $this->product->getTypeInstance();
    }
}
