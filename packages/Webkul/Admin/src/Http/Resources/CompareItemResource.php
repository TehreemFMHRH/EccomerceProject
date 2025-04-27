<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompareItemResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'product' => new ProductResource($this->product),
        ];
    }
}
