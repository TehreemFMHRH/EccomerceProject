<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Jobs\UpdateCreateSearchTerm as UpdateCreateSearchTermJob;
use Webkul\Product\Models\Product;
use Webkul\Shop\Http\Resources\ProductResource;
use Illuminate\Support\Facades\DB;
class ProductController extends APIController
{

    public function __construct(
        protected CategoryRepository $categoryRepository,

    ) {}


    public function index(): JsonResource
    {
        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $pModel = new \Webkul\Product\Models\Product();

        $pModel->setSearchEngine($searchEngine ?? 'database');

        $ps = $pModel->getAll(array_merge(request()->query(), [
            'channel_id'           => core()->getCurrentChannel()->id,
            'status'               => 1,
            'visible_individually' => 1,
        ]));

        if (! empty(request()->query('query'))) {

            if (count(request()->except(['mode', 'sort', 'limit'])) == 1) {
                UpdateCreateSearchTermJob::dispatch([
                    'term'       => request()->query('query'),
                    'results'    => $ps->total(),
                    'channel_id' => core()->getCurrentChannel()->id,
                    'locale'     => app()->getLocale(),
                ]);
            }
        }

        return ProductResource::collection($ps);
    }


    public function relatedProducts($i): JsonResource
    {
        $res = DB::select("SELECT * FROM products WHERE id = $i LIMIT 1");
$p = count($res) ? $res[0] : null;

if (! $p) {

    abort(404, 'Product not found');
}

        $relatedProducts = $p->related_products()
            ->take(core()->getConfigData('catalog.products.product_view_page.no_of_related_products'))
            ->get();

        return ProductResource::collection($relatedProducts);
    }


    public function upSellProducts($i): JsonResource
    {
        $res = DB::select("SELECT * FROM products WHERE id = $i LIMIT 1");
$p = count($res) ? $res[0] : null;

if (! $p) {

    abort(404, 'Product not found');
}

        $upSellProducts = $p->up_sells()
            ->take(core()->getConfigData('catalog.products.product_view_page.no_of_up_sells_products'))
            ->get();

        return ProductResource::collection($upSellProducts);
    }
}
