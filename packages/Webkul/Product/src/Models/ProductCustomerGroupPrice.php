<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerGroupProxy;
use Webkul\Product\Contracts\ProductCustomerGroupPrice as ProductCustomerGroupPriceContract;
use Webkul\Product\Database\Factories\ProductCustomerGroupPriceFactory;

class ProductCustomerGroupPrice extends Model implements ProductCustomerGroupPriceContract
{
    use HasFactory;

    
    protected $fillable = [
        'qty',
        'value_type',
        'value',
        'product_id',
        'customer_group_id',
        'unique_id',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function customer_group()
    {
        return $this->belongsTo(CustomerGroupProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return ProductCustomerGroupPriceFactory::new();
    }
}
