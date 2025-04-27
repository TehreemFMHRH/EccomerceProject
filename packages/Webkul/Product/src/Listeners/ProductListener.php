<?php

namespace Webkul\Product\Listeners;

use Illuminate\Support\Facades\Bus;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Jobs\ElasticSearch\DeleteIndex as DeleteElasticSearchIndexJob;
use Webkul\Product\Jobs\ElasticSearch\UpdateCreateIndex as UpdateCreateElasticSearchIndexJob;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;
use Webkul\Product\Jobs\UpdateCreatePriceIndex as UpdateCreatePriceIndexJob;
use Webkul\Product\Models\ProductBundleOptionProduct;
use Webkul\Product\Models\ProductGroupedProduct;
use Webkul\Product\Models\Product;

class ProductListener
{

    public function __construct(



        protected FlatIndexer $flatIndexer
    ) {}


    public function afterCreate($p)
    {
        $this->flatIndexer->refresh($p);

        $pIds = $this->getAllRelatedProductIds($p);

        UpdateCreateElasticSearchIndexJob::dispatch($pIds);
    }


    public function afterUpdate($p)
    {
        $this->flatIndexer->refresh($p);

        $pIds = $this->getAllRelatedProductIds($p);

        Bus::chain([
            new UpdateCreateInventoryIndexJob($pIds),
            new UpdateCreatePriceIndexJob($pIds),
            new UpdateCreateElasticSearchIndexJob($pIds),
        ])->dispatch();
    }


    public function beforeDelete($pId)
    {
        if (core()->getConfigData('catalog.products.search.engine') != 'elastic') {
            return;
        }

        $res = DB::select("SELECT * FROM products WHERE id = $pId LIMIT 1");
$p = !empty($res) ? $res[0] : null;

        if (! $p) {
            return;
        }

        $pIds = $this->getAllRelatedProductIds($p);

        DeleteElasticSearchIndexJob::dispatch($pIds);
    }


    public function getAllRelatedProductIds($p)
    {
        $pIds = [$p->id];

        if ($p->type == 'simple') {
            if ($p->parent_id) {
                $pIds[] = $p->parent_id;
            }

            $pIds = array_merge(
                $pIds,
                $this->getParentBundleProductIds($p),
                $this->getParentGroupProductIds($p)
            );
        } elseif ($p->type == 'configurable') {
            $pIds = [
                ...$p->variants->pluck('id')->toArray(),
                ...$pIds,
            ];
        }

        return $pIds;
    }


    public function getParentBundleProductIds($p)
    {
        $bundleOptionProducts = ProductBundleOptionProduct::where([
            'product_id' => $p->id,
        ])->get();

        $pIds = [];

        foreach ($bundleOptionProducts as $bundleOptionProduct) {
            $pIds[] = $bundleOptionProduct->bundle_option->product_id;
        }

        return $pIds;
    }


    public function getParentGroupProductIds($p)
    {
        $groupedOptionProducts = ProductGroupedProduct::where([
            'associated_product_id' => $p->id,
        ])->get();

        return $groupedOptionProducts->pluck('product_id')->toArray();
    }
}
