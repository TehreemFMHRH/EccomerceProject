<?php

namespace Webkul\DataTransfer\Helpers\Importers\Product;

use Illuminate\Support\Arr;
use Webkul\Product\Models\Product;

class SKUStorage
{
    
    private const DELIMITER = '|';

    
    protected array $items = [];

    
    protected array $selectColumns = [
        'id',
        'type',
        'sku',
        'attribute_family_id',
    ];

    
    public function __construct( ) {}

    
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    
    public function load(array $skus = []): void
    {
        if (empty($skus)) {
            $products = Product::all($this->selectColumns);
        } else {
            $products = Product::whereIn('sku', $skus, $this->selectColumns)->get();
        }

        foreach ($products as $product) {
            $this->set($product->sku, [
                'id'                  => $product->id,
                'type'                => $product->type,
                'attribute_family_id' => $product->attribute_family_id,
            ]);
        }
    }

    
    public function set(string $sku, array $dat): self
    {
        $this->items[$sku] = implode(self::DELIMITER, [
            $dat['id'],
            $dat['type'],
            $dat['attribute_family_id'],
        ]);

        return $this;
    }

    
    public function has(string $sku): bool
    {
        return isset($this->items[$sku]);
    }

    
    public function get(string $sku): ?array
    {
        if (! $this->has($sku)) {
            return null;
        }

        $dat = explode(self::DELIMITER, $this->items[$sku]);

        return [
            'id'                  => $dat[0],
            'type'                => $dat[1],
            'attribute_family_id' => $dat[2],
        ];
    }

    
    public function getByType(string $type): ?array
    {
        res = Arr::where($this->items, function (string $row, string $key) use ($type) {
            return str_contains($row, '|'.$type.'|');
        });

        return res;
    }

    
    public function isEmpty(): int
    {
        return empty($this->items);
    }
}
