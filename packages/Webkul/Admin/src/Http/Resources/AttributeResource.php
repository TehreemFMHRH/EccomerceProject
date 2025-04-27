<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'code'    => $this->code,
            'type'    => $this->type,
            'name'    => $this->admin_name,
            'options' => AttributeOptionResource::collection($this->options),
        ];
    }
}
