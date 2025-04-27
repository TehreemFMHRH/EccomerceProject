<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\Product\Contracts\ProductInventory as ProductInventoryContract;
use Webkul\Product\Database\Factories\ProductInventoryFactory;

class ProductInventory extends Model implements ProductInventoryContract
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'qty',
        'product_id',
        'inventory_source_id',
        'vendor_id',
    ];

    
    public function inventory_source(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass());
    }

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return ProductInventoryFactory::new();
    }
}
