<?php

namespace Webkul\Shop\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactUs extends Mailable
{
    
    public function __construct(public $contactUs) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    core()->getAdminEmailDetails()['email'],
                    core()->getAdminEmailDetails()['name']
                ),
            ],
            subject: trans('shop::app.emails.contact-us.inquiry-from').' '.$this->contactUs['name'].' '.trans('shop::app.emails.contact-us.contact-from'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.contact-us',
        );
    }
}
