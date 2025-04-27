<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\RefundItem as RefundItemContract;

class RefundItem extends Model implements RefundItemContract
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'additional' => 'array',
    ];

    
    public function refund()
    {
        return $this->belongsTo(RefundProxy::modelClass());
    }

    
    public function order_item()
    {
        return $this->belongsTo(OrderItemProxy::modelClass());
    }

    
    public function product()
    {
        return $this->morphTo();
    }

    
    public function child()
    {
        return $this->hasOne(RefundItemProxy::modelClass(), 'parent_id');
    }
}
