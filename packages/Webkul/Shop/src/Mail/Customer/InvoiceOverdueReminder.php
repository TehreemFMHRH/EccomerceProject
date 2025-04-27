<?php

namespace Webkul\Shop\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\Invoice;

class InvoiceOverdueReminder extends Mailable
{
    
    public function __construct(public Invoice $invoice) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    core()->getSenderEmailDetails()['email'],
                    core()->getSenderEmailDetails()['name']
                ),
            ],
            subject: trans('shop::app.emails.customers.reminder.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.customers.invoice-reminder',
        );
    }
}
