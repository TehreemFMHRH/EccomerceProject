<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Core\Models\Address;
use Webkul\Sales\Contracts\OrderAddress as OrderAddressContract;
use Webkul\Sales\Database\Factories\OrderAddressFactory;


class OrderAddress extends Address implements OrderAddressContract
{
    use HasFactory;

    
    public const ADDRESS_TYPE_SHIPPING = 'order_shipping';

    
    public const ADDRESS_TYPE_BILLING = 'order_billing';

    
    protected $attributes = [
        'address_type' => self::ADDRESS_TYPE_BILLING,
    ];

    
    protected static function boot(): void
    {
        static::addGlobalScope('address_type', function (Builder $builder) {
            $builder->whereIn('address_type', [
                self::ADDRESS_TYPE_BILLING,
                self::ADDRESS_TYPE_SHIPPING,
            ]);
        });

        static::creating(static function ($addr) {
            switch ($addr->address_type) {
                case CartAddress::ADDRESS_TYPE_BILLING:
                    $addr->address_type = self::ADDRESS_TYPE_BILLING;

                    break;

                case CartAddress::ADDRESS_TYPE_SHIPPING:
                    $addr->address_type = self::ADDRESS_TYPE_SHIPPING;

                    break;
            }
        });

        parent::boot();
    }

    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    
    protected static function newFactory(): Factory
    {
        return OrderAddressFactory::new();
    }
}
