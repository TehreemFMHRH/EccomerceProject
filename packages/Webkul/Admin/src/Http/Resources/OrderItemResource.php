<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'order_id'   => $this->order_id,
            'additional' => (object) $this->resource->additional ?? [],
            'product'    => new ProductResource($this->product),
        ];
    }
}
