<?php

namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Checkout\Contracts\Cart as CartContract;
use Webkul\Checkout\Database\Factories\CartFactory;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Models\CustomerProxy;

class Cart extends Model implements CartContract
{
    use HasFactory;

    
    protected $table = 'cart';

    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    protected $casts = [
        'additional' => 'json',
    ];

    
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    
    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartItemProxy::modelClass())
            ->whereNull('parent_id');
    }

    
    public function all_items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartItemProxy::modelClass());
    }

    
    public function billing_address(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CartAddressProxy::modelClass())->where('address_type', CartAddress::ADDRESS_TYPE_BILLING);
    }

    
    public function shipping_address(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CartAddressProxy::modelClass())->where('address_type', CartAddress::ADDRESS_TYPE_SHIPPING);
    }

    
    public function shipping_rates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartShippingRateProxy::modelClass());
    }

    
    public function selected_shipping_rate()
    {
        return $this->shipping_rates
            ->where('method', $this->shipping_method);
    }

    
    public function getSelectedShippingRateAttribute()
    {
        return $this->selected_shipping_rate()
            ->first();
    }

    
    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CartPaymentProxy::modelClass());
    }

    
    public function haveStockableItems(): bool
    {
        foreach ($this->items as $item) {
            if ($item->product->isStockable()) {
                return true;
            }
        }

        return false;
    }

    
    public function hasDownloadableItems(): bool
    {
        return $this->items->pluck('type')->contains('downloadable');
    }

    
    public function hasProductsWithQuantityBox(): bool
    {
        foreach ($this->items as $item) {
            if ($item->getTypeInstance()->showQuantityBox()) {
                return true;
            }
        }

        return false;
    }

    
    public function hasGuestCheckoutItems(): bool
    {
        foreach ($this->items as $item) {
            if (! $item->product->getAttribute('guest_checkout')) {
                return false;
            }
        }

        return true;
    }

    
    protected static function newFactory(): Factory
    {
        return CartFactory::new();
    }
}
