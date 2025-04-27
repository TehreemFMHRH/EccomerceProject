<?php

namespace Webkul\Shop\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Sales\Contracts\Invoice;
use Webkul\Shop\Mail\Mailable;

class InvoicedNotification extends Mailable
{
    
    public function __construct(public Invoice $invoice) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->invoice->order->customer_email,
                    $this->invoice->order->customer_full_name
                ),
            ],
            subject: trans('shop::app.emails.orders.invoiced.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.orders.invoiced',
        );
    }
}
