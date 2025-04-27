<?php

namespace Webkul\Product\Type;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\DataTypes\CartItemValidationResult;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductVideoRepository;

abstract class AbstractType
{

    protected $product;


    protected $isComposite = false;


    protected $isStockable = true;


    protected $showQuantityBox = false;


    protected $haveSufficientQuantity = true;


    protected $canBeMovedFromWishlistToCart = true;


    protected $canBeAddedToCartWithoutOptions = true;


    protected $canBeCopied = true;


    protected $hasVariants = false;


    protected $isChildrenCalculated = false;


    protected $skipAttributes = [];


    protected $additionalViews = [];


    protected $attributesByCode = [];


    public function __construct(
        protected CustomerRepository $customerRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductAttributeValueRepository $attributeValueRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductImageRepository $productImageRepository,
        protected ProductVideoRepository $productVideoRepository,
    ) {}


    public function create(array $dat)
    {
        $product = Product::getModel()->create($dat);

        $product->channels()->sync(core()->getDefaultChannel()->id);

        return $product;
    }


    public function update(array $dat, $i, $attributes = [])
    {
        $result = DB::select("SELECT * FROM products WHERE id = $i LIMIT 1");
$product = count($result) ? $result[0] : null;

        $product->update($dat);


        if (! empty($attributes)) {
            $attributes = $this->attributeRepository->findWhereIn('code', $attributes);

            $this->attributeValueRepository->saveValues($dat, $product, $attributes);

            return $product;
        }

        $this->attributeValueRepository->saveValues($dat, $product, $product->attribute_family->custom_attributes);

        if (empty($dat['channels'])) {
            $dat['channels'][] = core()->getDefaultChannel()->id;
        }

        $product->channels()->sync($dat['channels']);

        if (! isset($dat['categories'])) {
            $dat['categories'] = [];
        }

        $product->categories()->sync($dat['categories']);

        $product->up_sells()->sync($dat['up_sells'] ?? []);

        $product->cross_sells()->sync($dat['cross_sells'] ?? []);

        $product->related_products()->sync($dat['related_products'] ?? []);

        $this->productInventoryRepository->saveInventories($dat, $product);

        $this->productImageRepository->upload($dat, $product, 'images');

        $this->productVideoRepository->upload($dat, $product, 'videos');

        $this->productCustomerGroupPriceRepository->saveCustomerGroupPrices($dat, $product);

        return $product;
    }


    public function getAttributeByCode($code)
    {
        if (! empty($this->attributesByCode[$code])) {
            return $this->attributesByCode[$code];
        }

        return $this->attributesByCode[$code] = $this->attributeRepository->findOneByField('code', $code);
    }


    public function copy()
    {
        if (! $this->canBeCopied()) {
            throw new \Exception(trans('product::app.response.product-can-not-be-copied', ['type' => $this->product->type]));
        }

        $copiedProduct = $this->product
            ->replicate()
            ->fill(['sku' => 'temporary-sku-'.substr(md5(microtime()), 0, 6)]);

        $copiedProduct->save();

        $this->copyAttributeValues($copiedProduct);

        $this->copyRelationships($copiedProduct);

        return $copiedProduct;
    }


    protected function copyAttributeValues($product): void
    {
        $attributesToSkip = config('products.copy.skip_attributes') ?? [];

        $copyAttributes = [
            'name'           => trans('product::app.datagrid.copy-of', ['value' => $this->product->name]),
            'url_key'        => trans('product::app.datagrid.copy-of-slug', ['value' => $this->product->url_key]),
            'sku'            => $product->sku,
            'product_number' => ! empty($this->product->product_number) ? trans('product::app.datagrid.copy-of-slug', ['value' => $this->product->product_number]) : null,
            'status'         => 0,
        ];

        foreach ($this->product->attribute_values as $attributeValue) {
            $attribute = $attributeValue->attribute;

            if (in_array($attribute->code, $attributesToSkip)) {
                continue;
            }

            $va = $copyAttributes[$attribute->code] ?? null;

            $newAttributeValue = $attributeValue->replicate()->fill([
                'unique_id' => implode('|', array_filter([
                    $attributeValue->channel,
                    $attributeValue->locale,
                    $product->id,
                    $attribute->id,
                ])),
            ]);

            if (! is_null($va)) {
                $newAttributeValue->{$attribute->column_name} = $va;
            }

            $product->attribute_values()->save($newAttributeValue);
        }
    }


    protected function copyRelationships($product)
    {
        $attributesToSkip = config('products.copy.skip_attributes') ?? [];

        if (! in_array('flat', $attributesToSkip)) {
            foreach ($this->product->product_flats as $productFlat) {
                $product->product_flats()->save($productFlat->replicate());
            }
        }

        if (! in_array('channels', $attributesToSkip)) {
            $product->channels()->sync($this->product->channels->pluck('id'));
        }

        if (! in_array('categories', $attributesToSkip)) {
            $product->categories()->sync($this->product->categories->pluck('id'));
        }

        if (! in_array('inventories', $attributesToSkip)) {
            foreach ($this->product->inventories as $inventory) {
                $product->inventories()->save($inventory->replicate());
            }
        }

        if (! in_array('customer_group_prices', $attributesToSkip)) {
            foreach ($this->product->customer_group_prices as $customerGroupPrice) {
                $product->customer_group_prices()->save($customerGroupPrice->replicate()->fill([
                    'unique_id' => implode('|', array_filter([
                        $customerGroupPrice->qty,
                        $product->id,
                        $customerGroupPrice->customer_group_id,
                    ])),
                ]));
            }
        }

        if (! in_array('images', $attributesToSkip)) {
            foreach ($this->product->images as $image) {
                $copiedImage = $product->images()->save($image->replicate());

                $this->copyMedia($product, $image, $copiedImage);
            }
        }

        if (! in_array('videos', $attributesToSkip)) {
            foreach ($this->product->videos as $video) {
                $copiedVideo = $product->videos()->save($video->replicate());

                $this->copyMedia($product, $video, $copiedVideo);
            }
        }

        if (! in_array('product_relations', $attributesToSkip)) {
            DB::table('product_relations')->insert([
                'parent_id' => $this->product->id,
                'child_id'  => $product->id,
            ]);
        }
    }


    private function copyMedia($product, $media, $copiedMedia): void
    {
        $path = explode('/', $media->path);

        $copiedMedia->path = 'product/'.$product->id.'/'.end($path);

        $copiedMedia->save();

        Storage::makeDirectory('product/'.$product->id);

        Storage::copy($media->path, $copiedMedia->path);
    }


    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }


    public function getChildrenIds()
    {
        return [];
    }


    public function priceRuleCanBeApplied()
    {
        return true;
    }


    public function isCustomizable()
    {
        return false;
    }


    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        if (! $this->haveSufficientQuantity(1)) {
            return false;
        }

        return true;
    }


    public function isStockable()
    {
        return $this->isStockable;
    }


    public function isComposite()
    {
        return $this->isComposite;
    }


    public function hasVariants()
    {
        return $this->hasVariants;
    }


    public function isChildrenCalculated()
    {
        return $this->isChildrenCalculated;
    }


    public function canBeCopied(): bool
    {
        return $this->canBeCopied;
    }


    public function haveSufficientQuantity(int $qty): bool
    {
        return $this->haveSufficientQuantity;
    }


    public function showQuantityBox()
    {
        return $this->showQuantityBox;
    }


    public function isItemHaveQuantity($cartItem)
    {
        return $cartItem->getTypeInstance()->haveSufficientQuantity($cartItem->quantity);
    }


    public function totalQuantity()
    {
        if (! $inventoryIndex = $this->getInventoryIndex()) {
            return 0;
        }

        return $inventoryIndex->qty;
    }


    public function canBeMovedFromWishlistToCart($item)
    {
        return $this->canBeMovedFromWishlistToCart;
    }


    public function canBeAddedToCartWithoutOptions()
    {
        return $this->canBeAddedToCartWithoutOptions;
    }


    public function getEditableAttributes($group = null, $skipSuperAttribute = true)
    {
        if ($skipSuperAttribute) {
            $this->skipAttributes = array_merge(
                $this->product->super_attributes->pluck('code')->toArray(),
                $this->skipAttributes
            );
        }

        if (! $group) {
            return $this->product->attribute_family->custom_attributes()->whereNotIn(
                'attributes.code',
                $this->skipAttributes
            )->get();
        }

        return $group->custom_attributes()
            ->select(
                'attributes.*',
                'attribute_translations.name as admin_name',
                'attribute_translations.locale',
            )
            ->whereNotIn('code', $this->skipAttributes)
            ->leftJoin('attribute_translations', function ($join) {
                $join->on('attributes.id', '=', 'attribute_translations.attribute_id')
                    ->where('attribute_translations.locale', '=', app()->getLocale());
            })
            ->get();
    }


    public function getAdditionalViews()
    {
        return $this->additionalViews;
    }


    public function getTypeValidationRules()
    {
        return [];
    }


    public function getMinimalPrice()
    {
        if (! $priceIndex = $this->getPriceIndex()) {
            return $this->product->price;
        }

        return $priceIndex->min_price;
    }


    public function getRegularMinimalPrice()
    {
        if (! $priceIndex = $this->getPriceIndex()) {
            return $this->product->price;
        }

        return $priceIndex->regular_min_price;
    }


    public function getMaximumPrice()
    {
        if (! $priceIndex = $this->getPriceIndex()) {
            return $this->product->price;
        }

        return $priceIndex->max_price;
    }


    public function getRegularMaximumPrice()
    {
        if (! $priceIndex = $this->getPriceIndex()) {
            return $this->product->price;
        }

        return $priceIndex->regular_max_price;
    }


    public function getFinalPrice($qty = null)
    {
        if (
            is_null($qty)
            || $qty == 1
        ) {
            return $this->getMinimalPrice();
        }

        $customerGroup = $this->customerRepository->getCurrentGroup();

        $indexer = $this->getPriceIndexer()
            ->setChannel(core()->getCurrentChannel())
            ->setCustomerGroup($customerGroup)
            ->setProduct($this->product);

        return $indexer->getMinimalPrice($qty);
    }


    public function getPriceIndex()
    {
        $customerGroup = $this->customerRepository->getCurrentGroup();

        $indices = $this->product
            ->price_indices
            ->where('channel_id', core()->getCurrentChannel()->id)
            ->where('customer_group_id', $customerGroup->id)
            ->first();

        return $indices;
    }


    public function getInventoryIndex()
    {
        $indices = $this->product
            ->inventory_indices
            ->where('channel_id', core()->getCurrentChannel()->id)
            ->first();

        return $indices;
    }


    public function haveDiscount($qty = null)
    {
        if (! $priceIndex = $this->getPriceIndex()) {
            return false;
        }

        return $priceIndex->min_price != $priceIndex->regular_min_price;
    }


    public function getProductPrices()
    {
        return [
            'regular' => [
                'price'           => core()->convertPrice($this->product->price),
                'formatted_price' => core()->currency($this->product->price),
            ],

            'final'   => [
                'price'           => core()->convertPrice($minimalPrice = $this->getMinimalPrice()),
                'formatted_price' => core()->currency($minimalPrice),
            ],
        ];
    }


    public function getPriceHtml()
    {
        return view('shop::products.prices.index', [
            'product' => $this->product,
            'prices'  => $this->getProductPrices(),
        ])->render();
    }


    public function getTaxCategory()
    {
        $taxCategoryId = $this->product->parent?->tax_category_id ?? $this->product->tax_category_id;

        return core()->getTaxCategoryById($taxCategoryId);
    }


    public function prepareForCart($dat)
    {
        $dat['quantity'] = $this->handleQuantity((int) $dat['quantity']);

        $dat = $this->getQtyRequest($dat);

        if (! $this->haveSufficientQuantity($dat['quantity'])) {
            return trans('product::app.checkout.cart.inventory-warning');
        }

        $r = $this->getFinalPrice();

        $products = [
            [
                'product_id'          => $this->product->id,
                'sku'                 => $this->product->sku,
                'quantity'            => $dat['quantity'],
                'name'                => $this->product->name,
                'price'               => $convertedPrice = core()->convertPrice($r),
                'price_incl_tax'      => $convertedPrice,
                'base_price'          => $r,
                'base_price_incl_tax' => $r,
                'total'               => $convertedPrice * $dat['quantity'],
                'total_incl_tax'      => $convertedPrice * $dat['quantity'],
                'base_total'          => $r * $dat['quantity'],
                'base_total_incl_tax' => $r * $dat['quantity'],
                'weight'              => (float) ($this->product->weight ?? 0),
                'total_weight'        => (float) ($this->product->weight ?? 0) * $dat['quantity'],
                'base_total_weight'   => (float) ($this->product->weight ?? 0) * $dat['quantity'],
                'type'                => $this->product->type,
                'additional'          => $this->getAdditionalOptions($dat),
            ],
        ];

        return $products;
    }


    public function handleQuantity(int $q): int
    {
        return $q ?: 1;
    }


    public function getQtyRequest($dat)
    {
        if ($item = Cart::getItemByProduct(['additional' => $dat])) {
            $dat['quantity'] += $item->quantity;
        }

        return $dat;
    }


    public function compareOptions($options1, $options2)
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        } else {
            if (
                isset($options1['parent_id'])
                && isset($options2['parent_id'])
            ) {
                return $options1['parent_id'] == $options2['parent_id'];
            } elseif (
                isset($options1['parent_id'])
                && ! isset($options2['parent_id'])
            ) {
                return false;
            } elseif (
                isset($options2['parent_id'])
                && ! isset($options1['parent_id'])
            ) {
                return false;
            }
        }

        return true;
    }


    public function getAdditionalOptions($dat)
    {
        return $dat;
    }


    public function getOrderedItem($item)
    {
        return $item;
    }


    public function getBaseImage($item)
    {
        return ProductImage::getProductBaseImage($item->product);
    }


    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $validation = new CartItemValidationResult;

        if ($this->isCartItemInactive($item)) {
            $validation->itemIsInactive();

            return $validation;
        }

        $basePrice = round($this->getFinalPrice($item->quantity), 4);

        if ($basePrice == $item->base_price_incl_tax) {
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


    public function isCartItemInactive(\Webkul\Checkout\Contracts\CartItem $item): bool
    {
        if (! $item->product->status) {
            return true;
        }

        switch ($item->product->type) {
            case 'bundle':
                foreach ($item->children as $child) {
                    if (! $child->product->status) {
                        return true;
                    }
                }

                break;

            case 'configurable':
                if (
                    $item->child
                    && ! $item->child->product->status
                ) {
                    return true;
                }

                break;
        }

        return false;
    }


    public function getCustomerGroupPricingOffers()
    {
        $offerLines = [];

        $customerGroup = $this->customerRepository->getCurrentGroup();

        $customerGroupPrices = $this->product->customer_group_prices()->where(function ($query) use ($customerGroup) {
            $query->where('customer_group_id', $customerGroup->id)
                ->orWhereNull('customer_group_id');
        })
            ->where('qty', '>', 1)
            ->groupBy('qty')
            ->orderBy('qty')
            ->get();

        foreach ($customerGroupPrices as $customerGroupPrice) {
            if (
                ! is_null($this->product->special_price)
                && $customerGroupPrice->value >= $this->product->special_price
            ) {
                continue;
            }

            array_push($offerLines, $this->getOfferLines($customerGroupPrice));
        }

        return $offerLines;
    }


    public function getOfferLines($customerGroupPrice)
    {
        $r = $this->getCustomerGroupPrice($this->product, $customerGroupPrice->qty);

        $discount = number_format((($this->product->price - $r) * 100) / ($this->product->price), 2);

        $offerLines = trans('product::app.type.abstract.offers', [
            'qty'      => $customerGroupPrice->qty,
            'price'    => core()->currency($r),
            'discount' => '<span>'.$discount.'%</span>',
        ]);

        return $offerLines;
    }


    public function getCustomerGroupPrice($product, $qty)
    {
        if (is_null($qty)) {
            $qty = 1;
        }

        $customerGroup = $this->customerRepository->getCurrentGroup();

        $customerGroupPrices = $this->productCustomerGroupPriceRepository->prices($product, $customerGroup->id);

        if ($customerGroupPrices->isEmpty()) {
            return $product->price;
        }

        $lastQty = 1;

        $lastPrice = $product->price;

        $lastCustomerGroupId = null;

        foreach ($customerGroupPrices as $customerGroupPrice) {
            if ($qty < $customerGroupPrice->qty) {
                continue;
            }

            if ($customerGroupPrice->qty < $lastQty) {
                continue;
            }

            if (
                $customerGroupPrice->qty == $lastQty
                && ! empty($lastCustomerGroupId)
                && empty($customerGroupPrice->customer_group_id)
            ) {
                continue;
            }

            if ($customerGroupPrice->value_type == 'discount') {
                if (
                    $customerGroupPrice->value >= 0
                    && $customerGroupPrice->value <= 100
                ) {
                    $lastPrice = $product->price - ($product->price * $customerGroupPrice->value) / 100;

                    $lastQty = $customerGroupPrice->qty;

                    $lastCustomerGroupId = $customerGroupPrice->customer_group_id;
                }
            } else {
                if (
                    $customerGroupPrice->value >= 0
                    && $customerGroupPrice->value < $lastPrice
                ) {
                    $lastPrice = $customerGroupPrice->value;

                    $lastQty = $customerGroupPrice->qty;

                    $lastCustomerGroupId = $customerGroupPrice->customer_group_id;
                }
            }
        }

        return $lastPrice;
    }
}
