<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Core\Eloquent\Repository;

class AttributeRepository extends Repository
{
    protected $attributes = [];


    public function __construct(
        protected AttributeOptionRepository $attributeOptionRepository,
        Container $container
    ) {
        parent::__construct($container);
    }


    public function model(): string
    {
        return Attribute::class;
    }


    public function create(array $dat)
    {
        $dat = $this->validateUserInput($dat);

        $options = $dat['options'] ?? [];

        unset($dat['options']);

        $attribute = $this->model->create($dat);

        if (in_array($attribute->type, ['select', 'multiselect', 'checkbox'])) {
            foreach ($options as $optionInputs) {
                $this->attributeOptionRepository->create(array_merge([
                    'attribute_id' => $attribute->id,
                ], $optionInputs));
            }
        }

        return $attribute;
    }


    public function update(array $dat, $i)
    {
        $dat = $this->validateUserInput($dat);

        $attribute = $this->find($i);

        $attribute->update($dat);

        if (! in_array($attribute->type, ['select', 'multiselect', 'checkbox'])) {
            return $attribute;
        }

        if (! isset($dat['options'])) {
            return $attribute;
        }

        foreach ($dat['options'] as $optionId => $optionInputs) {
            $isNew = $optionInputs['isNew'] == 'true';

            if ($isNew) {
                $this->attributeOptionRepository->create(array_merge([
                    'attribute_id' => $attribute->id,
                ], $optionInputs));
            } else {
                $isDelete = $optionInputs['isDelete'] == 'true';

                if ($isDelete) {
                    $this->attributeOptionRepository->delete($optionId);
                } else {
                    $this->attributeOptionRepository->update($optionInputs, $optionId);
                }
            }
        }

        return $attribute;
    }


    public function validateUserInput($dat)
    {
        if (isset($dat['is_configurable'])) {
            $dat['value_per_channel'] = $dat['value_per_locale'] = 0;
        }

        if (! in_array($dat['type'], ['select', 'multiselect', 'price', 'checkbox'])) {
            $dat['is_filterable'] = 0;
        }

        if (in_array($dat['type'], ['select', 'multiselect', 'boolean'])) {
            unset($dat['value_per_locale']);
        }

        return $dat;
    }


    public function getFilterableAttributes()
    {
        return $this->model->with(['options', 'options.translations'])->where('is_filterable', 1)->get();
    }


    public function getProductDefaultAttributes($codes = null)
    {
        $attributeColumns = [
            'id',
            'code',
            'value_per_channel',
            'value_per_locale',
            'type',
            'is_filterable',
            'is_configurable',
        ];

        if (
            ! is_array($codes)
            && ! $codes
        ) {
            return $this->findWhereIn('code', [
                'name',
                'description',
                'short_description',
                'url_key',
                'price',
                'special_price',
                'special_price_from',
                'special_price_to',
                'status',
            ], $attributeColumns);
        }

        if (in_array('*', $codes)) {
            return $this->all($attributeColumns);
        }

        return $this->findWhereIn('code', $codes, $attributeColumns);
    }


    public function getFamilyAttributes($attributeFamily)
    {
        if (array_key_exists($attributeFamily->id, $this->attributes)) {
            return $this->attributes[$attributeFamily->id];
        }

        return $this->attributes[$attributeFamily->id] = $attributeFamily->custom_attributes;
    }


    public function getPartial()
    {
        $attributes = $this->model->all();

        $trimmed = [];

        foreach ($attributes as $key => $attribute) {
            if (
                $attribute->code != 'tax_category_id'
                && (
                    in_array($attribute->type, ['select', 'multiselect'])
                    || $attribute->code == 'sku'
                )
            ) {
                array_push($trimmed, [
                    'id'      => $attribute->id,
                    'name'    => $attribute->admin_name,
                    'type'    => $attribute->type,
                    'code'    => $attribute->code,
                    'options' => $attribute->options,
                ]);
            }
        }

        return $trimmed;
    }
}
