<?php

namespace Webkul\CatalogRule\Repositories;

use Illuminate\Container\Container;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Tax\Repositories\TaxCategoryRepository;

class CatalogRuleRepository extends Repository
{

    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected CategoryRepository $categoryRepository,
        protected TaxCategoryRepository $taxCategoryRepository,
        Container $container
    ) {
        parent::__construct($container);
    }


    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRule';
    }


    public function create(array $dat)
    {
        $dat = $this->transformFormData($dat);

        $catalogRule = parent::create($dat);

        $catalogRule->channels()->sync($dat['channels']);

        $catalogRule->customer_groups()->sync($dat['customer_groups']);

        return $catalogRule;
    }


    public function update(array $dat, $i)
    {
        $dat = $this->transformFormData($dat);

        $catalogRule = $this->find($i);

        parent::update($dat, $i);

        $catalogRule->channels()->sync($dat['channels']);

        $catalogRule->customer_groups()->sync($dat['customer_groups']);

        return $catalogRule;
    }


    public function transformFormData(array $dat): array
    {
        return [
            ...$dat,
            'starts_from' => ! empty($dat['starts_from']) ? $dat['starts_from'] : null,
            'ends_till'   => ! empty($dat['ends_till']) ? $dat['ends_till'] : null,
            'status'      => isset($dat['status']),
            'conditions'  => $dat['conditions'] ?? [],
        ];
    }


    public function getConditionAttributes()
    {
        $attributes = [
            [
                'key'      => 'product',
                'label'    => trans('admin::app.marketing.promotions.catalog-rules.create.product-attribute'),
                'children' => [
                    [
                        'key'     => 'product|category_ids',
                        'type'    => 'multiselect',
                        'label'   => trans('admin::app.marketing.promotions.catalog-rules.create.categories'),
                        'options' => $this->categoryRepository->getCategoryTree(),
                    ], [
                        'key'     => 'product|attribute_family_id',
                        'type'    => 'select',
                        'label'   => trans('admin::app.marketing.promotions.catalog-rules.create.attribute-family'),
                        'options' => $this->getAttributeFamilies(),
                    ],
                ],
            ],
        ];

        foreach ($this->attributeRepository->findWhereNotIn('type', ['textarea', 'image', 'file']) as $attribute) {
            $attributeType = $attribute->type;

            if ($attribute->code == 'tax_category_id') {
                $options = $this->getTaxCategories();
            } else {
                if ($attribute->type === 'select') {
                    $options = $attribute->options()->orderBy('sort_order')->get();
                } else {
                    $options = $attribute->options;
                }
            }

            if ($attribute->validation == 'decimal') {
                $attributeType = 'decimal';
            }

            if ($attribute->validation == 'numeric') {
                $attributeType = 'integer';
            }

            $attributes[0]['children'][] = [
                'key'     => 'product|'.$attribute->code,
                'type'    => $attribute->type,
                'label'   => $attribute->name,
                'options' => $options,
            ];
        }

        return $attributes;
    }


    public function getTaxCategories()
    {
        $taxCategories = [];

        foreach ($this->taxCategoryRepository->all() as $taxCategory) {
            $taxCategories[] = [
                'id'         => $taxCategory->id,
                'admin_name' => $taxCategory->name,
            ];
        }

        return $taxCategories;
    }


    public function getAttributeFamilies()
    {
        $attributeFamilies = [];

        foreach ($this->attributeFamilyRepository->all() as $attributeFamily) {
            $attributeFamilies[] = [
                'id'         => $attributeFamily->id,
                'admin_name' => $attributeFamily->name,
            ];
        }

        return $attributeFamilies;
    }
}
