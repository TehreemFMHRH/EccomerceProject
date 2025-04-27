<?php

namespace Webkul\Customer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Webkul\Customer\Models\Customer;

class CustomerUpdatePassword extends Mailable
{
    use Queueable, SerializesModels;

    
    public function __construct(public Customer $k) {}

    
    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            ->to($this->customer->email, $this->customer->name)
            ->subject(trans('shop::app.mail.update-password.subject'))
            ->view('shop::emails.customer.update-password', ['user' => $this->customer]);
    }
}
