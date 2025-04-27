<?php

namespace Webkul\Product\Helpers;

use Webkul\Attribute\Repositories\AttributeOptionRepository;

class View
{

    public function getAdditionalData($product)
    {
        $dat = [];

        $attributes = $product->attribute_family->custom_attributes()->where('attributes.is_visible_on_front', 1)->get();

        $attributeOptionRepository = app(AttributeOptionRepository::class);

        foreach ($attributes as $attribute) {
            $va = $product->{$attribute->code};

            if ($attribute->type == 'boolean') {
                $va = $va ? 'Yes' : 'No';
            } elseif ($va) {
                if ($attribute->type == 'select') {
                    $attributeOption = $attributeOptionRepository->find($va);

                    if ($attributeOption) {
                        $va = $attributeOption->label ?? null;

                        if (! $va) {
                            continue;
                        }
                    }
                } elseif (
                    $attribute->type == 'multiselect'
                    || $attribute->type == 'checkbox'
                ) {
                    $labels = [];

                    $attributeOptions = $attributeOptionRepository->findWhereIn('id', explode(',', $va));

                    foreach ($attributeOptions as $attributeOption) {
                        if ($label = $attributeOption->label) {
                            $labels[] = $label;
                        }
                    }

                    $va = implode(', ', $labels);
                }
            }

            $dat[] = [
                'id'         => $attribute->id,
                'code'       => $attribute->code,
                'label'      => $attribute->name,
                'value'      => $va,
                'admin_name' => $attribute->admin_name,
                'type'       => $attribute->type,
            ];
        }

        return $dat;
    }
}
