<?php

namespace Webkul\Product\Type;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Helpers\Indexers\Price\Grouped as GroupedIndexer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Models\ProductGroupedProduct;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductVideoRepository;

class Grouped extends AbstractType
{

    protected $skipAttributes = [
        'price',
        'cost',
        'special_price',
        'special_price_from',
        'special_price_to',
        'length',
        'width',
        'height',
        'weight',
        'depth',
        'manage_stock',
    ];


    protected $isComposite = true;


    protected $canBeAddedToCartWithoutOptions = false;


    // public function __construct(
    //     CustomerRepository $customerRepository,
    //     AttributeRepository $attributeRepository,
    //     ProductAttributeValueRepository $attributeValueRepository,
    //     ProductInventoryRepository $productInventoryRepository,
    //     ProductImageRepository $productImageRepository,
    //     ProductVideoRepository $productVideoRepository,
    //     ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,
    // ) {
    //     parent::__construct(
    //         $customerRepository,
    //         $attributeRepository,
    //         $attributeValueRepository,
    //         $productInventoryRepository,
    //         $productImageRepository,
    //         $productVideoRepository,
    //         $productCustomerGroupPriceRepository
    //     );
    // }


    public function update(array $dat, $i, $attributes = [])
    {
        $product = parent::update($dat, $i);

        if (! empty($attributes)) {
            return $product;
        }

        ProductGroupedProduct::saveGroupedProducts($dat, $product);

        return $product;
    }


    protected function copyRelationships($product)
    {
        parent::copyRelationships($product);

        $attributesToSkip = config('products.skipAttributesOnCopy') ?? [];

        if (in_array('grouped_products', $attributesToSkip)) {
            return;
        }

        foreach ($this->product->grouped_products as $groupedProduct) {
            $product->grouped_products()->save($groupedProduct->replicate());
        }
    }


    public function getChildrenIds()
    {
        return array_unique($this->product->grouped_products()->pluck('associated_product_id')->toArray());
    }


    public function priceRuleCanBeApplied()
    {
        return false;
    }


    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        foreach ($this->product->grouped_products as $groupedProduct) {
            if ($groupedProduct->associated_product->isSaleable()) {
                return true;
            }
        }

        return false;
    }


    public function haveSufficientQuantity(int $qty): bool
    {
        foreach ($this->product->grouped_products as $groupedProduct) {
            if ($groupedProduct->associated_product->haveSufficientQuantity($qty)) {
                return true;
            }
        }

        return false;
    }


    public function getPriceHtml()
    {
        return view('shop::products.prices.grouped', [
            'product' => $this->product,
            'prices'  => $this->getProductPrices(),
        ])->render();
    }


    public function prepareForCart($dat)
    {
        if (
            ! isset($dat['qty'])
            || ! is_array($dat['qty'])
        ) {
            return trans('product::app.checkout.cart.missing-options');
        }

        $cartProductsList = [];

        foreach ($dat['qty'] as $productId => $qty) {
            if (! $qty) {
                continue;
            }

            $product = Product::find($productId);

            if ($product->type !== 'simple') {
                return trans('product::app.checkout.cart.selected-products-simple');
            }

            $cartProducts = $product->getTypeInstance()->prepareForCart([
                'product_id' => $productId,
                'quantity'   => $qty,
            ]);

            if (is_string($cartProducts)) {
                return $cartProducts;
            }

            $cartProductsList[] = $cartProducts;
        }

        $products = array_merge(...$cartProductsList);

        if (! count($products)) {
            return trans('product::app.checkout.cart.integrity.qty-missing');
        }

        return $products;
    }


    public function getPriceIndexer()
    {
        return app(GroupedIndexer::class);
    }


    public function getTypeValidationRules()
    {
        return [
            'links' => 'array',
            'links' => function ($attribute, $va, $fail) {
                $associatedProductIds = collect($va)->pluck('associated_product_id')->toArray();

                $products = Product::whereIn('id', $associatedProductIds)
                    ->pluck('type')
                    ->filter(fn ($type) => $type !== 'simple')
                    ->count();

                if ($products) {
                    $fail(trans('product::app.checkout.cart.selected-products-simple'));
                }
            },
        ];
    }
}
