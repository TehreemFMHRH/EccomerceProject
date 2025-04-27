<?php

namespace Webkul\Product\Type;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\DataTypes\CartItemValidationResult;
use Webkul\Product\Helpers\BundleOption;
use Webkul\Product\Helpers\Indexers\Price\Bundle as BundleIndexer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Models\ProductBundleOptionProduct;
use Webkul\Product\Repositories\ProductBundleOptionRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductVideoRepository;
use Webkul\Tax\Facades\Tax;

class Bundle extends AbstractType
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


    protected $isChildrenCalculated = true;


    protected $showQuantityBox = true;


    protected $canBeAddedToCartWithoutOptions = false;


    public function __construct(
        CustomerRepository $customerRepository,
        AttributeRepository $attributeRepository,
        ProductAttributeValueRepository $attributeValueRepository,
        ProductInventoryRepository $productInventoryRepository,
        ProductImageRepository $productImageRepository,
        ProductVideoRepository $productVideoRepository,
        ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,
        protected ProductBundleOptionRepository $productBundleOptionRepository,

        protected BundleOption $bundleOptionHelper
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

        $this->productBundleOptionRepository->saveBundleOptions($dat, $product);

        return $product;
    }


    protected function copyRelationships($product)
    {
        parent::copyRelationships($product);

        $attributesToSkip = config('products.skipAttributesOnCopy') ?? [];

        if (in_array('bundle_options', $attributesToSkip)) {
            return;
        }

        foreach ($this->product->bundle_options as $bundleOption) {
            $product->bundle_options()->save($bundleOption->replicate());
        }
    }


    public function getChildrenIds()
    {
        return array_unique($this->product->bundle_options()->pluck('product_id')->toArray());
    }


    public function priceRuleCanBeApplied()
    {
        return false;
    }


    public function getFinalPrice($qty = null)
    {
        return round(0, 2);
    }


    public function getProductPrices()
    {
        return [
            'from' => [
                'regular' => [
                    'price'           => core()->convertPrice($regularMinimalPrice = $this->getRegularMinimalPrice()),
                    'formatted_price' => core()->currency($regularMinimalPrice),
                ],

                'final'   => [
                    'price'           => core()->convertPrice($minimalPrice = $this->getMinimalPrice()),
                    'formatted_price' => core()->currency($minimalPrice),
                ],
            ],

            'to' => [
                'regular' => [
                    'price'           => core()->convertPrice($regularMaximumPrice = $this->getRegularMaximumPrice()),
                    'formatted_price' => core()->currency($regularMaximumPrice),
                ],

                'final'   => [
                    'price'           => core()->convertPrice($maximumPrice = $this->getMaximumPrice()),
                    'formatted_price' => core()->currency($maximumPrice),
                ],
            ],
        ];
    }


    public function getPriceHtml()
    {
        return view('shop::products.prices.bundle', [
            'product' => $this->product,
            'prices'  => $this->getProductPrices(),
        ])->render();
    }


    public function prepareForCart($dat)
    {
        $bundleQuantity = parent::handleQuantity((int) $dat['quantity']);

        if (empty($dat['bundle_options'])) {
            return trans('product::app.checkout.cart.missing-options');
        }

        $dat['bundle_options'] = array_filter($this->validateBundleOptionForCart($dat['bundle_options']));

        if (empty($dat['bundle_options'])) {
            return trans('product::app.checkout.cart.missing-options');
        }

        if (! $this->haveSufficientQuantity($dat['quantity'])) {
            return trans('product::app.checkout.cart.inventory-warning');
        }

        $products = parent::prepareForCart($dat);

        foreach ($this->getCartChildProducts($dat) as $productId => $dat) {
            $product = Product::find($productId);

            if ($product->type !== 'simple') {
                return trans('product::app.checkout.cart.selected-products-simple');
            }

            /* need to check each individual quantity as well if don't have then show error */
            if (! $product->getTypeInstance()->haveSufficientQuantity($dat['quantity'] * $bundleQuantity)) {
                return trans('product::app.checkout.cart.inventory-warning');
            }

            if (! $product->getTypeInstance()->isSaleable()) {
                continue;
            }

            $cartProduct = $product->getTypeInstance()->prepareForCart(array_merge($dat, [
                'parent_id' => $this->product->id,
            ]));

            if (is_string($cartProduct)) {
                return $cartProduct;
            }

            $cartProduct[0]['parent_id'] = $this->product->id;

            $products = array_merge($products, $cartProduct);

            $products[0]['price'] += $cartProduct[0]['total'];
            $products[0]['price_incl_tax'] += $cartProduct[0]['total'];
            $products[0]['base_price'] += $cartProduct[0]['base_total'];
            $products[0]['base_price_incl_tax'] += $cartProduct[0]['base_total'];
            $products[0]['total'] += $cartProduct[0]['total'];
            $products[0]['total_incl_tax'] += $cartProduct[0]['total'];
            $products[0]['base_total'] += $cartProduct[0]['base_total'];
            $products[0]['base_total_incl_tax'] += $cartProduct[0]['base_total'];
            $products[0]['weight'] += $cartProduct[0]['total_weight'];
        }

        $products[0]['total_weight'] = $products[0]['base_total_weight'] = $products[0]['weight'] * $products[0]['quantity'];

        return $products;
    }


    public function getCartChildProducts($dat)
    {
        $products = [];

        foreach ($dat['bundle_options'] as $optionId => $optionProductIds) {
            foreach ($optionProductIds as $optionProductId) {
                if (! $optionProductId) {
                    continue;
                }

                $optionProduct = ProductBundleOptionProduct::findOneWhere([
                    'id'                       => $optionProductId,
                    'product_bundle_option_id' => $optionId,
                ]);

                if (! $optionProduct?->product->getTypeInstance()->isSaleable()) {
                    continue;
                }

                $qty = $dat['bundle_option_qty'][$optionId] ?? $optionProduct->qty;

                if (! isset($products[$optionProduct->product_id])) {
                    $products[$optionProduct->product_id] = [
                        'product_id' => $optionProduct->product_id,
                        'quantity'   => $qty,
                    ];
                } else {
                    $products[$optionProduct->product_id] = array_merge($products[$optionProduct->product_id], [
                        'quantity' => $products[$optionProduct->product_id]['quantity'] + $qty,
                    ]);
                }
            }
        }

        return $products;
    }


    public function compareOptions($options1, $options2)
    {
        if (
            isset($options1['bundle_options'])
            && isset($options2['bundle_options'])
        ) {
            return $options1['bundle_options'] == $options2['bundle_options']
                && $options1['bundle_option_qty'] == $this->getOptionQuantities($options2);
        }

        return false;
    }


    public function validateBundleOptionForCart($dat)
    {
        foreach ($dat as $key => $va) {
            if (is_array($va)) {
                $dat[$key] = $this->validateBundleOptionForCart($va);
            } elseif ($va) {
                $dat[$key] = (int) $va;
            } else {
                unset($dat[$key]);
            }
        }

        return $dat;
    }


    public function getAdditionalOptions($dat)
    {
        $bundleOptionQuantities = $dat['bundle_option_qty'] ?? [];

        $productBundleOptions = $this->productBundleOptionRepository
            ->whereIn('id', array_keys($dat['bundle_options']))
            ->orderBy('sort_order')
            ->get();

        $dat['attributes'] = [];

        foreach ($productBundleOptions as $option) {
            $labels = [];

            foreach ($dat['bundle_options'][$option->id] as $optionProductId) {
                if (! $optionProductId) {
                    continue;
                }

                $optionProduct = ProductBundleOptionProduct::find($optionProductId);

                $qty = $dat['bundle_option_qty'][$option->id] ?? $optionProduct->qty;

                if (! isset($dat['bundle_option_qty'][$option->id])) {
                    $bundleOptionQuantities[$option->id] = $qty;
                }

                $label = $qty.' x '.$optionProduct->product->name;

                $r = $optionProduct->product->getTypeInstance()->getMinimalPrice();

                if ($r != 0) {
                    $label .= ' '.core()->currency($r);
                }

                $labels[] = $label;
            }

            if (count($labels)) {
                $dat['attributes'][] = [
                    'attribute_name' => $option->label,
                    'option_id'      => $option->id,
                    'option_label'   => implode(', ', $labels),
                ];
            }
        }

        $dat['bundle_option_qty'] = $bundleOptionQuantities;

        return $dat;
    }


    public function getOptionQuantities($dat)
    {
        $optionQuantities = [];

        foreach ($dat['bundle_options'] as $optionId => $optionProductIds) {
            foreach ($optionProductIds as $optionProductId) {
                if (! $optionProductId) {
                    continue;
                }

                if (isset($dat['bundle_option_qty'][$optionId])) {
                    $optionQuantities[$optionId] = $dat['bundle_option_qty'][$optionId];

                    continue;
                }

                $optionProduct = ProductBundleOptionProduct::find($optionProductId);

                $optionQuantities[$optionId] = $optionProduct->qty;
            }
        }

        return $optionQuantities;
    }


    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $validation = new CartItemValidationResult;

        if (parent::isCartItemInactive($item)) {
            $validation->itemIsInactive();

            return $validation;
        }

        $basePrice = 0;

        foreach ($item->children as $childItem) {
            $childValidation = $childItem->getTypeInstance()->validateCartItem($childItem);

            if ($childValidation->isItemInactive()) {
                $validation->itemIsInactive();
            }

            if ($childValidation->isCartInvalid()) {
                $validation->cartIsInvalid();
            }

            $basePrice += $childItem->base_price * $childItem->quantity;
        }

        $basePrice = round($basePrice, 4);

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

        $item->additional = $this->getAdditionalOptions($item->additional);

        $item->save();

        return $validation;
    }


    public function haveSufficientQuantity(int $qty): bool
    {
        // to consider a bundle in stock we need to check that at least one product from each required group is available for the given quantity
        foreach ($this->product->bundle_options as $option) {
            if ($option->is_required) {
                foreach ($option->bundle_option_products as $bundleOptionProduct) {
                    // as long as at least one product in the required group is available we can continue checking other groups
                    if ($bundleOptionProduct->product->haveSufficientQuantity($bundleOptionProduct->qty * $qty)) {
                        continue 2;
                    }
                }

                // if any required option does not have any in-stock product option we will get here.
                return false;
            }
        }

        return true;
    }


    public function getPriceIndexer()
    {
        return app(BundleIndexer::class);
    }


    public function getTypeValidationRules()
    {
        return [
            'bundle_options' => 'array',
            'bundle_options' => function ($attribute, $va, $fail) {
                $associatedProductIds = collect($va)
                    ->pluck('products')
                    ->flatten(1)
                    ->pluck('product_id')
                    ->toArray();

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
