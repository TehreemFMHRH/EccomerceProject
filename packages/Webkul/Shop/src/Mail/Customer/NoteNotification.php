<?php

namespace Webkul\Shop\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Customer\Models\CustomerNote;
use Webkul\Shop\Mail\Mailable;

class NoteNotification extends Mailable
{
    
    public function __construct(public CustomerNote $customerNote) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->customerNote->customer->email),
            ],
            subject: trans('shop::app.emails.orders.commented.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.commented',
        );
    }
}
