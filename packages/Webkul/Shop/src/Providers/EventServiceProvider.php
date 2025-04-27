<?php

namespace Webkul\Shop\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Shop\Listeners\Customer;
use Webkul\Shop\Listeners\GDPR;
use Webkul\Shop\Listeners\Invoice;
use Webkul\Shop\Listeners\Order;
use Webkul\Shop\Listeners\Refund;
use Webkul\Shop\Listeners\Shipment;

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        
        'customer.registration.after' => [
            [Customer::class, 'afterCreated'],
        ],

        'customer.password.update.after' => [
            [Customer::class, 'afterPasswordUpdated'],
        ],

        'customer.subscription.after' => [
            [Customer::class, 'afterSubscribed'],
        ],

        'customer.note.create.after' => [
            [Customer::class, 'afterNoteCreated'],
        ],

        
        'customer.account.gdpr-request.create.after' => [
            [GDPR::class, 'afterGdprRequestCreated'],
        ],

        'customer.account.gdpr-request.update.after' => [
            [GDPR::class, 'afterGdprRequestUpdated'],
        ],

        
        'checkout.order.save.after' => [
            [Order::class, 'afterCreated'],
        ],

        'sales.order.cancel.after' => [
            [Order::class, 'afterCanceled'],
        ],

        'sales.order.comment.create.after' => [
            [Order::class, 'afterCommented'],
        ],

        'sales.invoice.save.after' => [
            [Invoice::class, 'afterCreated'],
        ],

        'sales.invoice.send_duplicate_email' => [
            [Invoice::class, 'afterCreated'],
        ],

        'sales.shipment.save.after' => [
            [Shipment::class, 'afterCreated'],
        ],

        'sales.refund.save.after' => [
            [Refund::class, 'afterCreated'],
        ],
    ];
}
