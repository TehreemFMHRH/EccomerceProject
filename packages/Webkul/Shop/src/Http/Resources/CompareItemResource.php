<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;

class CompareItemResource extends JsonResource
{
    
    protected static $comparableAttributes = [];

    
    public static function collection($resource)
    {
        self::$comparableAttributes = app(AttributeFamilyRepository::class)->getComparableAttributesBelongsToFamily();

        return parent::collection($resource);
    }

    
    public function toArray($request)
    {
        $dat = (new ProductResource($this->resource))
            ->toArray($this->resource);

        foreach (self::$comparableAttributes as $attribute) {
            if (in_array($attribute->code, ['name', 'price'])) {
                continue;
            }

            if (in_array($attribute->type, ['select', 'multiselect', 'checkbox'])) {
                $labels = [];

                $attributeOptions = $attribute->options->whereIn('id', explode(',', $this->{$attribute->code}));

                foreach ($attributeOptions as $attributeOption) {
                    if ($label = $attributeOption->label) {
                        $labels[] = strip_tags($label);
                    }
                }

                $dat[$attribute->code] = implode(', ', $labels);
            } else {
                if ($attribute->enable_wysiwyg) {
                    $dat[$attribute->code] = $this->{$attribute->code};
                } else {
                    $dat[$attribute->code] = strip_tags($this->{$attribute->code});
                }
            }
        }

        return $dat;
    }
}
