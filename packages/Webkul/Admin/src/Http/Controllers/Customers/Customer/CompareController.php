<?php

namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\CompareItemResource;
use Webkul\Customer\Repositories\CompareItemRepository;

class CompareController extends Controller
{
    
    public function __construct(protected CompareItemRepository $compareItemRepository) {}

    
    public function items(int $i): JsonResource
    {
        $compareItems = $this->compareItemRepository
            ->with('product')
            ->where('customer_id', $i)
            ->get();

        return CompareItemResource::collection($compareItems);
    }

    
    public function destroy(int $i): JsonResource
    {
        $this->validate(request(), [
            'item_id' => 'required|exists:compare_items,id',
        ]);

        $this->compareItemRepository->delete(request()->input('item_id'));

        return new JsonResource([
            'message' => trans('admin::app.customers.customers.view.compare.delete-success'),
        ]);
    }
}
