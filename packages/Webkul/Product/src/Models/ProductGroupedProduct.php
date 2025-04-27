<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\ProductGroupedProduct as ProductGroupedProductContract;
use Webkul\Product\Database\Factories\ProductGroupedProductFactory;
use Illuminate\Support\Str;
class ProductGroupedProduct extends Model implements ProductGroupedProductContract
{
    use HasFactory;

    
    public $timestamps = false;

    
    protected $fillable = [
        'qty',
        'sort_order',
        'product_id',
        'associated_product_id',
    ];

    
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    public function associated_product()
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return ProductGroupedProductFactory::new();
    }

    private function saveGroupedProducts($dat, $product)
    {
        $previousGroupedProductIds = $product->grouped_products()->pluck('id');

        if (isset($dat['links'])) {
            foreach ($dat['links'] as $linkId => $linkInputs) {
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
