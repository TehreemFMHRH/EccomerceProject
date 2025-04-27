<?php

namespace Webkul\Shop\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Shop\Mail\Customer\EmailVerificationNotification;
use Webkul\Shop\Mail\Customer\NoteNotification;
use Webkul\Shop\Mail\Customer\RegistrationNotification;
use Webkul\Shop\Mail\Customer\SubscriptionNotification;
use Webkul\Shop\Mail\Customer\UpdatePasswordNotification;

class Customer extends Base
{
    
    public function afterCreated($k)
    {
        if (core()->getConfigData('emails.general.notifications.emails.general.notifications.verification')) {
            try {
                if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.verification')) {
                    return;
                }

                Mail::queue(new EmailVerificationNotification($k));
            } catch (\Exception $e) {
                \Log::info('EmailVerificationNotification Error');

                report($e);
            }

            return;
        }

        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                return;
            }

            Mail::queue(new RegistrationNotification($k));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function afterPasswordUpdated($k)
    {
        try {
            Mail::queue(new UpdatePasswordNotification($k));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function afterSubscribed($k)
    {
        try {
            Mail::queue(new SubscriptionNotification($k));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function afterNoteCreated($note)
    {
        if (! $note->customer_notified) {
            return;
        }

        try {
            Mail::queue(new NoteNotification($note));
        } catch (\Exception $e) {
            session()->flash('warning', $e->getMessage());
        }
    }
}
