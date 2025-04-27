<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Product\Models\ProductBundleOptionProduct;
use Webkul\Product\Models\ProductGroupedProduct;
use Webkul\Product\Models\Product;

class FPCProductListener
{
    
    public function afterUpdate($product)
    {
        $urls = $this->getForgettableUrls($product);

        ResponseCache::forget($urls);
    }

    
    public function beforeDelete($productId)
    {
        $product = Product::find($productId);

        $urls = $this->getForgettableUrls($product);

        ResponseCache::forget($urls);
    }

    
    public function getForgettableUrls($product)
    {
        $urls = [];

        $products = $this->getAllRelatedProducts($product);

        foreach ($products as $product) {
            $urls[] = '/'.$product->url_key;
        }

        return $urls;
    }

    
    public function getAllRelatedProducts($product)
    {
        $products = [$product];

        if ($product->type == 'simple') {
            if ($product->parent_id) {
                $products[] = $product->parent;
            }

            $products = array_merge(
                $products,
                $this->getParentBundleProducts($product),
                $this->getParentGroupProducts($product)
            );
        } elseif ($product->type == 'configurable') {
            $products = [];

            
            foreach ($product->variants()->get() as $variant) {
                $products[] = $variant;
            }

            $products[] = $product;
        }

        return $products;
    }

    
    public function getParentBundleProducts($product)
    {
        $bundleOptionProducts = ProductBundleOptionProduct::where([
            'product_id' => $product->id,
        ])->get();

        $products = [];

        foreach ($bundleOptionProducts as $bundleOptionProduct) {
            $products[] = $bundleOptionProduct->bundle_option->product;
        }

        return $products;
    }

    
    public function getParentGroupProducts($product)
    {
        $groupedOptionProducts = ProductGroupedProduct::where([
            'associated_product_id' => $product->id,
        ])->get();

        $products = [];

        foreach ($groupedOptionProducts as $groupedOptionProduct) {
            $products[] = $groupedOptionProduct->product;
        }

        return $products;
    }
}
