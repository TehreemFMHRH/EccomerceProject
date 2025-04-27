<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Contracts\Shipment as ShipmentContract;
use Webkul\Sales\Database\Factories\ShipmentFactory;

class Shipment extends Model implements ShipmentContract
{
    use HasFactory;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }

    
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItemProxy::modelClass());
    }

    
    public function inventory_source(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'inventory_source_id');
    }

    
    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    
    public function address(): BelongsTo
    {
        return $this->belongsTo(OrderAddressProxy::modelClass(), 'order_address_id')
            ->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING);
    }

    
    protected static function newFactory(): Factory
    {
        return ShipmentFactory::new();
    }
}
