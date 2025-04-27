<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\CompareItem as CompareItemContract;
use Webkul\Customer\Database\Factories\CompareItemFactory;
use Webkul\Product\Models\ProductProxy;

class CompareItem extends Model implements CompareItemContract
{
    use HasFactory;

    
    protected $guarded = [];

    
    protected $table = 'compare_items';

    
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    
    protected static function newFactory(): Factory
    {
        return CompareItemFactory::new();
    }
}
