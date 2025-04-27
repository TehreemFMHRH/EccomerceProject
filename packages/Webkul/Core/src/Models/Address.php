<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Contracts\Address as AddressContract;
use Webkul\Customer\Models\Customer;

abstract class Address extends Model implements AddressContract
{
    
    protected $table = 'addresses';

    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    
    protected $casts = [
        'use_for_shipping' => 'boolean',
        'default_address'  => 'boolean',
    ];

    
    public function getNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
