<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Contracts\SubscribersList as SubscribersListContract;
use Webkul\Core\Database\Factories\SubscriberListFactory;
use Webkul\Customer\Models\CustomerProxy;

class SubscribersList extends Model implements SubscribersListContract
{
    use HasFactory;

    
    protected $table = 'subscribers_list';

    
    protected $fillable = [
        'email',
        'is_subscribed',
        'token',
        'customer_id',
        'channel_id',
    ];

    
    protected $hidden = ['token'];

    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return SubscriberListFactory::new();
    }
}
