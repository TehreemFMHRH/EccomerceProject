<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\CartRule\Repositories\CartRuleCouponRepository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Product\Models\Product;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;
use Webkul\Shop\Http\Resources\ProductResource;

class CartController extends APIController
{
    
    public function __construct(

        protected CartRuleCouponRepository $cartRuleCouponRepository
    ) {}

    
    public function index(): JsonResource
    {
        Cart::collectTotals();

        $resp = [
            'data' => ($cart = Cart::getCart()) ? new CartResource($cart) : null,
        ];

        if (session()->has('info')) {
            $resp['message'] = session()->get('info');
        }

        return new JsonResource($resp);
    }

    
    public function store()
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $product = Product::with('parent')->findOrFail(request()->input('product_id'));

        try {
            if (! $product->status) {
                throw new \Exception(trans('shop::app.checkout.cart.inactive-add'));
            }

            $resp = [];

            if (request()->get('is_buy_now')) {
                Cart::deActivateCart();

                $resp['redirect'] = route('shop.checkout.onepage.index');
            }

            $cart = Cart::addProduct($product, request()->all());

            return new JsonResource(array_merge([
                'data'    => new CartResource($cart),
                'message' => trans('shop::app.checkout.cart.item-add-to-cart'),
            ], $resp));
        } catch (\Exception $exception) {
            return response()->json([
                'redirect_uri' => route('shop.product_or_category.index', $product->url_key),
                'message'      => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    
    public function destroy(): JsonResource
    {
        $this->validate(request(), [
            'cart_item_id' => 'required|exists:cart_items,id',
        ]);

        Cart::removeItem(request()->input('cart_item_id'));

        Cart::collectTotals();

        return new JsonResource([
            'data'    => new CartResource(Cart::getCart()),
            'message' => trans('shop::app.checkout.cart.success-remove'),
        ]);
    }

    
    public function destroySelected(): JsonResource
    {
        foreach (request()->input('ids') as $i) {
            Cart::removeItem($i);
        }

        return new JsonResource([
            'data'     => new CartResource(Cart::getCart()) ?? null,
            'message'  => trans('shop::app.checkout.cart.index.remove-selected-success'),
        ]);
    }

    
    public function moveToWishlist(): JsonResource
    {
        foreach (request()->input('ids') as $index => $i) {
            $qty = request()->input('qty')[$index];

            Cart::moveToWishlist($i, $qty);
        }

        return new JsonResource([
            'data'     => new CartResource(Cart::getCart()) ?? null,
            'message'  => trans('shop::app.checkout.cart.index.move-to-wishlist-success'),
        ]);
    }

    
    public function update(): JsonResource
    {
        try {
            Cart::updateItems(request()->input());

            return new JsonResource([
                'data'    => new CartResource(Cart::getCart()),
                'message' => trans('shop::app.checkout.cart.index.quantity-update'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResource([
                'message' => $exception->getMessage(),
            ]);
        }
    }

    
    public function estimateShippingMethods(): JsonResource
    {
        $this->validate(request(), [
            'country'         => 'required',
            'state'           => 'required',
            'postcode'        => 'required',
            'shipping_method' => 'sometimes|required',
        ]);

        $cart = Cart::getCart();

        $addr = (new CartAddress)->fill([
            'country'  => request()->input('country'),
            'state'    => request()->input('state'),
            'postcode' => request()->input('postcode'),
            'cart_id'  => $cart->id,
        ]);

        $cart->setRelation('billing_address', $addr);

        $cart->setRelation('shipping_address', $addr);

        Cart::setCart($cart);

        if (request()->has('shipping_method')) {
            Cart::saveShippingMethod(request()->input('shipping_method'));
        }

        Cart::collectTotals();

        $cartResource = (new CartResource(Cart::getCart()))->jsonSerialize();

        return new JsonResource([
            'data'     => [
                'cart'             => $cartResource,
                'shipping_methods' => array_values(Shipping::collectRates()['shippingMethods']),
            ],
        ]);
    }

    
    public function storeCoupon()
    {
        $validatedData = $this->validate(request(), [
            'code' => 'required',
        ]);

        try {
            if (strlen($validatedData['code'])) {
                $coupon = $this->cartRuleCouponRepository->findOneByField('code', $validatedData['code']);

                if (! $coupon) {
                    return (new JsonResource([
                        'data'     => new CartResource(Cart::getCart()),
                        'message'  => trans('Coupon not found.'),
                    ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                if ($coupon->cart_rule->status) {
                    if (Cart::getCart()->coupon_code == $validatedData['code']) {
                        return (new JsonResource([
                            'data'     => new CartResource(Cart::getCart()),
                            'message'  => trans('shop::app.checkout.coupon.already-applied'),
                        ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    Cart::setCouponCode($validatedData['code'])->collectTotals();

                    if (Cart::getCart()->coupon_code == $validatedData['code']) {
                        return new JsonResource([
                            'data'     => new CartResource(Cart::getCart()),
                            'message'  => trans('shop::app.checkout.coupon.success-apply'),
                        ]);
                    }
                }

                return (new JsonResource([
                    'data'     => new CartResource(Cart::getCart()),
                    'message'  => trans('Coupon not found.'),
                ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (\Exception $e) {
            return (new JsonResource([
                'data'    => new CartResource(Cart::getCart()),
                'message' => trans('shop::app.checkout.coupon.error'),
            ]))->response()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function destroyCoupon(): JsonResource
    {
        Cart::removeCouponCode()->collectTotals();

        return new JsonResource([
            'data'     => new CartResource(Cart::getCart()),
            'message'  => trans('shop::app.checkout.coupon.remove'),
        ]);
    }

    
    public function crossSellProducts()
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return new JsonResource([
                'data' => [],
            ]);
        }

        $productIds = $cart->items->pluck('product_id')->toArray();

        $products = Product
            ->select('products.*', 'product_cross_sells.child_id')
            ->join('product_cross_sells', 'products.id', '=', 'product_cross_sells.child_id')
            ->whereIn('product_cross_sells.parent_id', $productIds)
            ->groupBy('product_cross_sells.child_id')
            ->take(core()->getConfigData('catalog.products.cart_view_page.no_of_cross_sells_products'))
            ->get();

        return ProductResource::collection($products);
    }
}
