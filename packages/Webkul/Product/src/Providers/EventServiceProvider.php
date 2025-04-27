<?php

namespace Webkul\Product\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        'catalog.product.create.after'  => [
            'Webkul\Product\Listeners\ProductListener@afterCreate',
        ],
        'catalog.product.update.after'  => [
            'Webkul\Product\Listeners\ProductListener@afterUpdate',
        ],
        'catalog.product.delete.before' => [
            'Webkul\Product\Listeners\ProductListener@beforeDelete',
        ],
        'checkout.order.save.after'     => [
            'Webkul\Product\Listeners\Order@afterCancelOrCreate',
        ],
        'sales.order.cancel.after'      => [
            'Webkul\Product\Listeners\Order@afterCancelOrCreate',
        ],
        'sales.refund.save.after'       => [
            'Webkul\Product\Listeners\Refund@afterCreate',
        ],
    ];
}
