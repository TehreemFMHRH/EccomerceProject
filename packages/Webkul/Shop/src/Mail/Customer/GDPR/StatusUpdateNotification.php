<?php

namespace Webkul\Shop\Mail\Customer\GDPR;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\GDPR\Contracts\GDPRDataRequest;

class StatusUpdateNotification extends Mailable
{
    
    public function __construct(public GDPRDataRequest $gdprRequest) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->gdprRequest->email),
            ],
            subject: trans('shop::app.emails.customers.gdpr.status-update.subject')
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.gdpr.status-update-notification',
        );
    }
}
