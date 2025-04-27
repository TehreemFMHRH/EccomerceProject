<?php

namespace Webkul\Notification\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateOrderNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(protected $dat) {}


    public function broadcastOn()
    {
        return new Channel('notification');
    }


    public function broadcastWith()
    {
        return $this->data;
    }


    public function broadcastQueue()
    {
        return 'broadcastable';
    }


    public function broadcastAs()
    {
        return 'update-notification';
    }
}
