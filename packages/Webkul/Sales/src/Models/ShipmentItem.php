<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\ShipmentItem as ShipmentItemContract;

class ShipmentItem extends Model implements ShipmentItemContract
{
    
    protected $guarded = [
        'id',
        'child',
        'created_at',
        'updated_at',
    ];

    
    protected $casts = [
        'additional' => 'array',
    ];

    
    public function getTypeInstance()
    {
        return $this->order_item->getTypeInstance();
    }

    
    public function shipment()
    {
        return $this->belongsTo(ShipmentProxy::modelClass());
    }

    
    public function order_item()
    {
        return $this->belongsTo(OrderItemProxy::modelClass());
    }

    
    public function product()
    {
        return $this->morphTo();
    }

    
    public function getTypeAttribute()
    {
        return $this->order_item->type;
    }
}
