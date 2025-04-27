<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;
class DownloadableController extends Controller
{
    
    public function __construct( ) {}

    
    public function options(int $id): JsonResponse
    {
        $result = DB::select("SELECT * FROM products WHERE id = $id LIMIT 1");
$product = count($result) ? $result[0] : null;

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
