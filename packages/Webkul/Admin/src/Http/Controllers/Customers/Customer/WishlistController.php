<?php

namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\WishlistItemResource;
use Webkul\Customer\Repositories\WishlistRepository;

class WishlistController extends Controller
{
    
    public function __construct(protected WishlistRepository $wishlistRepository) {}

    
    public function items(int $i): JsonResource
    {
        $wishlistItems = $this->wishlistRepository
            ->with('product')
            ->where('customer_id', $i)
            ->get();

        return WishlistItemResource::collection($wishlistItems);
    }

    
    public function destroy(int $i): JsonResource
    {
        $this->validate(request(), [
            'item_id' => 'required|exists:wishlist_items,id',
        ]);

        $this->wishlistRepository->delete(request()->input('item_id'));

        return new JsonResource([
            'message' => trans('admin::app.customers.customers.view.wishlist.delete-success'),
        ]);
    }
}
