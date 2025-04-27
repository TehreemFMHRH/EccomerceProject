<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Mail\Order\InventorySourceNotification;
use Webkul\Admin\Mail\Order\ShippedNotification as AdminShippedNotification;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Checkout\Models\CartPayment;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Core\Models\CoreConfig;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\Shipment;
use Webkul\Shop\Mail\Order\ShippedNotification as ShopShippedNotification;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

it('should returns the shipment page', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.sales.shipments.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.sales.shipments.index.title'));
});

it('should fails the validation error when store the the shipment to the order', function () {
    // Arrange.
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $k = Customer::factory()->create();

    $cart = Cart::factory()->create([
        'customer_id'         => $k->id,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
        'customer_email'      => $k->email,
        'is_guest'            => 0,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating'     => '0',
        'is_buy_now' => '0',
        'quantity'   => '1',
    ];

    $cartItem = CartItem::factory()->create([
        'cart_id'           => $cart->id,
        'product_id'        => $product->id,
        'sku'               => $product->sku,
        'quantity'          => $additional['quantity'],
        'name'              => $product->name,
        'price'             => $convertedPrice = core()->convertPrice($r = $product->price),
        'base_price'        => $r,
        'total'             => $convertedPrice * $additional['quantity'],
        'base_total'        => $r * $additional['quantity'],
        'weight'            => $product->weight ?? 0,
        'total_weight'      => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type'              => $product->type,
        'additional'        => $additional,
    ]);

    $customerAddress = CustomerAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CustomerAddress::ADDRESS_TYPE,
    ]);

    $cartBillingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
    ]);

    $cartShippingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    $cartPayment = CartPayment::factory()->create([
        'cart_id'      => $cart->id,
        'method'       => $paymentMethod = 'cashondelivery',
        'method_title' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title'),
    ]);

    $cartShippingRate = CartShippingRate::factory()->create([
        'carrier'            => 'free',
        'carrier_title'      => 'Free shipping',
        'method'             => 'free_free',
        'method_title'       => 'Free Shipping',
        'method_description' => 'Free Shipping',
        'cart_address_id'    => $cartShippingAddress->id,
    ]);

    $o = Order::factory()->create([
        'cart_id'             => $cart->id,
        'customer_id'         => $k->id,
        'customer_email'      => $k->email,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
    ]);

    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id'   => $o->id,
        'sku'        => $product->sku,
        'type'       => $product->type,
        'name'       => $product->name,
    ]);

    $orderBillingAddress = OrderAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
    ]);

    $orderShippingAddress = OrderAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    $orderPayment = OrderPayment::factory()->create([
        'order_id' => $o->id,
    ]);

    foreach ($o->items as $item) {
        foreach ($o->channel->inventory_sources as $inventorySource) {
            $items[$item->id][$inventorySource->id] = $inventorySource->id;
        }
    }

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.sales.shipments.store', $o->id))
        ->assertJsonValidationErrorFor('shipment.source')
        ->assertUnprocessable();

    $cart->refresh();

    $cartItem->refresh();

    $cartBillingAddress->refresh();

    $cartShippingAddress->refresh();

    $orderBillingAddress->refresh();

    $orderShippingAddress->refresh();

    $o->refresh();

    $orderItem->refresh();

    $this->assertModelWise([
        Cart::class => [
            $this->prepareCart($cart),
        ],

        CartItem::class => [
            $this->prepareCartItem($cartItem),
        ],

        CartPayment::class => [
            $this->prepareCartPayment($cartPayment),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartBillingAddress),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartShippingAddress),
        ],

        CartShippingRate::class => [
            $this->prepareCartShippingRate($cartShippingRate),
        ],

        CustomerAddress::class => [
            $this->prepareAddress($customerAddress),
        ],

        Order::class => [
            $this->prepareOrder($o),
        ],

        OrderItem::class => [
            $this->prepareOrderItem($orderItem),
        ],

        OrderAddress::class => [
            $this->prepareAddress($orderBillingAddress),

            $this->prepareAddress($orderShippingAddress),
        ],

        OrderPayment::class => [
            $this->prepareOrderPayment($orderPayment),
        ],
    ]);
});

it('should store the shipment to the order', function () {
    // Arrange.
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $k = Customer::factory()->create();

    $cart = Cart::factory()->create([
        'customer_id'         => $k->id,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
        'customer_email'      => $k->email,
        'is_guest'            => 0,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating'     => '0',
        'is_buy_now' => '0',
        'quantity'   => '1',
    ];

    $cartItem = CartItem::factory()->create([
        'cart_id'           => $cart->id,
        'product_id'        => $product->id,
        'sku'               => $product->sku,
        'quantity'          => $additional['quantity'],
        'name'              => $product->name,
        'price'             => $convertedPrice = core()->convertPrice($r = $product->price),
        'base_price'        => $r,
        'total'             => $convertedPrice * $additional['quantity'],
        'base_total'        => $r * $additional['quantity'],
        'weight'            => $product->weight ?? 0,
        'total_weight'      => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type'              => $product->type,
        'additional'        => $additional,
    ]);

    $customerAddress = CustomerAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CustomerAddress::ADDRESS_TYPE,
    ]);

    $cartBillingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
    ]);

    $cartShippingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    $cartPayment = CartPayment::factory()->create([
        'cart_id'      => $cart->id,
        'method'       => $paymentMethod = 'cashondelivery',
        'method_title' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title'),
    ]);

    $cartShippingRate = CartShippingRate::factory()->create([
        'carrier'            => 'free',
        'carrier_title'      => 'Free shipping',
        'method'             => 'free_free',
        'method_title'       => 'Free Shipping',
        'method_description' => 'Free Shipping',
        'cart_address_id'    => $cartShippingAddress->id,
    ]);

    $o = Order::factory()->create([
        'cart_id'             => $cart->id,
        'customer_id'         => $k->id,
        'customer_email'      => $k->email,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
    ]);

    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id'   => $o->id,
        'sku'        => $product->sku,
        'type'       => $product->type,
        'name'       => $product->name,
    ]);

    $orderBillingAddress = OrderAddress::factory()->create([
        ...Arr::except($cartBillingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'order_id'     => $o->id,
    ]);

    $orderShippingAddress = OrderAddress::factory()->create([
        ...Arr::except($cartShippingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
        'order_id'     => $o->id,
    ]);

    $orderPayment = OrderPayment::factory()->create([
        'order_id' => $o->id,
    ]);

    $shipmentSources = $o->channel->inventory_sources->pluck('id')->toArray();

    foreach ($o->items as $item) {
        foreach ($o->channel->inventory_sources as $inventorySource) {
            $items[$item->id][$inventorySource->id] = $inventorySource->id;
        }
    }

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.sales.shipments.store', $o->id), [
        'shipment' => $dat = [
            'source'        => fake()->randomElement($shipmentSources),
            'items'         => $items,
            'carrier_title' => fake()->name(),
            'track_number'  => fake()->numerify('##########'),
        ],
    ])
        ->assertRedirect(route('admin.sales.orders.view', $o->id))
        ->isRedirection();

    $cart->refresh();

    $cartItem->refresh();

    $cartBillingAddress->refresh();

    $cartShippingAddress->refresh();

    $orderBillingAddress->refresh();

    $orderShippingAddress->refresh();

    $o->refresh();

    $orderItem->refresh();

    $this->assertModelWise([
        Cart::class => [
            $this->prepareCart($cart),
        ],

        CartItem::class => [
            $this->prepareCartItem($cartItem),
        ],

        CartPayment::class => [
            $this->prepareCartPayment($cartPayment),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartBillingAddress),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartShippingAddress),
        ],

        CartShippingRate::class => [
            $this->prepareCartShippingRate($cartShippingRate),
        ],

        CustomerAddress::class => [
            $this->prepareAddress($customerAddress),
        ],

        Order::class => [
            $this->prepareOrder($o),
        ],

        OrderItem::class => [
            $this->prepareOrderItem($orderItem),
        ],

        OrderAddress::class => [
            $this->prepareAddress($orderBillingAddress),

            $this->prepareAddress($orderShippingAddress),
        ],

        OrderPayment::class => [
            $this->prepareOrderPayment($orderPayment),
        ],

        Shipment::class => [
            [
                'order_id'      => $o->id,
                'carrier_title' => $dat['carrier_title'],
                'track_number'  => $dat['track_number'],
            ],
        ],
    ]);
});

it('should store the shipment to the order and send email to the customer and admin and also send a mail to inventory source', function () {
    // Arrange.
    Mail::fake();

    CoreConfig::where('code', 'emails.general.notifications.emails.general.notifications.new_shipment')->update([
        'value' => 1,
    ]);

    CoreConfig::where('code', 'emails.general.notifications.emails.general.notifications.new_shipment_mail_to_admin')->update([
        'value' => 1,
    ]);

    CoreConfig::where('code', 'emails.general.notifications.emails.general.notifications.new_inventory_source')->update([
        'value' => 1,
    ]);

    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $k = Customer::factory()->create();

    $cart = Cart::factory()->create([
        'customer_id'         => $k->id,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
        'customer_email'      => $k->email,
        'is_guest'            => 0,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating'     => '0',
        'is_buy_now' => '0',
        'quantity'   => '1',
    ];

    $cartItem = CartItem::factory()->create([
        'cart_id'           => $cart->id,
        'product_id'        => $product->id,
        'sku'               => $product->sku,
        'quantity'          => $additional['quantity'],
        'name'              => $product->name,
        'price'             => $convertedPrice = core()->convertPrice($r = $product->price),
        'base_price'        => $r,
        'total'             => $convertedPrice * $additional['quantity'],
        'base_total'        => $r * $additional['quantity'],
        'weight'            => $product->weight ?? 0,
        'total_weight'      => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type'              => $product->type,
        'additional'        => $additional,
    ]);

    $customerAddress = CustomerAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CustomerAddress::ADDRESS_TYPE,
    ]);

    $cartBillingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
    ]);

    $cartShippingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    $cartPayment = CartPayment::factory()->create([
        'cart_id'      => $cart->id,
        'method'       => $paymentMethod = 'cashondelivery',
        'method_title' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title'),
    ]);

    $cartShippingRate = CartShippingRate::factory()->create([
        'carrier'            => 'free',
        'carrier_title'      => 'Free shipping',
        'method'             => 'free_free',
        'method_title'       => 'Free Shipping',
        'method_description' => 'Free Shipping',
        'cart_address_id'    => $cartShippingAddress->id,
    ]);

    $o = Order::factory()->create([
        'cart_id'             => $cart->id,
        'customer_id'         => $k->id,
        'customer_email'      => $k->email,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
    ]);

    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id'   => $o->id,
        'sku'        => $product->sku,
        'type'       => $product->type,
        'name'       => $product->name,
    ]);

    $orderBillingAddress = OrderAddress::factory()->create([
        ...Arr::except($cartBillingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'order_id'     => $o->id,
    ]);

    $orderShippingAddress = OrderAddress::factory()->create([
        ...Arr::except($cartShippingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
        'order_id'     => $o->id,
    ]);

    $orderPayment = OrderPayment::factory()->create([
        'order_id' => $o->id,
    ]);

    $shipmentSources = $o->channel->inventory_sources->pluck('id')->toArray();

    foreach ($o->items as $item) {
        foreach ($o->channel->inventory_sources as $inventorySource) {
            $items[$item->id][$inventorySource->id] = $inventorySource->id;
        }
    }

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.sales.shipments.store', $o->id), [
        'shipment' => $dat = [
            'source'        => fake()->randomElement($shipmentSources),
            'items'         => $items,
            'carrier_title' => fake()->name(),
            'track_number'  => fake()->numerify('##########'),
        ],
    ])
        ->assertRedirect(route('admin.sales.orders.view', $o->id))
        ->isRedirection();

    $cart->refresh();

    $cartItem->refresh();

    $cartBillingAddress->refresh();

    $cartShippingAddress->refresh();

    $orderBillingAddress->refresh();

    $orderShippingAddress->refresh();

    $o->refresh();

    $orderItem->refresh();

    $this->assertModelWise([
        Cart::class => [
            $this->prepareCart($cart),
        ],

        CartItem::class => [
            $this->prepareCartItem($cartItem),
        ],

        CartPayment::class => [
            $this->prepareCartPayment($cartPayment),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartBillingAddress),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartShippingAddress),
        ],

        CartShippingRate::class => [
            $this->prepareCartShippingRate($cartShippingRate),
        ],

        CustomerAddress::class => [
            $this->prepareAddress($customerAddress),
        ],

        Order::class => [
            $this->prepareOrder($o),
        ],

        OrderItem::class => [
            $this->prepareOrderItem($orderItem),
        ],

        OrderAddress::class => [
            $this->prepareAddress($orderBillingAddress),

            $this->prepareAddress($orderShippingAddress),
        ],

        OrderPayment::class => [
            $this->prepareOrderPayment($orderPayment),
        ],

        Shipment::class => [
            [
                'order_id'      => $o->id,
                'carrier_title' => $dat['carrier_title'],
                'track_number'  => $dat['track_number'],
            ],
        ],
    ]);

    Mail::assertQueued(AdminShippedNotification::class);

    Mail::assertQueued(ShopShippedNotification::class);

    Mail::assertQueued(InventorySourceNotification::class);

    Mail::assertQueuedCount(3);
});

it('should return the view page of shipments', function () {
    // Arrange.
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $k = Customer::factory()->create();

    $cart = Cart::factory()->create([
        'customer_id'         => $k->id,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
        'customer_email'      => $k->email,
        'is_guest'            => 0,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating'     => '0',
        'is_buy_now' => '0',
        'quantity'   => '1',
    ];

    $cartItem = CartItem::factory()->create([
        'cart_id'           => $cart->id,
        'product_id'        => $product->id,
        'sku'               => $product->sku,
        'quantity'          => $additional['quantity'],
        'name'              => $product->name,
        'price'             => $convertedPrice = core()->convertPrice($r = $product->price),
        'base_price'        => $r,
        'total'             => $convertedPrice * $additional['quantity'],
        'base_total'        => $r * $additional['quantity'],
        'weight'            => $product->weight ?? 0,
        'total_weight'      => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type'              => $product->type,
        'additional'        => $additional,
    ]);

    $customerAddress = CustomerAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CustomerAddress::ADDRESS_TYPE,
    ]);

    $cartBillingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
    ]);

    $cartShippingAddress = CartAddress::factory()->create([
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    $cartPayment = CartPayment::factory()->create([
        'cart_id'      => $cart->id,
        'method'       => $paymentMethod = 'cashondelivery',
        'method_title' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title'),
    ]);

    $cartShippingRate = CartShippingRate::factory()->create([
        'carrier'            => 'free',
        'carrier_title'      => 'Free shipping',
        'method'             => 'free_free',
        'method_title'       => 'Free Shipping',
        'method_description' => 'Free Shipping',
        'cart_address_id'    => $cartShippingAddress->id,
    ]);

    $o = Order::factory()->create([
        'cart_id'             => $cart->id,
        'customer_id'         => $k->id,
        'customer_email'      => $k->email,
        'customer_first_name' => $k->first_name,
        'customer_last_name'  => $k->last_name,
    ]);

    $orderItem = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id'   => $o->id,
        'sku'        => $product->sku,
        'type'       => $product->type,
        'name'       => $product->name,
    ]);

    $orderBillingAddress = OrderAddress::factory()->create([
        ...Arr::except($cartBillingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'order_id'     => $o->id,
    ]);

    $orderShippingAddress = OrderAddress::factory()->create([
        ...Arr::except($cartShippingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id'      => $cart->id,
        'customer_id'  => $k->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
        'order_id'     => $o->id,
    ]);

    $orderPayment = OrderPayment::factory()->create([
        'order_id' => $o->id,
    ]);

    $shipment = Shipment::factory()->create([
        'total_qty'             => rand(1, 10),
        'total_weight'          => rand(1, 10),
        'carrier_code'          => fake()->numerify('##########'),
        'carrier_title'         => fake()->word(),
        'track_number'          => fake()->numerify('##########'),
        'customer_id'           => $k->id,
        'customer_type'         => Customer::class,
        'order_id'              => $o->id,
        'order_address_id'      => $orderBillingAddress->id,
        'inventory_source_id'   => 1,
        'inventory_source_name' => 'Default',
    ]);

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.sales.shipments.view', $shipment->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.sales.shipments.view.title', ['shipment_id' => $shipment->id]))
        ->assertSeeText(trans('admin::app.account.edit.back-btn'));

    $cart->refresh();

    $cartItem->refresh();

    $cartBillingAddress->refresh();

    $cartShippingAddress->refresh();

    $orderBillingAddress->refresh();

    $orderShippingAddress->refresh();

    $o->refresh();

    $orderItem->refresh();

    $this->assertModelWise([
        Cart::class => [
            $this->prepareCart($cart),
        ],

        CartItem::class => [
            $this->prepareCartItem($cartItem),
        ],

        CartPayment::class => [
            $this->prepareCartPayment($cartPayment),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartBillingAddress),
        ],

        CartAddress::class => [
            $this->prepareAddress($cartShippingAddress),
        ],

        CartShippingRate::class => [
            $this->prepareCartShippingRate($cartShippingRate),
        ],

        CustomerAddress::class => [
            $this->prepareAddress($customerAddress),
        ],

        Order::class => [
            $this->prepareOrder($o),
        ],

        OrderItem::class => [
            $this->prepareOrderItem($orderItem),
        ],

        OrderAddress::class => [
            $this->prepareAddress($orderBillingAddress),

            $this->prepareAddress($orderShippingAddress),
        ],

        OrderPayment::class => [
            $this->prepareOrderPayment($orderPayment),
        ],

        Shipment::class => [
            [
                'total_qty'             => $shipment->total_qty,
                'total_weight'          => $shipment->total_weight,
                'carrier_code'          => $shipment->carrier_code,
                'carrier_title'         => $shipment->carrier_title,
                'track_number'          => $shipment->track_number,
                'customer_id'           => $shipment->customer_id,
                'customer_type'         => $shipment->customer_type,
                'order_id'              => $shipment->order_id,
                'order_address_id'      => $shipment->order_address_id,
                'inventory_source_id'   => $shipment->inventory_source_id,
                'inventory_source_name' => $shipment->inventory_source_name,
            ],
        ],
    ]);
});
