<?php

namespace Webkul\Product\Type;

use Illuminate\Support\Str;
use Webkul\Admin\Validations\ConfigurableUniqueSku;
use Webkul\Checkout\Models\CartItem as CartItemModel;
use Webkul\Product\DataTypes\CartItemValidationResult;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Helpers\Indexers\Price\Configurable as ConfigurableIndexer;
use Webkul\Tax\Facades\Tax;
use Webkul\Product\Models\Product;

class Configurable extends AbstractType
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
        'manage_stock',
    ];


    protected $fillableVariantAttributeCodes = [
        'sku',
        'name',
        'url_key',
        'short_description',
        'description',
        'price',
        'weight',
        'status',
        'tax_category_id',
    ];


    protected $fillableVariantAttributes;


    protected $isComposite = true;


    protected $showQuantityBox = true;


    protected $canBeAddedToCartWithoutOptions = false;


    protected $hasVariants = true;


    public function create(array $dat)
    {
        $product = parent::create($dat);

        if (! isset($dat['super_attributes'])) {
            return $product;
        }


        $this->fillableVariantAttributes = $this->attributeRepository->findWhereIn('code', $this->fillableVariantAttributeCodes);

        $superAttributes = [];

        foreach ($dat['super_attributes'] as $attributeCode => $attributeOptions) {
            $attribute = $this->getAttributeByCode($attributeCode);

            $this->fillableVariantAttributes->push($attribute);

            $superAttributes[$attribute->code] = $attributeOptions;

            $product->super_attributes()->attach($attribute->id);
        }

        foreach (array_permutation($superAttributes) as $permutation) {
            $this->createVariant($product, $permutation, [
                'channel' => $dat['channel'] ?? core()->getDefaultChannelCode(),
                'locale'  => $dat['locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel(),
            ]);
        }

        return $product;
    }


    public function update(array $dat, $i, $attributes = [])
    {
        $product = parent::update($dat, $i, $attributes);

        if (! empty($attributes)) {
            return $product;
        }


        $this->fillableVariantAttributes = $this->attributeRepository->findWhereIn('code', $this->fillableVariantAttributeCodes);

        $previousVariantIds = $product->variants->pluck('id');

        foreach ($dat['variants'] ?? [] as $variantId => $variantData) {
            if (Str::contains($variantId, 'variant_')) {
                $superAttributes = [];

                foreach ($product->super_attributes as $superAttribute) {
                    $superAttributes[$superAttribute->id] = $variantData[$superAttribute->code];

                    $this->fillableVariantAttributes->push($superAttribute);
                }

                $this->createVariant($product, $superAttributes, array_merge($variantData, [
                    'channel' => $dat['channel'] ?? core()->getDefaultChannelCode(),
                    'locale'  => $dat['locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel(),
                ]));
            } else {
                if (is_numeric($index = $previousVariantIds->search($variantId))) {
                    $previousVariantIds->forget($index);
                }

                $this->updateVariant(array_merge($variantData, [
                    'channel'         => $dat['channel'] ?? core()->getDefaultChannelCode(),
                    'locale'          => $dat['locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel(),
                    'tax_category_id' => $dat['tax_category_id'] ?? null,
                ]), $variantId);
            }
        }

        foreach ($previousVariantIds as $variantId) {
            Product::delete($variantId);
        }

        return $product;
    }


    public function createVariant($product, $superAttributes, $dat = [])
    {
        $sku = $product->sku.'-variant-'.implode('-', $superAttributes);

        $dat = array_merge([
            'sku'               => $sku,
            'name'              => 'Variant '.implode(' ', $superAttributes),
            'price'             => 0,
            'weight'            => 0,
            'status'            => 1,
            'tax_category_id'   => '',
            'url_key'           => $sku,
            'short_description' => $sku,
            'description'       => $sku,
            'inventories'       => [],
        ], $dat);

        $variant = parent::create([
            'type'                => 'simple',
            'sku'                 => $sku,
            'attribute_family_id' => $product->attribute_family_id,
            'parent_id'           => $product->id,
        ]);

        foreach ($superAttributes as $attributeCode => $optionId) {
            $dat[$attributeCode] = $optionId;
        }

        $this->attributeValueRepository->saveValues($dat, $variant, $this->fillableVariantAttributes);

        $this->productInventoryRepository->saveInventories($dat, $variant);

        $this->productImageRepository->upload($dat, $variant, 'images');

        return $variant;
    }


    public function updateVariant(array $dat, $i)
    {
        $variant = Product::find($i);

        $variant->update(['sku' => $dat['sku']]);

        $this->attributeValueRepository->saveValues($dat, $variant, $this->fillableVariantAttributes);

        $this->productInventoryRepository->saveInventories($dat, $variant);

        $this->productImageRepository->upload($dat, $variant, 'images');

        $variant->channels()->sync($variant->parent->channels->pluck('id')->toArray());

        return $variant;
    }


    protected function copyRelationships($product)
    {
        parent::copyRelationships($product);

        $attributesToSkip = config('products.skipAttributesOnCopy') ?? [];

        if (
            in_array('super_attributes', $attributesToSkip)
            || in_array('variants', $attributesToSkip)
        ) {
            return;
        }

        foreach ($this->product->super_attributes as $superAttribute) {
            $product->super_attributes()->save($superAttribute);
        }

        foreach ($this->product->variants as $variant) {
            $newVariant = $variant->getTypeInstance()->copy();

            $newVariant->parent_id = $product->id;

            $newVariant->save();
        }
    }


    public function getChildrenIds()
    {
        return $this->product->variants()->pluck('id')->toArray();
    }


    public function isItemHaveQuantity($cartItem)
    {
        return $cartItem->child->getTypeInstance()->haveSufficientQuantity($cartItem->quantity);
    }


    public function getTypeValidationRules()
    {
        return [
            'variants.*.name'   => 'required',
            'variants.*.sku'    => [
                'required',
                new ConfigurableUniqueSku($this->getChildrenIds()),
            ],
            'variants.*.price'  => 'required',
            'variants.*.weight' => 'required',
        ];
    }


    public function canBeMovedFromWishlistToCart($item)
    {
        return isset($item->additional['selected_configurable_option']);
    }


    public function getProductPrices()
    {
        $minPrice = $this->getMinimalPrice();

        return [
            'regular' => [
                'price'           => $minPrice,
                'formatted_price' => core()->currency($minPrice),
            ],
        ];
    }


    public function getPriceHtml()
    {
        return view('shop::products.prices.configurable', [
            'product' => $this->product,
            'prices'  => $this->getProductPrices(),
        ])->render();
    }


    public function prepareForCart($dat)
    {
        $dat['quantity'] = parent::handleQuantity((int) $dat['quantity']);

        if (empty($dat['selected_configurable_option'])) {
            return trans('product::app.checkout.cart.missing-options');
        }

        $dat = $this->getQtyRequest($dat);

        $childProduct = Product::find($dat['selected_configurable_option']);

        if (! $childProduct->haveSufficientQuantity($dat['quantity'])) {
            return trans('product::app.checkout.cart.inventory-warning');
        }

        $r = $childProduct->getTypeInstance()->getFinalPrice();

        return [
            [
                'product_id'          => $this->product->id,
                'sku'                 => $this->product->sku,
                'name'                => $this->product->name,
                'type'                => $this->product->type,
                'quantity'            => $dat['quantity'],
                'price'               => $convertedPrice = core()->convertPrice($r),
                'price_incl_tax'      => $convertedPrice,
                'base_price'          => $r,
                'base_price_incl_tax' => $r,
                'total'               => $convertedPrice * $dat['quantity'],
                'total_incl_tax'      => $convertedPrice * $dat['quantity'],
                'base_total'          => $r * $dat['quantity'],
                'base_total_incl_tax' => $r * $dat['quantity'],
                'weight'              => $childProduct->weight,
                'total_weight'        => $childProduct->weight * $dat['quantity'],
                'base_total_weight'   => $childProduct->weight * $dat['quantity'],
                'additional'          => $this->getAdditionalOptions($dat),
            ], [
                'parent_id'  => $this->product->id,
                'product_id' => (int) $dat['selected_configurable_option'],
                'sku'        => $childProduct->sku,
                'name'       => $childProduct->name,
                'type'       => $childProduct->type,
                'additional' => [
                    'product_id' => (int) $dat['selected_configurable_option'],
                    'parent_id'  => $this->product->id,
                ],
            ],
        ];
    }


    public function compareOptions($options1, $options2)
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        }

        if (
            isset($options1['selected_configurable_option'])
            && isset($options2['selected_configurable_option'])
        ) {
            return $options1['selected_configurable_option'] === $options2['selected_configurable_option'];
        }

        if (! isset($options1['selected_configurable_option'])) {
            return false;
        }

        if (! isset($options2['selected_configurable_option'])) {
            return false;
        }
    }


    public function getAdditionalOptions($dat)
    {
        $childProduct = app('Webkul\Product\Models\Product')->find($dat['selected_configurable_option']);

        foreach ($this->product->super_attributes as $attribute) {
            $option = $attribute->options()->where('id', $childProduct->{$attribute->code})->first();

            $dat['attributes'][$attribute->code] = [
                'attribute_name' => $attribute->name ? $attribute->name : $attribute->admin_name,
                'option_id'      => $option->id,
                'option_label'   => $option->label ? $option->label : $option->admin_name,
            ];
        }

        return $dat;
    }


    public function getOrderedItem($item)
    {
        return $item->child;
    }


    public function getBaseImage($item)
    {
        $product = $item->product;

        if ($item instanceof \Webkul\Customer\Contracts\Wishlist) {
            if (isset($item->additional['selected_configurable_option'])) {
                $product = Product::find($item->additional['selected_configurable_option']);
            }
        } else {
            if (count($item->child->product->images)) {
                $product = $item->child->product;
            }
        }

        return ProductImage::getProductBaseImage($product);
    }


    public function validateCartItem(CartItemModel $item): CartItemValidationResult
    {
        $validation = new CartItemValidationResult;

        if ($this->isCartItemInactive($item)) {
            $validation->itemIsInactive();

            return $validation;
        }

        $basePrice = $item->child->getTypeInstance()->getFinalPrice($item->quantity);

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


    public function haveSufficientQuantity(int $qty): bool
    {
        foreach ($this->product->variants as $variant) {
            if ($variant->haveSufficientQuantity($qty)) {
                return true;
            }
        }

        return (bool) core()->getConfigData('catalog.inventory.stock_options.back_orders');
    }


    public function isSaleable()
    {
        foreach ($this->product->variants as $variant) {
            if ($variant->isSaleable()) {
                return true;
            }
        }

        return false;
    }


    public function totalQuantity()
    {
        $t = 0;

        foreach ($this->product->variants as $variant) {
            $inventoryIndex = $variant->totalQuantity();

            $t += $inventoryIndex->qty;
        }

        return $t;
    }


    public function getPriceIndexer()
    {
        return app(ConfigurableIndexer::class);
    }
}
