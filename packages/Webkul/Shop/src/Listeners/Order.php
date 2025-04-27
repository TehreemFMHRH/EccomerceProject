<?php

namespace Webkul\Shop\Listeners;

use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Shop\Mail\Order\CanceledNotification;
use Webkul\Shop\Mail\Order\CommentedNotification;
use Webkul\Shop\Mail\Order\CreatedNotification;

class Order extends Base
{
    
    public function afterCreated(OrderContract $o)
    {
        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_order')) {
                return;
            }

            $this->prepareMail($o, new CreatedNotification($o));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function afterCanceled($o)
    {
        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.cancel_order')) {
                return;
            }

            $this->prepareMail($o, new CanceledNotification($o));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function afterCommented($comment)
    {
        if (! $comment->customer_notified) {
            return;
        }

        try {
            
            $this->prepareMail($comment, new CommentedNotification($comment));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
