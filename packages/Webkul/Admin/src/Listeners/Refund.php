<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\RefundedNotification;
use Webkul\Paypal\Payment\SmartButton;

class Refund extends Base
{
    
    public function afterCreated($refund)
    {
        $this->refundOrder($refund);

        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_refund_mail_to_admin')) {
                return;
            }

            $this->prepareMail($refund, new RefundedNotification($refund));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function refundOrder($refund)
    {
        $o = $refund->order;

        if ($o->payment->method === 'paypal_smart_button') {
            /* getting smart button instance */
            $smartButton = new SmartButton;

            /* getting paypal oder id */
            $paypalOrderID = $o->payment->additional['orderID'];

            /* getting capture id by paypal order id */
            $captureID = $smartButton->getCaptureId($paypalOrderID);

            /* now refunding order on the basis of capture id and refund data */
            $smartButton->refundOrder($captureID, [
                'amount' => [
                    'value'         => round($refund->grand_total, 2),
                    'currency_code' => $refund->order_currency_code,
                ],
            ]);
        }
    }
}
