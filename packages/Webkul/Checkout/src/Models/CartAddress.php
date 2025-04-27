<?php

namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Checkout\Contracts\CartAddress as CartAddressContract;
use Webkul\Checkout\Database\Factories\CartAddressFactory;
use Webkul\Core\Models\Address;


class CartAddress extends Address implements CartAddressContract
{
    use HasFactory;

    
    public const ADDRESS_TYPE_SHIPPING = 'cart_shipping';

    
    public const ADDRESS_TYPE_BILLING = 'cart_billing';

    
    protected $attributes = [
        'address_type' => self::ADDRESS_TYPE_BILLING,
    ];

    
    protected static function boot(): void
    {
        static::addGlobalScope('address_type', static function (Builder $builder) {
            $builder->whereIn('address_type', [
                self::ADDRESS_TYPE_BILLING,
                self::ADDRESS_TYPE_SHIPPING,
            ]);
        });

        parent::boot();
    }

    
    public function shipping_rates(): HasMany
    {
        return $this->hasMany(CartShippingRateProxy::modelClass());
    }

    
    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return CartAddressFactory::new();
    }
}
