<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Contracts\AttributeOption as AttributeOptionContract;
use Webkul\Attribute\Database\Factories\AttributeOptionFactory;
use Webkul\Core\Eloquent\TranslatableModel;

class AttributeOption extends TranslatableModel implements AttributeOptionContract
{
    use HasFactory;

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $fillable = [
        'admin_name',
        'swatch_value',
        'sort_order',
        'attribute_id',
    ];

    
    protected $appends = [
        'swatch_value_url',
    ];

    
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }

    
    public function swatch_value_url()
    {
        if (
            $this->swatch_value
            && $this->attribute->swatch_type == 'image'
        ) {
            return url('cache/small/'.$this->swatch_value);
        }

        return null;
    }

    
    public function getSwatchValueUrlAttribute()
    {
        return $this->swatch_value_url();
    }

    
    protected static function newFactory(): Factory
    {
        return AttributeOptionFactory::new();
    }
}
