<?php

namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Mail\Customer\RegistrationNotification;

class Customer extends Base
{
    
    public function afterCreated($k)
    {
        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.customer_registration_confirmation_mail_to_admin')) {
                return;
            }

            Mail::queue(new RegistrationNotification($k));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
