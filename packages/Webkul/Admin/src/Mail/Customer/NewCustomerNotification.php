<?php

namespace Webkul\Admin\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Customer\Contracts\Customer;

class NewCustomerNotification extends Mailable
{
    
    public function __construct(
        public Customer $k,
        public string $password
    ) {}

    
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
            view: 'shop::emails.customers.new-customer',
        );
    }
}
