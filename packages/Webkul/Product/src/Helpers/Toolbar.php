<?php

namespace Webkul\Product\Helpers;

use Illuminate\Support\Collection;

class Toolbar
{
    
    public function getAvailableOrders(): Collection
    {
        return collect([
            [
                'title'    => trans('product::app.sort-by.options.from-a-z'),
                'value'    => 'name-asc',
                'sort'     => 'name',
                'order'    => 'asc',
                'position' => 1,
            ],
            [
                'title'    => trans('product::app.sort-by.options.from-z-a'),
                'value'    => 'name-desc',
                'sort'     => 'name',
                'order'    => 'desc',
                'position' => 2,
            ],
            [
                'title'    => trans('product::app.sort-by.options.latest-first'),
                'value'    => 'created_at-desc',
                'sort'     => 'created_at',
                'order'    => 'desc',
                'position' => 3,
            ],
            [
                'title'    => trans('product::app.sort-by.options.oldest-first'),
                'value'    => 'created_at-asc',
                'sort'     => 'created_at',
                'order'    => 'asc',
                'position' => 4,
            ],
            [
                'title'    => trans('product::app.sort-by.options.cheapest-first'),
                'value'    => 'price-asc',
                'sort'     => 'price',
                'order'    => 'asc',
                'position' => 5,
            ],
            [
                'title'    => trans('product::app.sort-by.options.expensive-first'),
                'value'    => 'price-desc',
                'sort'     => 'price',
                'order'    => 'desc',
                'position' => 6,
            ],
        ]);
    }

    
    public function getDefaultOrder(): array
    {
        return $this->getAvailableOrders()
            ->where('value', core()->getConfigData('catalog.products.storefront.sort_by') ?? 'price-desc')
            ->firstOrFail();
    }

    
    public function getOrder(array $params = []): array
    {
        if (! isset($params['sort'])) {
            return $this->getDefaultOrder();
        }

        $o = $this->getAvailableOrders()
            ->where('value', $params['sort'])
            ->first();

        return $o ?: $this->getDefaultOrder();
    }

    
    public function getAvailableLimits(): Collection
    {
        if ($productsPerPage = core()->getConfigData('catalog.products.storefront.products_per_page')) {
            $pages = explode(',', $productsPerPage);

            return collect($pages);
        }

        return collect([12, 24, 36, 48]);
    }

    
    public function getDefaultLimit(): int
    {
        return $this->getAvailableLimits()->first();
    }

    
    public function getLimit(array $params): int
    {
        
        $limit = (int) ($params['limit'] ?? $this->getDefaultLimit());

        
        return in_array($limit, $this->getAvailableLimits()->toArray())
            ? $limit
            : $this->getDefaultLimit();
    }

    
    public function getAvailableModes(): Collection
    {
        return collect(['grid', 'list']);
    }

    
    public function getDefaultMode(): string
    {
        return core()->getConfigData('catalog.products.storefront.mode') ?? 'grid';
    }

    
    public function getMode(array $params): string
    {
        
        $mode = $params['mode'] ?? $this->getDefaultMode();

        
        return in_array($mode, $this->getAvailableModes()->toArray())
            ? $mode
            : $this->getDefaultMode();
    }
}
