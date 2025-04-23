<?php

return [
    [
        'key'    => 'sales.carriers.express_shipping',
        'name'   => 'admin::app.configuration.index.sales.shipping-methods.express-shipping.page-title',
        'info'   => 'admin::app.configuration.index.sales.shipping-methods.express-shipping.title-info',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'admin::app.admin.system.title',
                'type'          => 'depends',
                'depend'        => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'description',
                'title'         => 'admin::app.admin.system.description',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'default_rate',
                'title'         => 'admin::app.admin.system.rate',
                'type'          => 'depends',
                'depend'        => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'base_amount',
                'title'         => 'admin::app.admin.system.minimum-amount',
                'type'          => 'text',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'    => 'type',
                'title'   => 'admin::app.admin.system.type',
                'type'    => 'select',
                'options' => [
                    ['title' => 'Per Unit', 'value' => 'per_unit'],
                    ['title' => 'Per Order', 'value' => 'per_order'],
                ],
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'active',
                'title'         => 'admin::app.admin.system.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => false,
            ],
        ],
    ],
];
