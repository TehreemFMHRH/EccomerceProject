<?php

namespace Webkul\Shop\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Core\Contracts\SubscribersList;
use Webkul\Shop\Mail\Mailable;

class SubscriptionNotification extends Mailable
{
    
    public function __construct(public SubscribersList $subscribersList) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->subscribersList->email),
            ],
            subject: trans('shop::app.emails.customers.subscribed.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.subscribed',
            with: [
                'fullName' => trim($this->subscribersList->first_name.' '.$this->subscribersList->last_name),
            ],
        );
    }
}
