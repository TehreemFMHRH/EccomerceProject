<?php

namespace Webkul\Admin\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\OrderComment;

class CommentedNotification extends Mailable
{
    
    public function __construct(public OrderComment $comment) {}

    
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(
                    $this->comment->order->customer_email,
                    $this->comment->order->customer_full_name
                ),
            ],
            subject: trans('admin::app.emails.orders.commented.subject'),
        );
    }

    
    public function content(): Content
    {
        return new Content(
            view: 'admin::emails.orders.commented',
        );
    }
}
