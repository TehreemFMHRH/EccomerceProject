<?php

namespace Webkul\Shop\Mail\Customer\GDPR;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\GDPR\Contracts\GDPRDataRequest;

class NewRequestNotification extends Mailable
{
    
    public function __construct(public GDPRDataRequest $gdprRequest) {}

    
    public function envelope(): Envelope
    {
        $subjectKey = $this->gdprRequest->type === 'update'
            ? 'shop::app.emails.customers.gdpr.new-update-request'
            : 'shop::app.emails.customers.gdpr.new-delete-request';

        return new Envelope(
            to: [
                new Address($this->gdprRequest->email),
            ],
            subject: trans($subjectKey)
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.gdpr.new-request',
        );
    }
}
