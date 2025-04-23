<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;

class GroupedController extends Controller
{
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

        $options = $product->grouped_products()->orderBy('sort_order')->get();

        $products = [];

        foreach ($options as $option) {
            if (! $option->associated_product->getTypeInstance()->isSaleable()) {
                continue;
            }

            $products[] = [
                'id'              => $option->associated_product->id,
                'name'            => $option->associated_product->name,
                'qty'             => $option->qty,
                'price'           => $price = $option->associated_product->getTypeInstance()->getFinalPrice(),
                'formatted_price' => core()->formatPrice($price),
            ];
        }

        return new JsonResponse([
            'data' => $products,
        ]);
    }
}
