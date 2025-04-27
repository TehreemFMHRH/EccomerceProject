<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Contracts\ProductInventoryIndex as ProductInventoryIndexContract;

class ProductInventoryIndex extends Model implements ProductInventoryIndexContract
{
    
    protected $fillable = [
        'qty',
        'product_id',
        'channel_id',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }
}
