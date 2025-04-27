<?php

namespace Webkul\Product\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;

class ProductAttributeValueRepository extends Repository
{

    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductAttributeValue';
    }


    public function saveValues($dat, $product, $attributes)
    {
        $attributeValuesToInsert = [];

        foreach ($attributes as $attribute) {
            if ($attribute->type === 'boolean') {
                $dat[$attribute->code] = ! empty($dat[$attribute->code]);
            }

            if (in_array($attribute->type, ['multiselect', 'checkbox'])) {
                $dat[$attribute->code] = implode(',', $dat[$attribute->code] ?? []);
            }

            if (! isset($dat[$attribute->code])) {
                continue;
            }

            if (
                $attribute->type === 'price'
                && empty($dat[$attribute->code])
            ) {
                $dat[$attribute->code] = null;
            }

            if (
                $attribute->type === 'date'
                && empty($dat[$attribute->code])
            ) {
                $dat[$attribute->code] = null;
            }

            if (in_array($attribute->type, ['image', 'file'])) {
                $dat[$attribute->code] = gettype($dat[$attribute->code]) === 'object'
                    ? request()->file($attribute->code)->store('product/'.$product->id)
                    : $dat[$attribute->code];
            }

            $attributeValues = $product->attribute_values
                ->where('attribute_id', $attribute->id);

            $channel = $attribute->value_per_channel ? ($dat['channel'] ?? core()->getDefaultChannelCode()) : null;

            $locale = $attribute->value_per_locale ? ($dat['locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel()) : null;

            if ($attribute->value_per_channel) {
                if ($attribute->value_per_locale) {
                    $filteredAttributeValues = $attributeValues
                        ->where('channel', $channel)
                        ->where('locale', $locale);
                } else {
                    $filteredAttributeValues = $attributeValues
                        ->where('channel', $channel);
                }
            } else {
                if ($attribute->value_per_locale) {
                    $filteredAttributeValues = $attributeValues
                        ->where('locale', $locale);
                } else {
                    $filteredAttributeValues = $attributeValues;
                }
            }

            $attributeValue = $filteredAttributeValues->first();

            $uniqueId = implode('|', array_filter([
                $channel,
                $locale,
                $product->id,
                $attribute->id,
            ]));

            if (! $attributeValue) {
                $attributeValuesToInsert[] = array_merge($this->getAttributeTypeColumnValues($attribute, $dat[$attribute->code]), [
                    'product_id'   => $product->id,
                    'attribute_id' => $attribute->id,
                    'channel'      => $channel,
                    'locale'       => $locale,
                    'unique_id'    => $uniqueId,
                ]);
            } else {
                $previousTextValue = $attributeValue->text_value;

                if (in_array($attribute->type, ['image', 'file'])) {

                    if (! empty($dat[$attribute->code]['delete'])) {
                        Storage::delete($previousTextValue);

                        $dat[$attribute->code] = null;
                    }

                    elseif (
                        ! empty($previousTextValue)
                        && $dat[$attribute->code] != $previousTextValue
                    ) {
                        Storage::delete($previousTextValue);
                    }
                }

                $attributeValue = $this->update([
                    $attribute->column_name => $dat[$attribute->code],
                    'unique_id'             => $uniqueId,
                ], $attributeValue->id);
            }
        }

        if (! empty($attributeValuesToInsert)) {
            $this->insert($attributeValuesToInsert);
        }
    }


    public function getAttributeTypeColumnValues($attribute, $va)
    {
        $attributeTypeFields = array_fill_keys(array_values($attribute->attributeTypeFields), null);

        $attributeTypeFields[$attribute->column_name] = $va;

        return $attributeTypeFields;
    }


    public function isValueUnique($productId, $attributeId, $column, $va)
    {
        $count = $this->resetScope()
            ->model
            ->where($column, $va)
            ->where('attribute_id', '=', $attributeId)
            ->where('product_id', '!=', $productId)
            ->count('id');

        return ! $count;
    }
}
