<?php

namespace Webkul\Notification\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateOrderNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    
    public function broadcastOn()
    {
        return new Channel('notification');
    }

    
    public function broadcastQueue()
    {
        return 'broadcastable';
    }

    
    public function broadcastAs()
    {
        return 'create-notification';
    }
}
