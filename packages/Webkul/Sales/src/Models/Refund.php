<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Sales\Contracts\Refund as RefundContract;
use Webkul\Sales\Database\Factories\RefundFactory;

class Refund extends Model implements RefundContract
{
    use HasFactory;

    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    protected $statusLabel = [];

    
    public function getStatusLabelAttribute()
    {
        return $this->statusLabel[$this->state] ?? '';
    }

    
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }

    
    public function items(): HasMany
    {
        return $this->hasMany(RefundItemProxy::modelClass())
            ->whereNull('parent_id');
    }

    
    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    
    public function channel(): MorphTo
    {
        return $this->morphTo();
    }

    
    public function address(): BelongsTo
    {
        return $this->belongsTo(OrderAddressProxy::modelClass(), 'order_address_id');
    }

    
    protected static function newFactory(): Factory
    {
        return RefundFactory::new();
    }
}
