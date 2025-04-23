<?php

return [
    [
        'key'    => 'sales.payment_methods.stripe',
        'name'   => 'admin::app.configuration.index.sales.payment-methods.stripe',
        'info'   => 'admin::app.configuration.index.sales.payment-methods.stripe-info',
        'sort'   => 5,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.title',
                'type'          => 'text',
                'channel_based' => true,
                'locale_based'  => true,
                'validation'    => 'required_if:active,1',
                'depends'       => 'active:1',
            ], [
                'name'          => 'description',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.description',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'image',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.logo',
                'type'          => 'image',
                'info'          => 'admin::app.configuration.index.sales.payment-methods.logo-information',
                'channel_based' => false,
                'locale_based'  => false,
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp',
            ], [
                'name'          => 'published_key',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.published-key',
                'type'          => 'text',
            ], [
                'name'          => 'secret_key',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.secret-key',
                'type'          => 'text',
                'info'          => 'admin::app.configuration.index.sales.payment-methods.secret-key-info',
                'validation'    => 'required_if:active,1',
                'channel_based' => false,
                'locale_based'  => false,
            ], [
                'name'          => 'active',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.status',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'sandbox',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.sandbox',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'    => 'sort',
                'title'   => 'admin::app.configuration.index.sales.payment-methods.sort-order',
                'type'    => 'select',
                'options' => [
                    ['title' => '1', 'value' => 1],
                    ['title' => '2', 'value' => 2],
                    ['title' => '3', 'value' => 3],
                    ['title' => '4', 'value' => 4],
                    ['title' => '5', 'value' => 5],
                ],
            ],
        ],
    ],
];
