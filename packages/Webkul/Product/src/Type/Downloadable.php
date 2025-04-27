<?php

namespace Webkul\Product\Type;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\DataTypes\CartItemValidationResult;
use Webkul\Product\Helpers\Indexers\Price\Downloadable as DownloadableIndexer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductDownloadableLinkRepository;
use Webkul\Product\Repositories\ProductDownloadableSampleRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductVideoRepository;
use Webkul\Tax\Facades\Tax;

class Downloadable extends AbstractType
{

    protected $skipAttributes = [
        'length',
        'width',
        'height',
        'weight',
        'depth',
        'manage_stock',
        'guest_checkout',
    ];


    protected $isStockable = false;


    protected $canBeAddedToCartWithoutOptions = false;


    public function __construct(
        CustomerRepository $customerRepository,
        AttributeRepository $attributeRepository,
        ProductAttributeValueRepository $attributeValueRepository,
        ProductInventoryRepository $productInventoryRepository,
        productImageRepository $productImageRepository,
        ProductVideoRepository $productVideoRepository,
        ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,
        protected ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        protected ProductDownloadableSampleRepository $productDownloadableSampleRepository
    ) {
        parent::__construct(
            $customerRepository,
            $attributeRepository,
            $attributeValueRepository,
            $productInventoryRepository,
            $productImageRepository,
            $productVideoRepository,
            $productCustomerGroupPriceRepository
        );
    }


    public function update(array $dat, $i, $attributes = [])
    {
        $product = parent::update($dat, $i, $attributes);

        if (! empty($attributes)) {
            return $product;
        }

        $this->productDownloadableLinkRepository->saveLinks($dat, $product);

        $this->productDownloadableSampleRepository->saveSamples($dat, $product);

        return $product;
    }


    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        if ($this->product->downloadable_links()->count()) {
            return true;
        }

        return false;
    }


    public function getTypeValidationRules()
    {
        return [
            'downloadable_links.*.type'       => 'required',
            'downloadable_links.*.file'       => 'required_if:type,==,file',
            'downloadable_links.*.file_name'  => 'required_if:type,==,file',
            'downloadable_links.*.url'        => 'required_if:type,==,url',
            'downloadable_links.*.downloads'  => 'required',
            'downloadable_links.*.sort_order' => 'required',
        ];
    }


    public function prepareForCart($dat)
    {
        if (empty($dat['links'])) {
            return trans('product::app.checkout.cart.missing-links');
        }

        $products = parent::prepareForCart($dat);

        foreach ($this->product->downloadable_links as $link) {
            if (! in_array($link->id, $dat['links'])) {
                continue;
            }

            $products[0]['price'] += ($r = core()->convertPrice($link->price));
            $products[0]['price_incl_tax'] += $r;
            $products[0]['base_price'] += $link->price;
            $products[0]['base_price_incl_tax'] += $link->price;
            $products[0]['total'] += ($t = core()->convertPrice($link->price) * $products[0]['quantity']);
            $products[0]['total_incl_tax'] += $t;
            $products[0]['base_total'] += ($link->price * $products[0]['quantity']);
        }

        return $products;
    }


    public function compareOptions($options1, $options2)
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        }

        if (
            isset($options1['links'])
            && isset($options2['links'])
        ) {
            return $options1['links'] === $options2['links'];
        }

        if (! isset($options1['links'])) {
            return false;
        }

        if (! isset($options2['links'])) {
            return false;
        }
    }


    public function getAdditionalOptions($dat)
    {
        $labels = [];

        foreach ($this->product->downloadable_links as $link) {
            if (in_array($link->id, $dat['links'])) {
                $labels[] = $link->title;
            }
        }

        $dat['attributes'][0] = [
            'attribute_name' => 'Downloads',
            'option_id'      => 0,
            'option_label'   => implode(', ', $labels),
        ];

        return $dat;
    }


    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $validation = new CartItemValidationResult;

        if (parent::isCartItemInactive($item)) {
            $validation->itemIsInactive();

            return $validation;
        }

        $basePrice = $this->getFinalPrice($item->quantity);

        foreach ($item->product->downloadable_links as $link) {
            if (! in_array($link->id, $item->additional['links'])) {
                continue;
            }

            $basePrice += $link->price;
        }

        $basePrice = round($basePrice, 2);

        if (Tax::isInclusiveTaxProductPrices()) {
            $itemBasePrice = $item->base_price_incl_tax;
        } else {
            $itemBasePrice = $item->base_price;
        }

        if ($basePrice == $itemBasePrice) {
            return $validation;
        }

        $item->base_price = $basePrice;
        $item->base_price_incl_tax = $basePrice;

        $item->price = ($r = core()->convertPrice($basePrice));
        $item->price_incl_tax = $r;

        $item->base_total = $basePrice * $item->quantity;
        $item->base_total_incl_tax = $basePrice * $item->quantity;

        $item->total = ($t = core()->convertPrice($basePrice * $item->quantity));
        $item->total_incl_tax = $t;

        $item->save();

        return $validation;
    }


    public function getMaximumPrice()
    {
        return $this->product->price;
    }


    public function getPriceIndexer()
    {
        return app(DownloadableIndexer::class);
    }
}
