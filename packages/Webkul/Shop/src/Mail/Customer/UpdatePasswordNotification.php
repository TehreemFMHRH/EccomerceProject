<?php

namespace Webkul\Shop\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Customer\Models\Customer;
use Webkul\Shop\Mail\Mailable;

class UpdatePasswordNotification extends Mailable
{
    
    public function __construct(public Customer $k) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->customer->email, $this->customer->name),
            ],
            subject: trans('shop::app.emails.customers.update-password.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.update-password',
        );
    }
}
