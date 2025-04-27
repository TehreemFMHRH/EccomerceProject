<?php

namespace Webkul\Shop\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Sales\Contracts\Refund;
use Webkul\Shop\Mail\Mailable;

class RefundedNotification extends Mailable
{
    
    public function __construct(public Refund $refund) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->refund->order->customer_email,
                    $this->refund->order->customer_full_name
                ),
            ],
            subject: trans('shop::app.emails.orders.refunded.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.orders.refunded',
        );
    }
}
