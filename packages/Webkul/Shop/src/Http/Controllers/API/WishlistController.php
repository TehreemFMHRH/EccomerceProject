<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Repositories\WishlistRepository;
use Webkul\Product\Models\Product;
use Webkul\Shop\Http\Resources\CartResource;
use Webkul\Shop\Http\Resources\WishlistResource;

class WishlistController extends APIController
{
    
    public function __construct(
        protected WishlistRepository $wishlistRepository,

    ) {}

    
    public function index(): JsonResource
    {
        $this->removeInactiveItems();

        $items = $this->wishlistRepository
            ->where([
                'channel_id'  => core()->getCurrentChannel()->id,
                'customer_id' => auth()->guard('customer')->user()->id,
            ])
            ->get();

        return WishlistResource::collection($items);
    }

    
    public function store(): JsonResource
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $product = Product::find(request()->input('product_id'));

        if (! $product) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.product-removed'),
            ]);
        }

        $dat = [
            'channel_id'  => core()->getCurrentChannel()->id,
            'product_id'  => $product->id,
            'customer_id' => auth()->guard()->user()->id,
        ];

        if (! $this->wishlistRepository->findOneWhere($dat)) {
            $this->wishlistRepository->create($dat);

            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.success'),
            ]);
        }

        $this->wishlistRepository->deleteWhere([
            'product_id'  => $product->id,
            'customer_id' => auth()->guard()->user()->id,
        ]);

        return new JsonResource([
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    
    public function moveToCart($i): JsonResource
    {
        $wishlistItem = $this->wishlistRepository->findOneWhere([
            'id'          => $i,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        if (! $wishlistItem) {
            abort(404);
        }

        try {
            $result = Cart::moveToCart($wishlistItem, request()->input('quantity'));

            if ($result) {
                return new JsonResource([
                    'data' => [
                        'wishlist' => WishlistResource::collection($this->wishlistRepository->get()),
                        'cart'     => new CartResource(Cart::getCart()),
                    ],

                    'message'  => trans('shop::app.customers.account.wishlist.moved-success'),
                ]);
            }

            return new JsonResource([
                'redirect' => true,
                'data'     => route('shop.product_or_category.index', $wishlistItem->product->url_key),
                'message'  => trans('shop::app.checkout.cart.missing-options'),
            ]);

        } catch (\Exception $exception) {
            return new JsonResource([
                'redirect' => true,
                'data'     => route('shop.product_or_category.index', $wishlistItem->product->url_key),
                'message'  => $exception->getMessage(),
            ]);
        }
    }

    
    public function destroy($i): JsonResource
    {
        $success = $this->wishlistRepository->deleteWhere([
            'id'          => $i,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        if (! $success) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.remove-fail'),
            ]);
        }

        return new JsonResource([
            'data'    => WishlistResource::collection($this->wishlistRepository->get()),
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    
    public function destroyAll(): JsonResource
    {
        $success = $this->wishlistRepository->deleteWhere([
            'customer_id'  => auth()->guard('customer')->user()->id,
        ]);

        if (! $success) {
            return new JsonResource([
                'message'  => trans('shop::app.customers.account.wishlist.remove-fail'),
            ]);
        }

        return new JsonResource([
            'message'  => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    
    protected function removeInactiveItems()
    {
        $k = auth()->guard('customer')->user();

        $k->load(['wishlist_items.product']);

        $inactiveItemIds = $k->wishlist_items
            ->filter(fn ($item) => ! $item->product->status)
            ->pluck('product_id')
            ->toArray();

        return $k->wishlist_items()
            ->whereIn('product_id', $inactiveItemIds)
            ->delete();
    }
}
