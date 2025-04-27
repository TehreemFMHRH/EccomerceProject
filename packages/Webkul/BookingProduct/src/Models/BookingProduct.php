<?php

namespace Webkul\BookingProduct\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\BookingProduct\Contracts\BookingProduct as BookingProductContract;
use Webkul\Product\Models\ProductProxy;

class BookingProduct extends Model implements BookingProductContract
{
    
    protected $fillable = [
        'location',
        'show_location',
        'type',
        'qty',
        'available_every_week',
        'available_from',
        'available_to',
        'product_id',
    ];

    
    protected $with = [
        'default_slot',
        'appointment_slot',
        'event_tickets',
        'rental_slot',
        'table_slot',
    ];

    
    protected $casts = [
        'available_from' => 'datetime',
        'available_to'   => 'datetime',
    ];

    
    public function default_slot(): HasOne
    {
        return $this->hasOne(BookingProductDefaultSlotProxy::modelClass());
    }

    
    public function appointment_slot(): HasOne
    {
        return $this->hasOne(BookingProductAppointmentSlotProxy::modelClass());
    }

    
    public function event_tickets(): HasMany
    {
        return $this->hasMany(BookingProductEventTicketProxy::modelClass());
    }

    
    public function rental_slot(): HasOne
    {
        return $this->hasOne(BookingProductRentalSlotProxy::modelClass());
    }

    
    public function table_slot(): HasOne
    {
        return $this->hasOne(BookingProductTableSlotProxy::modelClass());
    }

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }
}
