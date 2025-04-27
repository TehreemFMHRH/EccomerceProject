<?php

namespace Webkul\Product\Models;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Product\Contracts\ProductDownloadableSample as ProductDownloadableSampleContract;

class ProductDownloadableSample extends TranslatableModel implements ProductDownloadableSampleContract
{
    public $translatedAttributes = ['title'];

    protected $fillable = [
        'url',
        'file',
        'file_name',
        'type',
        'sort_order',
        'product_id',
    ];

    protected $with = ['translations'];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function file_url()
    {
        return Storage::url($this->file);
    }

    
    public function getFileUrlAttribute()
    {
        return $this->file_url();
    }

    
    public function toArray()
    {
        $array = parent::toArray();

        $translation = $this->translate(core()->getRequestedLocaleCode());

        $array['title'] = $translation ? $translation->title : '';

        $array['file_url'] = $this->file ? Storage::url($this->file) : null;

        return $array;
    }
}
