<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\ProductImage as ProductImageContract;

class ProductImage extends Model implements ProductImageContract
{
    
    public $timestamps = false;

    
    protected $fillable = [
        'type',
        'path',
        'product_id',
        'position',
    ];

    
    protected $appends = ['url'];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function url()
    {
        return Storage::url($this->path);
    }

    
    public function getUrlAttribute()
    {
        return $this->url();
    }

    
    public function isCustomAttribute($attribute)
    {
        return $this->attribute_family->custom_attributes->pluck('code')->contains($attribute);
    }
}
