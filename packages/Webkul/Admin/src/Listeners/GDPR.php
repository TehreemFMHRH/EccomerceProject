<?php

namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Mail\Customer\GDPR\NewRequestNotification;
use Webkul\Admin\Mail\Customer\GDPR\StatusUpdateNotification;

class GDPR extends Base
{
    
    public function afterGdprRequestCreated($gdprRequest)
    {
        try {
            Mail::queue(new NewRequestNotification($gdprRequest));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function afterGdprRequestUpdated($gdprRequest)
    {
        try {
            Mail::queue(new StatusUpdateNotification($gdprRequest));
        } catch (\Exception $e) {
            report($e);
        }
    }
}
