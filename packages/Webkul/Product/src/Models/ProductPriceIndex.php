<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Models\CustomerGroupProxy;
use Webkul\Product\Contracts\ProductPriceIndex as ProductPriceIndexContract;

class ProductPriceIndex extends Model implements ProductPriceIndexContract
{
    
    protected $fillable = [
        'min_price',
        'regular_min_price',
        'max_price',
        'regular_max_price',
        'product_id',
        'channel_id',
        'customer_group_id',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    
    public function customer_group()
    {
        return $this->belongsTo(CustomerGroupProxy::modelClass());
    }
}
