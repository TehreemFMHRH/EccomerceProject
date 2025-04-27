<?php

namespace Webkul\Notification\Listeners;

use Webkul\Notification\Events\CreateOrderNotification;
use Webkul\Notification\Events\UpdateOrderNotification;
use Webkul\Notification\Repositories\NotificationRepository;

class Order
{
    
    public function __construct(protected NotificationRepository $notificationRepository) {}

    
    public function createOrder($o)
    {
        $this->notificationRepository->create(['type' => 'order', 'order_id' => $o->id]);

        event(new CreateOrderNotification);
    }

    
    public function updateOrder($o)
    {
        event(new UpdateOrderNotification([
            'id'     => $o->id,
            'status' => $o->status,
        ]));
    }
}
