<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;

class DownloadableController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct( ) {}

    /**
     * Returns the compare items of the customer.
     */
    public function options(int $id): JsonResponse
    {
        $product = Product::find($id);

if (! $product) {
    // Custom logic
    abort(404, 'Product not found');
}

        $links = [];

        foreach ($product->downloadable_links as $link) {
            $links[] = [
                'id'              => $link->id,
                'title'           => $link->title,
                'price'           => $link->price,
                'formatted_price' => core()->formatPrice($link->price),
            ];
        }

        return new JsonResponse([
            'data' => $links,
        ]);
    }
}
