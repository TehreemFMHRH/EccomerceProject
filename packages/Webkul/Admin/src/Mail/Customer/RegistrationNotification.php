<?php

namespace Webkul\Admin\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Customer\Contracts\Customer;

class RegistrationNotification extends Mailable
{
    
    public function __construct(public Customer $k) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    core()->getAdminEmailDetails()['email'],
                    core()->getAdminEmailDetails()['name']
                ),
            ],
            subject: trans('admin::app.emails.customers.registration.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'admin::emails.customers.registration',
        );
    }
}
