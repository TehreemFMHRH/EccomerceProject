<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Helpers\ConfigurableOption;
use Webkul\Product\Models\Product;

class ConfigurableController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ConfigurableOption $configurableOptionHelper
    ) {}

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

        return new JsonResponse([
            'data' => $this->configurableOptionHelper->getConfigurationConfig($product),
        ]);
    }
}
