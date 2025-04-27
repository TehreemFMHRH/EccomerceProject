<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;
class SimpleController extends Controller
{
    
    public function __construct(

    ) {}

    
    public function customizableOptions(int $i): JsonResponse
    {
        $result = DB::select("SELECT * FROM products WHERE id = $i LIMIT 1");
$product = count($result) ? $result[0] : null;

if (! $product) {
    // Custom logic
    abort(404, 'Product not found');
}

        return new JsonResponse([
            'data' => $product->customizable_options()->with([
                'product',
                'customizable_option_prices',
            ])->get(),

            'meta' => [
                'initial_price' => $product->getTypeInstance()->getMinimalPrice(),
            ],
        ]);
    }
}
