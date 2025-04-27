<?php

namespace Webkul\Marketing\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Marketing\Contracts\Campaign;

class NewsletterMail extends Mailable
{
    
    public function __construct(
        public string $e,
        public Campaign $campaign
    ) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->email),
            ],
            subject: $this->campaign->subject,
        );
    }

    
    public function content(): Content
    {
        return new Content(
            htmlString: $this->campaign->email_template->content,
        );
    }
}
