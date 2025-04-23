<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Webkul\Product\Contracts\ProductGroupedProduct as ProductGroupedProductContract;
use Webkul\Product\Database\Factories\ProductGroupedProductFactory;

class ProductGroupedProduct extends Model implements ProductGroupedProductContract
{
    use HasFactory;

    /**
     * Set timestamp false.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Add fillable property to the model.
     *
     * @var array
     */
    protected $fillable = [
        'qty',
        'sort_order',
        'product_id',
        'associated_product_id',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    /**
     * Get the product that owns the image.
     */
    public function associated_product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductGroupedProductFactory::new();
    }

    private function saveGroupedProducts($data, $product)
    {
        $previousGroupedProductIds = $product->grouped_products()->pluck('id');

        if (isset($data['links'])) {
            foreach ($data['links'] as $linkId => $linkInputs) {
                if (Str::contains($linkId, 'link_')) {
                    $groupedProduct = $this->where([
                        'product_id'            => $product->id,
                        'associated_product_id' => $linkInputs['associated_product_id'],
                    ])->first();

                    if ($groupedProduct) {
                        $groupedProduct->update(array_merge([
                            'product_id' => $product->id,
                        ], $linkInputs));

                        if (is_numeric($index = $previousGroupedProductIds->search($groupedProduct->id))) {
                            $previousGroupedProductIds->forget($index);
                        }
                    } else {
                        $this->create(array_merge([
                            'product_id' => $product->id,
                        ], $linkInputs));
                    }
                } else {
                    if (is_numeric($index = $previousGroupedProductIds->search($linkId))) {
                        $previousGroupedProductIds->forget($index);
                    }

                    $this->find($linkId)?->update($linkInputs);
                }
            }
        }

        // Delete removed items
        $this->destroy($previousGroupedProductIds);
    }
}
