<?php

namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Shetabit\Visitor\Traits\Visitor;
use Webkul\Checkout\Models\CartProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\SubscribersListProxy;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Customer\Database\Factories\CustomerFactory;
use Webkul\Product\Models\ProductReviewProxy;
use Webkul\Sales\Models\InvoiceProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Shop\Mail\Customer\ResetPasswordNotification;

class Customer extends Authenticatable implements CustomerContract
{
    use HasApiTokens, HasFactory, Notifiable, Visitor;

    
    protected $table = 'customers';

    
    protected $casts = [
        'subscribed_to_news_letter' => 'boolean',
    ];

    
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'email',
        'phone',
        'password',
        'api_token',
        'token',
        'customer_group_id',
        'channel_id',
        'subscribed_to_news_letter',
        'status',
        'is_verified',
        'is_suspended',
    ];

    
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    
    protected $appends = ['image_url'];

    
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

    
    public function getNameAttribute(): string
    {
        return ucfirst($this->first_name).' '.ucfirst($this->last_name);
    }

    
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    
    public function emailExists($e): bool
    {
        $results = $this->where('email', $e);

        if ($results->count() === 0) {
            return false;
        }

        return true;
    }

    
    public function group()
    {
        return $this->belongsTo(CustomerGroupProxy::modelClass(), 'customer_group_id');
    }

    
    public function addresses()
    {
        return $this->hasMany(CustomerAddressProxy::modelClass(), 'customer_id');
    }

    
    public function default_address()
    {
        return $this->hasOne(CustomerAddressProxy::modelClass(), 'customer_id')
            ->where('default_address', 1);
    }

    
    public function invoices()
    {
        return $this->hasManyThrough(InvoiceProxy::modelClass(), OrderProxy::modelClass());
    }

    
    public function wishlist_items()
    {
        return $this->hasMany(WishlistProxy::modelClass(), 'customer_id');
    }

    
    public function isWishlistShared(): bool
    {
        return (bool) $this->wishlist_items()->where('shared', 1)->first();
    }

    
    public function getWishlistSharedLink()
    {
        return $this->isWishlistShared()
            ? URL::signedRoute('shop.customer.wishlist.shared', ['id' => $this->id])
            : null;
    }

    
    public function all_carts()
    {
        return $this->hasMany(CartProxy::modelClass(), 'customer_id');
    }

    
    public function inactive_carts()
    {
        return $this->hasMany(CartProxy::modelClass(), 'customer_id')
            ->where('is_active', 0);
    }

    
    public function active_carts()
    {
        return $this->hasMany(CartProxy::modelClass(), 'customer_id')
            ->where('is_active', 1);
    }

    
    public function orders()
    {
        return $this->hasMany(OrderProxy::modelClass(), 'customer_id');
    }

    
    public function reviews()
    {
        return $this->hasMany(ProductReviewProxy::modelClass(), 'customer_id');
    }

    
    public function notes()
    {
        return $this->hasMany(CustomerNoteProxy::modelClass(), 'customer_id');
    }

    
    public function subscription()
    {
        return $this->hasOne(SubscribersListProxy::modelClass(), 'customer_id');
    }

    
    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass(), 'channel_id');
    }

    
    protected static function newFactory()
    {
        return CustomerFactory::new();
    }
}
