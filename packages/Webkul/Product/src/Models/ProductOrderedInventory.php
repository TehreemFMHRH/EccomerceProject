<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Contracts\ProductOrderedInventory as ProductOrderedInventoryContract;
use Webkul\Product\Database\Factories\ProductOrderedInventoryFactory;

class ProductOrderedInventory extends Model implements ProductOrderedInventoryContract
{
    use HasFactory;

    
    public $timestamps = false;

    
    protected $fillable = [
        'qty',
        'product_id',
        'channel_id',
    ];

    
    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return ProductOrderedInventoryFactory::new();
    }
}
