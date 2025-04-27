<?php

namespace Webkul\Sales\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaymentResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'method'       => $this->method,
            'method_title' => $this->method_title,
            'additional'   => $request->input('orderData'),
        ];
    }
}
