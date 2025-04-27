<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Product\Contracts\ProductReview as ProductReviewContract;
use Webkul\Product\Database\Factories\ProductReviewFactory;

class ProductReview extends Model implements ProductReviewContract
{
    use HasFactory;

    protected $fillable = [
        'comment',
        'title',
        'rating',
        'status',
        'product_id',
        'customer_id',
        'name',
    ];

    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }

    
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function images(): HasMany
    {
        return $this->hasMany(ProductReviewAttachmentProxy::modelClass(), 'review_id');
    }

    
    protected static function newFactory(): Factory
    {
        return ProductReviewFactory::new();
    }
}
