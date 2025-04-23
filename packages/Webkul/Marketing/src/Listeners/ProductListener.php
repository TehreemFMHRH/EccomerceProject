<?php

namespace Webkul\Marketing\Listeners;

use Illuminate\Support\Facades\Event;
use Webkul\Marketing\Models\URLRewrite;
use Webkul\Product\Models\Product;

class ProductListener
{
    /**
     * Permanent redirect code
     *
     * @var int
     */
    const PERMANENT_REDIRECT_CODE = 301;

    /**
     * After product is updated
     *
     * @param  int  $id
     * @return void
     */
    public function beforeUpdate($id)
    {
        $currentURLKey = request()->input('url_key');

        if (! $currentURLKey) {
            return;
        }

        $product = Product::find($id);

        if ($currentURLKey === $product->url_key) {
            return;
        }

        if (empty($product->url_key)) {
            /**
             * Delete category and product url rewrites
             * if already exists for the request path
             */
            $urlRewrites = URLRewrite::whereIn('entity_type', ['category', 'product'])
                ->where('request_path', $currentURLKey)
                ->get();

            foreach ($urlRewrites as $urlRewrite) {
                Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $urlRewrite->id);

                $urlRewrite->delete();

                Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $urlRewrite->id);
            }

            return;
        }

        /**
         * Delete category and product url rewrites
         * if already exists for the request path
         */
        $urlRewrites = URLRewrite::whereIn('entity_type', ['category', 'product'])
            ->where('request_path', $currentURLKey)
            ->get();

        foreach ($urlRewrites as $urlRewrite) {
            Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $urlRewrite->id);

            $urlRewrite->delete();

            Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $urlRewrite->id);
        }

        Event::dispatch('marketing.search_seo.url_rewrites.create.before');

        $urlRewrites = URLRewrite::create([
            'entity_type'   => 'product',
            'request_path'  => $product->url_key,
            'target_path'   => $currentURLKey ?? '',
            'locale'        => app()->getLocale(),
            'redirect_type' => $this::PERMANENT_REDIRECT_CODE,
        ]);

        Event::dispatch('marketing.search_seo.url_rewrites.create.after', $urlRewrites);
    }

    /**
     * Before product is deleted
     *
     * @param  int  $id
     * @return void
     */
    public function beforeDelete($id)
    {
        $product = Product::find($id);

        /**
         * Delete product url rewrites
         * if already exists for the request path
         */
        $urlRewrites = URLRewrite::whereIn('entity_type', ['category', 'product'])
            ->where('target_path', $product->url_key)
            ->get();

        foreach ($urlRewrites as $urlRewrite) {
            Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $urlRewrite->id);

            $urlRewrite->delete();

            Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $urlRewrite->id);
        }
    }
}
