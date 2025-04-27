<?php

namespace Webkul\Product\Type;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\BookingProduct\Helpers\Booking as BookingHelper;
use Webkul\BookingProduct\Models\BookingProduct;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\DataTypes\CartItemValidationResult;
use Webkul\Product\Helpers\Indexers\Price\Virtual as VirtualIndexer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductVideoRepository;

class Booking extends AbstractType
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


    protected $isComposite = true;


    protected $isStockable = false;


    public function __construct(
        protected CustomerRepository $customerRepository,
        protected AttributeRepository $attributeRepository,

        protected ProductAttributeValueRepository $attributeValueRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductImageRepository $productImageRepository,
        protected ProductVideoRepository $productVideoRepository,
        protected BookingHelper $bookingHelper
    ) {}


    public function update(array $dat, $i, $attribute = 'id')
    {
        $product = parent::update($dat, $i, $attribute);

        if (request()->route()->getName() != 'admin.catalog.products.mass_update') {
            $bookingProduct = BookingProduct::findOneByField('product_id', $i);

            $bookingProduct
                ? BookingProduct::update($dat['booking'], $bookingProduct->id)
                : BookingProduct::create(array_merge($dat['booking'], [
                    'product_id' => $i,
                ]));
        }

        return $product;
    }


    public function getBookingProduct(int $productId)
    {
        $bookingProducts = [];

        if (isset($bookingProducts[$productId])) {
            return $bookingProducts[$productId];
        }

        return $bookingProducts[$productId] = BookingProduct::findOneByField('product_id', $productId);
    }


    public function showQuantityBox(): bool
    {
        $bookingProduct = $this->getBookingProduct($this->product->id);

        return in_array($bookingProduct->type, ['default', 'rental', 'table']);
    }


    public function isItemHaveQuantity($cartItem): bool
    {
        $bookingProduct = $this->getBookingProduct($this->product->id);

        return app($this->bookingHelper->getTypeHelper($bookingProduct->type))->isItemHaveQuantity($cartItem);
    }

    public function haveSufficientQuantity(int $qty): bool
    {
        return true;
    }


    public function isComposite()
    {
        return $this->isComposite;
    }


    public function prepareForCart($dat)
    {
        if (empty($dat['booking'])) {
            return trans('shop::app.products.booking.cart.integrity.missing_options');
        }

        $products = [];

        $bookingProduct = $this->getBookingProduct($dat['product_id']);

        if ($bookingProduct->type == 'rental') {
            if (isset($dat['booking']['slot']['from'])) {
                $time = $dat['booking']['slot']['to'] - $dat['booking']['slot']['from'];

                $hours = floor($time / 60) / 60;

                if ($hours > 1) {
                    return trans('shop::app.products.booking.cart.integrity.select_hourly_duration');
                }
            }

            $products = parent::prepareForCart($dat);
        } elseif ($bookingProduct->type == 'event') {
            if (
                Carbon::now() > $bookingProduct->available_from
                && Carbon::now() > $bookingProduct->available_to
            ) {
                return trans('shop::app.products.booking.cart.integrity.event.expired');
            }

            $filtered = Arr::where($dat['booking']['qty'], function ($qty, $key) {
                return $qty != 0;
            });

            if (! count($filtered)) {
                return trans('shop::app.products.booking.cart.integrity.missing_options');
            }

            $cartProductsList = [];

            foreach ($dat['booking']['qty'] as $ticketId => $qty) {
                if (! $qty) {
                    continue;
                }

                $dat['quantity'] = $qty;
                $dat['booking']['ticket_id'] = $ticketId;
                $dat['booking']['slot'] = implode('-', [$bookingProduct->available_from->timestamp, $bookingProduct->available_to->timestamp]);
                $cartProducts = parent::prepareForCart($dat);

                if (is_string($cartProducts)) {
                    return $cartProducts;
                }

                $cartProductsList[] = $cartProducts;
            }

            $products = array_merge(...$cartProductsList);
        } else {
            $products = parent::prepareForCart($dat);
        }

        $typeHelper = app($this->bookingHelper->getTypeHelper($bookingProduct->type));

        if (! $typeHelper->isSlotAvailable($products)) {
            return trans('shop::app.products.booking.cart.integrity.inventory_warning');
        }

        $products = $typeHelper->addAdditionalPrices($products);

        return $products;
    }


    public function compareOptions($options1, $options2): bool
    {
        if ($this->product->id !== (int) $options2['product_id']) {
            return false;
        }

        if (
            isset($options1['booking'], $options2['booking'])
            && isset($options1['booking']['ticket_id'], $options2['booking']['ticket_id'])
            && $options1['booking']['ticket_id'] === $options2['booking']['ticket_id']
        ) {
            return true;
        }

        return false;
    }


    public function getAdditionalOptions($dat): array
    {
        return $this->bookingHelper->getCartItemOptions($dat);
    }


    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $result = new CartItemValidationResult;

        if (parent::isCartItemInactive($item)) {
            $result->itemIsInactive();

            return $result;
        }

        if (! $bookingProduct = $this->getBookingProduct($item->product_id)) {
            $result->cartIsInvalid();

            return $result;
        }

        return app($this->bookingHelper->getTypeHelper($bookingProduct->type))->validateCartItem($item);
    }


    public function getPriceIndexer()
    {
        return app(VirtualIndexer::class);
    }
}
