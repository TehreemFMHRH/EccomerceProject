<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderComment as OrderCommentContract;

class OrderComment extends Model implements OrderCommentContract
{
    protected $fillable = [
        'comment',
        'customer_notified',
        'order_id',
    ];

    
    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
}
