<?php

namespace Webkul\Shop\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Contracts\OrderComment;

class Base
{
    
    protected function getLocale($object)
    {
        if ($object instanceof OrderComment) {
            $object = $object->order;
        }

        $objectFirstItem = $object->items->first();

        return $objectFirstItem->additional['locale'] ?? 'en';
    }

    
    protected function prepareMail($entity, $notification)
    {
        $customerLocale = $this->getLocale($entity);

        $previousLocale = core()->getCurrentLocale()->code;

        app()->setLocale($customerLocale);

        try {
            Mail::queue($notification);
        } catch (\Exception $e) {
            \Log::error('Error in Sending Email'.$e->getMessage());
        }

        app()->setLocale($previousLocale);
    }
}
