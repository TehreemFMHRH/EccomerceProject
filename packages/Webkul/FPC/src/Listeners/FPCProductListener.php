<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Product\Models\ProductBundleOptionProduct;
use Webkul\Product\Models\ProductGroupedProduct;
use Webkul\Product\Models\Product;

class FPCProductListener
{
    /**
     * Update or create product page cache
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function afterUpdate($product)
    {
        $urls = $this->getForgettableUrls($product);

        ResponseCache::forget($urls);
    }

    /**
     * Delete product page c
     *
     * @param  int  $productId
     * @return void
     */
    public function beforeDelete($productId)
    {
        $product = Product::find($productId);

        $urls = $this->getForgettableUrls($product);

        ResponseCache::forget($urls);
    }

    /**
     * Returns product urls
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    public function getForgettableUrls($product)
    {
        $urls = [];

        $products = $this->getAllRelatedProducts($product);

        foreach ($products as $product) {
            $urls[] = '/'.$product->url_key;
        }

        return $urls;
    }

    /**
     * Returns parents bundle products associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
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

            /**
             * Fetching fresh variants.
             */
            foreach ($product->variants()->get() as $variant) {
                $products[] = $variant;
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * Returns parents bundle products associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
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

    /**
     * Returns parents group products associated with simple product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
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
