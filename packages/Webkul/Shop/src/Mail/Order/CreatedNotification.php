<?php

namespace Webkul\Shop\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Sales\Contracts\Order;
use Webkul\Shop\Mail\Mailable;

class CreatedNotification extends Mailable
{
    
    public function __construct(public Order $o) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->order->customer_email,
                    $this->order->customer_full_name
                ),
            ],
            subject: trans('shop::app.emails.orders.created.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.orders.created',
        );
    }
}
