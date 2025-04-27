<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Sales\Contracts\Invoice as InvoiceContract;
use Webkul\Sales\Database\Factories\InvoiceFactory;
use Webkul\Sales\Traits\InvoiceReminder;
use Webkul\Sales\Traits\PaymentTerm;

class Invoice extends Model implements InvoiceContract
{
    use HasFactory, InvoiceReminder, PaymentTerm;

    
    public const STATUS_PENDING = 'pending';

    
    public const STATUS_PAID = 'paid';

    
    public const STATUS_REFUNDED = 'refunded';

    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    protected $statusLabel = [
        self::STATUS_PENDING  => 'Pending',
        self::STATUS_PAID     => 'Paid',
        self::STATUS_REFUNDED => 'Refunded',
    ];

    
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
        return $this->hasMany(InvoiceItemProxy::modelClass())
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
        return $this->belongsTo(OrderAddressProxy::modelClass(), 'order_address_id')
            ->where('address_type', OrderAddress::ADDRESS_TYPE_BILLING);
    }

    
    protected static function newFactory(): Factory
    {
        return InvoiceFactory::new();
    }
}
