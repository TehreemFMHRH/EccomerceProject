<?php

namespace Webkul\Admin\Validations;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\CategoryTranslationProxy;
use Webkul\Product\Models\Product;

class ProductCategoryUniqueSlug implements Rule
{
    
    protected $reservedSlugs = [
        'categories',
    ];

    
    protected $isSlugReserved = false;

    
    public function __construct(
        protected $tableName = null,
        protected $i = null
    ) {}

    
    public function passes($attribute, $va)
    {
        if (in_array($va, $this->reservedSlugs)) {
            return ! ($this->isSlugReserved = true);
        }

        return $this->isSlugUnique($va);
    }

    
    public function message()
    {
        if ($this->isSlugReserved) {
            return trans('admin::app.validations.slug-reserved');
        }

        return trans('admin::app.validations.slug-being-used');
    }

    
    protected function isSlugUnique($slug)
    {
        return ! $this->isSlugExistsInCategories($slug) && ! $this->isSlugExistsInProducts($slug);
    }

    
    protected function isSlugExistsInCategories($slug)
    {
        if (
            $this->tableName
            && $this->id
            && $this->tableName === 'category_translations'
        ) {
            return CategoryTranslationProxy::modelClass()::where('category_id', '<>', $this->id)
                ->where('slug', $slug)
                ->limit(1)
                ->select(DB::raw(1))
                ->exists();
        }

        return CategoryTranslationProxy::modelClass()::where('slug', $slug)
            ->limit(1)
            ->select(DB::raw(1))
            ->exists();
    }

    
    protected function isSlugExistsInProducts($slug)
    {
        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $product = app(Product::class)
            ->setSearchEngine($searchEngine ?? 'database')
            ->findBySlug($slug);

        if (
            $product
            && $this->tableName
            && $this->id
            && $this->tableName === 'products'
            && $this->id == $product->id
        ) {
            $product = null;
        }

        return (bool) $product;
    }
}
