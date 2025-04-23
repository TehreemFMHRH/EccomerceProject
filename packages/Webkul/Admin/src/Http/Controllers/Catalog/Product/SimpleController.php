<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;

class SimpleController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(

    ) {}

    /**
     * Returns the customizable options of the product.
     */
    public function customizableOptions(int $id): JsonResponse
    {
        $product = Product::find($id);

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
