<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class WishlistResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'product' => new ProductResource($this->product),
            'options' => $this->formatAdditionalAttributes(),
        ];
    }

    
    public function formatAdditionalAttributes(): array
    {
        $additional = $this->resource->additional ?? [];

        if (! empty($additional['attributes'])) {
            $additional['attributes'] = collect($additional['attributes'])
                ->map(function ($attribute) {
                    if (
                        isset($attribute['attribute_type'])
                        && $attribute['attribute_type'] == 'file'
                    ) {
                        $attribute['file_name'] = File::basename($attribute['option_label']);

                        $attribute['file_url'] = Storage::url($attribute['option_label']);
                    }

                    return $attribute;
                })
                ->toArray();
        }

        return $additional;
    }
}
