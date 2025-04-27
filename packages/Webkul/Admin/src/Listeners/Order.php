<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\CanceledNotification;
use Webkul\Admin\Mail\Order\CreatedNotification;
use Webkul\Sales\Contracts\Order as OrderContract;

class Order extends Base
{
    
    public function afterCreated(OrderContract $o)
    {
        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_order_mail_to_admin')) {
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
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.cancel_order_mail_to_admin')) {
                return;
            }

            $this->prepareMail($o, new CanceledNotification($o));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
