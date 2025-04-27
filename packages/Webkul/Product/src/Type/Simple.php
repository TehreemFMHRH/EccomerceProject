<?php

namespace Webkul\Product\Type;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Checkout\Contracts\CartItem;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\DataTypes\CartItemValidationResult;
use Webkul\Product\Helpers\Indexers\Price\Simple as SimpleIndexer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Models\ProductBundleOptionProduct;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductCustomizableOptionPriceRepository;
use Webkul\Product\Repositories\ProductCustomizableOptionRepository;
use Webkul\Product\Models\ProductGroupedProduct;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductVideoRepository;

class Simple extends AbstractType
{

    protected $showQuantityBox = true;


    public function __construct(
        CustomerRepository $customerRepository,
        AttributeRepository $attributeRepository,
        ProductAttributeValueRepository $attributeValueRepository,
        ProductInventoryRepository $productInventoryRepository,
        ProductImageRepository $productImageRepository,
        ProductVideoRepository $productVideoRepository,
        ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,


        protected ProductCustomizableOptionRepository $productCustomizableOptionRepository,
        protected ProductCustomizableOptionPriceRepository $productCustomizableOptionPriceRepository,
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

        $this->productCustomizableOptionRepository->saveCustomizableOptions($dat, $product);

        return $product;
    }


    public function canBeAddedToCartWithoutOptions()
    {
        if ($this->product->customizable_options->isNotEmpty()) {
            return false;
        }

        return $this->canBeAddedToCartWithoutOptions;
    }


    public function isCustomizable()
    {

        if ($this->product->parent) {
            return false;
        }


        $associatedWithGroupedProduct = ProductGroupedProduct::where('associated_product_id', $this->product->id)->first();

        if ($associatedWithGroupedProduct) {
            return false;
        }


        $associatedWithBundleProduct = ProductBundleOptionProduct::where('product_id', $this->product->id)->first();

        if ($associatedWithBundleProduct) {
            return false;
        }

        return true;
    }


    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        return $this->haveSufficientQuantity(1);
    }


    public function haveSufficientQuantity(int $qty): bool
    {
        if (! $this->product->manage_stock) {
            return true;
        }

        return $qty <= $this->totalQuantity() ?: (bool) core()->getConfigData('catalog.inventory.stock_options.back_orders');
    }


    public function getMaximumPrice()
    {
        return $this->product->price;
    }


    public function getPriceIndexer()
    {
        return app(SimpleIndexer::class);
    }


    public function prepareForCart($dat)
    {
        if (
            $this->product->customizable_options->where('is_required', 1)->isNotEmpty()
            && empty($dat['customizable_options'])
        ) {
            return trans('product::app.checkout.cart.missing-options');
        }

        $dat['quantity'] = $this->handleQuantity((int) $dat['quantity']);

        $dat = $this->getQtyRequest($dat);

        if (! $this->haveSufficientQuantity($dat['quantity'])) {
            return trans('product::app.checkout.cart.inventory-warning');
        }

        $r = $this->getFinalPrice();

        if (! empty($dat['customizable_options'])) {
            $formattedCustomizableOptions = $this->formatRequestedCustomizableOptions($dat['customizable_options']);


            foreach ($formattedCustomizableOptions->where('type', 'file') as $option) {
                if (
                    isset($option['prices'][0]['label'])
                    && $option['prices'][0]['label'] instanceof \Illuminate\Http\UploadedFile
                ) {
                    $extension = $option['prices'][0]['label']->getClientOriginalExtension();

                    if (
                        ! empty($option['supported_file_extensions'])
                        && ! in_array(strtolower($extension), $option['supported_file_extensions'])
                    ) {
                        return trans('product::app.checkout.cart.invalid-file-extension');
                    }
                }
            }


            $formattedCustomizableOptions = $formattedCustomizableOptions->map(function ($option) use ($dat) {
                if ($option['type'] === 'file') {
                    $file = $option['prices'][0]['label'];

                    if (
                        ! empty($file)
                        && $file instanceof UploadedFile
                    ) {
                        $filePath = $file->store("carts/{$dat['cart_id']}");

                        $option['prices'][0]['label'] = $filePath;
                    } else {
                        $filePath = collect($dat['formatted_customizable_options'] ?? [])
                            ->firstWhere('id', $option['id']);

                        $option['prices'][0]['label'] = $filePath['prices'][0]['label'] ?? '';
                    }
                }

                return $option;
            });

            $r += $formattedCustomizableOptions->sum('total_price');

            $dat['formatted_customizable_options'] = $formattedCustomizableOptions->toArray();
        }

        return [
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
    }


    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $validation = new CartItemValidationResult;

        if ($this->isCartItemInactive($item)) {
            $validation->itemIsInactive();

            return $validation;
        }

        $basePrice = round($this->getFinalPrice($item->quantity), 4);


        if (! empty($item->additional['customizable_options'])) {
            $formattedCustomizableOptions = $this->formatRequestedCustomizableOptions($item->additional['customizable_options']);

            $basePrice += round($formattedCustomizableOptions->sum('total_price'), 4);
        }

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


    public function getTypeValidationRules()
    {
        return [
            'customizable_options' => [
                'array',
                function ($attribute, $va, $fail) {
                    if (! $this->isCustomizable()) {
                        $fail(trans('admin::app.catalog.products.edit.types.simple.customizable-options.validations.associated-product'));
                    }
                },
            ],
        ];
    }


    public function getAdditionalOptions($dat)
    {
        if (! empty($dat['formatted_customizable_options'])) {
            $dat['attributes'] = [];

            foreach ($dat['formatted_customizable_options'] as $option) {
                if (in_array($option['type'], ['checkbox', 'multiselect'])) {
                    $dat['attributes'][] = [
                        'attribute_type' => $option['type'],
                        'attribute_name' => $option['label'][app()->getLocale()] ?? $option['label'][app()->getFallbackLocale()],
                        'option_label'   => collect($option['prices'])->pluck('label')->join(', ', ' and '),
                    ];
                } else {
                    $dat['attributes'][] = [
                        'attribute_type' => $option['type'],
                        'attribute_name' => $option['label'][app()->getLocale()] ?? $option['label'][app()->getFallbackLocale()],
                        'option_label'   => $option['prices'][0]['label'],
                    ];
                }
            }
        }

        return $dat;
    }


    public function compareOptions($options1, $options2)
    {
        if (
            isset($options1['customizable_options'])
            && isset($options2['customizable_options'])
        ) {
            return $options1['customizable_options'] == $options2['customizable_options'];
        }

        if (
            (
                ! isset($options1['customizable_options'])
                && isset($options2['customizable_options'])
            )
            || (
                isset($options1['customizable_options'])
                && ! isset($options2['customizable_options'])
            )
        ) {
            return false;
        }

        if (
            ! isset($options1['customizable_options'])
            && ! isset($options2['customizable_options'])
        ) {
            return $this->product->id == $options2['product_id'];
        }

        return false;
    }


    protected function formatRequestedCustomizableOptions(array $requestedCustomizableOptions): Collection
    {
        $formattedCustomizableOptions = [];

        $customizableOptions = $this->productCustomizableOptionRepository
            ->with(['customizable_option_prices'])
            ->where('product_id', $this->product->id)
            ->whereIn('id', array_keys($requestedCustomizableOptions))
            ->get();

        foreach ($customizableOptions as $customizableOption) {
            switch ($customizableOption->type) {
                case 'text':
                case 'textarea':
                case 'date':
                case 'datetime':
                case 'time':
                    if (! $customizableOption->is_required && empty($requestedCustomizableOptions[$customizableOption->id][0])) {
                        continue 2;
                    }

                    $optionPrice = $customizableOption->customizable_option_prices->first();

                    $formattedCustomizableOptions[] = [
                        'id'          => $customizableOption->id,
                        'type'        => $customizableOption->type,
                        'label'       => $customizableOption->translations->pluck('label', 'locale')->toArray(),
                        'prices'      => [[
                            'id'    => $optionPrice->id,
                            'label' => $requestedCustomizableOptions[$customizableOption->id][0],
                            'price' => $optionPrice->price,
                        ]],
                        'total_price' => $optionPrice->price,
                    ];

                    break;

                case 'checkbox':
                case 'radio':
                case 'select':
                case 'multiselect':
                    if (! $customizableOption->is_required && empty($requestedCustomizableOptions[$customizableOption->id])) {
                        continue 2;
                    }


                    if (in_array(0, $requestedCustomizableOptions[$customizableOption->id])) {
                        continue 2;
                    }

                    $optionPrices = $customizableOption->customizable_option_prices
                        ->whereIn('id', $requestedCustomizableOptions[$customizableOption->id]);

                    $formattedCustomizableOptions[] = [
                        'id'          => $customizableOption->id,
                        'type'        => $customizableOption->type,
                        'label'       => $customizableOption->translations->pluck('label', 'locale')->toArray(),
                        'prices'      => $optionPrices->map(fn ($r) => [
                            'id'    => $r->id,
                            'label' => $r->label,
                            'price' => $r->price,
                        ])->values()->toArray(),
                        'total_price' => $optionPrices->sum('price'),
                    ];

                    break;

                case 'file':
                    if (! $customizableOption->is_required && empty($requestedCustomizableOptions[$customizableOption->id][0])) {
                        continue 2;
                    }

                    $optionPrice = $customizableOption->customizable_option_prices->first();


                    $formattedCustomizableOptions[] = [
                        'id'                        => $customizableOption->id,
                        'type'                      => $customizableOption->type,
                        'label'                     => $customizableOption->translations->pluck('label', 'locale')->toArray(),
                        'supported_file_extensions' => collect(explode(',', $customizableOption->supported_file_extensions))
                            ->map(fn ($extension) => trim($extension))
                            ->filter(fn ($extension) => ! empty($extension))
                            ->toArray(),
                        'prices'      => [[
                            'id'    => $optionPrice->id,
                            'label' => $requestedCustomizableOptions[$customizableOption->id][0],
                            'price' => $optionPrice->price,
                        ]],
                        'total_price' => $optionPrice->price,
                    ];

                    break;
            }
        }

        return collect($formattedCustomizableOptions);
    }
}
