<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\ProductReviewAttachment as ProductReviewAttachmentContract;
use Webkul\Product\Database\Factories\ProductReviewAttachmentFactory;

class ProductReviewAttachment extends Model implements ProductReviewAttachmentContract
{
    use HasFactory;

    
    public $timestamps = false;

    
    protected $fillable = [
        'path',
        'review_id',
        'type',
        'mime_type',
    ];

    
    protected $appends = ['url'];

    
    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReviewProxy::modelClass());
    }

    
    public function url(): string
    {
        return Storage::url($this->path);
    }

    
    public function getUrlAttribute(): string
    {
        return $this->url();
    }

    
    protected static function newFactory(): Factory
    {
        return ProductReviewAttachmentFactory::new();
    }
}
