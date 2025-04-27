<?php

namespace Webkul\Admin\Validations;

use Illuminate\Contracts\Validation\Rule;
use Webkul\Product\Models\Product;

class ConfigurableUniqueSku implements Rule
{
    
    public function __construct(
        protected $currentIds = null,
    ) {}

    
    public function passes($attribute, $va)
    {
        return $this->isSkuExistsInProduct();
    }

    
    public function message()
    {
        return trans('admin::app.catalog.products.index.already-taken', ['name' => ':attribute']);
    }

    
    protected function isSkuExistsInProduct()
    {
        $requestedSkus = collect(request()->get('variants'))->pluck('sku')->toArray();

        $product = app(Product::class);

        
        if (
            $product->whereIn('sku', $requestedSkus)
                ->whereNotIn('id', $this->currentIds)
                ->exists()
        ) {
            return false;
        }

        
        return ! (count($requestedSkus) !== count(array_unique($requestedSkus)));
    }
}
