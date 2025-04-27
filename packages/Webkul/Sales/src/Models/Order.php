<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Checkout\Models\CartProxy;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Database\Factories\OrderFactory;

class Order extends Model implements OrderContract
{
    use HasFactory;

    protected $dates = ['created_at'];

    protected $appends = ['datetime'];

    
    public const STATUS_PENDING = 'pending';

    
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    
    public const STATUS_PROCESSING = 'processing';

    
    public const STATUS_COMPLETED = 'completed';

    
    public const STATUS_CANCELED = 'canceled';

    
    public const STATUS_CLOSED = 'closed';

    
    public const STATUS_FRAUD = 'fraud';

    
    protected $guarded = [
        'id',
        'items',
        'shipping_address',
        'billing_address',
        'customer',
        'channel',
        'payment',
        'created_at',
        'updated_at',
    ];

    protected $statusLabel = [
        self::STATUS_PENDING         => 'Pending',
        self::STATUS_PENDING_PAYMENT => 'Pending Payment',
        self::STATUS_PROCESSING      => 'Processing',
        self::STATUS_COMPLETED       => 'Completed',
        self::STATUS_CANCELED        => 'Canceled',
        self::STATUS_CLOSED          => 'Closed',
        self::STATUS_FRAUD           => 'Fraud',
    ];

    
    public function getCustomerFullNameAttribute(): string
    {
        return $this->customer_first_name.' '.$this->customer_last_name;
    }

    
    public function getStatusLabelAttribute()
    {
        return $this->statusLabel[$this->status];
    }

    
    public function getBaseTotalDueAttribute()
    {
        return $this->base_grand_total - $this->base_grand_total_invoiced;
    }

    
    public function getTotalDueAttribute()
    {
        return $this->grand_total - $this->grand_total_invoiced;
    }

    
    public function getDatetimeAttribute()
    {
        return $this->created_at?->diffForHumans();
    }

    
    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartProxy::modelClass());
    }

    
    public function items(): HasMany
    {
        return $this->hasMany(OrderItemProxy::modelClass())
            ->whereNull('parent_id');
    }

    
    public function comments(): HasMany
    {
        return $this->hasMany(OrderCommentProxy::modelClass());
    }

    
    public function all_items(): HasMany
    {
        return $this->hasMany(OrderItemProxy::modelClass());
    }

    
    public function downloadable_link_purchased()
    {
        return $this->hasMany(DownloadableLinkPurchasedProxy::modelClass());
    }

    
    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentProxy::modelClass());
    }

    
    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceProxy::modelClass());
    }

    
    public function refunds(): HasMany
    {
        return $this->hasMany(RefundProxy::modelClass());
    }

    
    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransactionProxy::modelClass());
    }

    
    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    
    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddressProxy::modelClass());
    }

    
    public function payment(): HasOne
    {
        return $this->hasOne(OrderPaymentProxy::modelClass());
    }

    
    public function billing_address()
    {
        return $this->addresses
            ->where('address_type', OrderAddress::ADDRESS_TYPE_BILLING);
    }

    
    public function getBillingAddressAttribute()
    {
        return $this->billing_address()
            ->first();
    }

    
    public function shipping_address()
    {
        return $this->addresses
            ->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING);
    }

    
    public function getShippingAddressAttribute()
    {
        return $this->shipping_address()
            ->first();
    }

    
    public function channel()
    {
        return $this->morphTo();
    }

    
    public function haveStockableItems(): bool
    {
        foreach ($this->items as $item) {
            if ($item->getTypeInstance()->isStockable()) {
                return true;
            }
        }

        return false;
    }

    
    public function canShip(): bool
    {
        foreach ($this->items as $item) {
            if (
                $item->canShip()
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        return false;
    }

    
    public function canInvoice(): bool
    {
        foreach ($this->items as $item) {
            if (
                $item->canInvoice()
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        return false;
    }

    
    public function hasOpenInvoice(): bool
    {
        $pendingInvoice = $this->invoices()->where('state', 'pending')
            ->orWhere('state', 'pending_payment')
            ->first();

        if ($pendingInvoice) {
            return true;
        }

        return false;
    }

    
    public function canCancel(): bool
    {
        $pendingInvoice = $this->invoices->where('state', 'pending')->first();

        if ($pendingInvoice) {
            return true;
        }

        foreach ($this->items as $item) {
            if (
                $item->canCancel()
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        return false;
    }

    
    public function canRefund(): bool
    {
        foreach ($this->items as $item) {
            if (
                $item->qty_to_refund > 0
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        if ($this->base_grand_total_invoiced - $this->base_grand_total_refunded - $this->refunds()->sum('base_adjustment_fee') > 0) {
            return true;
        }

        return false;
    }

    
    public function canReorder(): bool
    {
        if ($this->is_guest) {
            return false;
        }

        foreach ($this->items as $item) {
            if (! $item->product?->getTypeInstance()->isSaleable()) {
                return false;
            }
        }

        return true;
    }

    
    protected static function newFactory(): Factory
    {
        return OrderFactory::new();
    }
}
