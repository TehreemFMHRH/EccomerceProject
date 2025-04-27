<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Helpers\BundleOption;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class BundleController extends Controller
{
    
    public function __construct(
        protected BundleOption $bundleOptionHelper
    ) {}

    
    public function options(int $id): JsonResponse
    {
        $result = DB::select("SELECT * FROM products WHERE id = $id LIMIT 1");
$product = count($result) ? $result[0] : null;

if (! $product) {
    // Custom logic
    abort(404, 'Product not found');
}

        return new JsonResponse([
            'data' => $this->bundleOptionHelper->getBundleConfig($product),
        ]);
    }
}
