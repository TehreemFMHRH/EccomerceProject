<?php

namespace Webkul\Marketing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    
    protected $listen = [
        
        'catalog.product.update.before'  => [
            'Webkul\Marketing\Listeners\ProductListener@beforeUpdate',
        ],

        'catalog.product.delete.before' => [
            'Webkul\Marketing\Listeners\ProductListener@beforeDelete',
        ],

        
        'catalog.category.create.after' => [
            'Webkul\Marketing\Listeners\Category@afterCreate',
        ],

        'catalog.category.update.before' => [
            'Webkul\Marketing\Listeners\Category@beforeUpdate',
        ],

        'catalog.category.delete.before' => [
            'Webkul\Marketing\Listeners\Category@beforeDelete',
        ],

        
        'cms.page.create.after' => [
            'Webkul\Marketing\Listeners\Page@afterCreate',
        ],

        'cms.page.update.before' => [
            'Webkul\Marketing\Listeners\Page@beforeUpdate',
        ],

        'cms.page.delete.before' => [
            'Webkul\Marketing\Listeners\Page@beforeDelete',
        ],
    ];
}
