<?php

namespace Webkul\Admin\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\Order;

class CanceledNotification extends Mailable
{
    
    public function __construct(public Order $o) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    core()->getAdminEmailDetails()['email'],
                    core()->getAdminEmailDetails()['name']
                ),
            ],
            subject: trans('admin::app.emails.orders.canceled.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'admin::emails.orders.canceled',
        );
    }
}
