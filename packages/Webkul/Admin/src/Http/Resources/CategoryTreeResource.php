<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'parent_id' => $this->parent_id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'url'       => $this->url,
            'status'    => $this->status,
            'children'  => self::collection($this->children),
        ];
    }
}
