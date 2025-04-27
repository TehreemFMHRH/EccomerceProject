<?php

namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\OrderItemResource;
use Webkul\Sales\Repositories\OrderItemRepository;

class OrderController extends Controller
{
    
    public function __construct(protected OrderItemRepository $orderItemRepository) {}

    
    public function recentItems(int $i): JsonResource
    {
        $orderItems = $this->orderItemRepository
            ->distinct('order_items.product_id')
            ->leftJoin('orders', 'order_items.order_id', 'orders.id')
            ->whereNull('order_items.parent_id')
            ->where('orders.customer_id', $i)
            ->orderBy('orders.created_at', 'desc')
            ->limit(5)
            ->get();

        return OrderItemResource::collection($orderItems);
    }
}
