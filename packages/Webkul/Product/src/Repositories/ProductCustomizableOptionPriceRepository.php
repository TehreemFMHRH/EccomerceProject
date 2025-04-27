<?php

namespace Webkul\Product\Repositories;

use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\ProductCustomizableOptionPrice;

class ProductCustomizableOptionPriceRepository extends Repository
{
    
    public function model(): string
    {
        return ProductCustomizableOptionPrice::class;
    }

    
    public function saveCustomizableOptionPrices($dat, $productCustomizableOption)
    {
        $previousCustomizableOptionPriceIds = $productCustomizableOption->customizable_option_prices()->pluck('id');

        if (isset($dat['prices'])) {
            foreach ($dat['prices'] as $customizableOptionPriceId => $customizableOptionPriceInputs) {
                if (Str::contains($customizableOptionPriceId, 'price_')) {
                    $this->create(array_merge([
                        'product_customizable_option_id' => $productCustomizableOption->id,
                    ], $customizableOptionPriceInputs));
                } else {
                    if (is_numeric($index = $previousCustomizableOptionPriceIds->search($customizableOptionPriceId))) {
                        $previousCustomizableOptionPriceIds->forget($index);
                    }

                    $this->update($customizableOptionPriceInputs, $customizableOptionPriceId);
                }
            }
        }

        foreach ($previousCustomizableOptionPriceIds as $previouscustomizableOptionPriceId) {
            $this->delete($previouscustomizableOptionPriceId);
        }
    }
}
