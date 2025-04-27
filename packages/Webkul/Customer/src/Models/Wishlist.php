<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Contracts\Wishlist as WishlistContract;
use Webkul\Customer\Database\Factories\CustomerWishlistFactory;
use Webkul\Product\Models\ProductProxy;

class Wishlist extends Model implements WishlistContract
{
    use HasFactory;

    
    protected $table = 'wishlist_items';

    
    protected $casts = [
        'additional' => 'array',
    ];

    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function channel()
    {
        return $this->hasOne(ChannelProxy::modelClass(), 'id', 'channel_id');
    }

    
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    
    protected static function newFactory()
    {
        return CustomerWishlistFactory::new();
    }
}
