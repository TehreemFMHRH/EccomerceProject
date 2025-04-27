<?php

namespace Webkul\Shop\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Customer\Contracts\Customer;
use Webkul\Shop\Mail\Mailable;

class RegistrationNotification extends Mailable
{
    
    public function __construct(public Customer $k) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->customer->email),
            ],
            subject: trans('shop::app.emails.customers.registration.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.registration',
        );
    }
}
