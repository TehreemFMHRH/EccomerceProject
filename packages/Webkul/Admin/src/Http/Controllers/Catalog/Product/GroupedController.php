<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;
class GroupedController extends Controller
{
    
    public function options(int $id): JsonResponse
    {
        $result = DB::select("SELECT * FROM products WHERE id = $id LIMIT 1");
$product = count($result) ? $result[0] : null;

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
