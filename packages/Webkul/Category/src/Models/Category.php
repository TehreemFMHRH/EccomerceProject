<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\NodeTrait;
use Shetabit\Visitor\Traits\Visitable;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Category\Contracts\Category as CategoryContract;
use Webkul\Category\Database\Factories\CategoryFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Product\Models\ProductProxy;

class Category extends TranslatableModel implements CategoryContract
{
    use HasFactory, NodeTrait, Visitable;

    
    public $translatedAttributes = [
        'name',
        'description',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    
    protected $fillable = [
        'position',
        'status',
        'display_mode',
        'parent_id',
        'additional',
    ];

    
    protected $with = ['translations'];

    
    protected $appends = ['logo_url', 'banner_url', 'url'];

    
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductProxy::modelClass(), 'product_categories');
    }

    
    public function filterableAttributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeProxy::modelClass(), 'category_filterable_attributes')
            ->with([
                'options' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'translations',
                'options.translations',
            ]);
    }

    
    public function getUrlAttribute()
    {
        if ($categoryTranslation = $this->translate(core()->getCurrentLocale()->code)) {
            return url($categoryTranslation->slug);
        }

        return url($this->translate(core()->getDefaultLocaleCodeFromDefaultChannel())?->slug);
    }

    
    public function getLogoUrlAttribute()
    {
        if (! $this->logo_path) {
            return;
        }

        return Storage::url($this->logo_path);
    }

    
    public function getBannerUrlAttribute()
    {
        if (! $this->banner_path) {
            return;
        }

        return Storage::url($this->banner_path);
    }

    
    protected function useFallback(): bool
    {
        return true;
    }

    
    protected function getFallbackLocale(?string $locale = null): ?string
    {
        if ($fallback = core()->getDefaultLocaleCodeFromDefaultChannel()) {
            return $fallback;
        }

        return parent::getFallbackLocale();
    }

    
    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }
}
