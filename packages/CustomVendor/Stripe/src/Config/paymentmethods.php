<?php

return [
    'stripe' => [
        'code'            => 'stripe',
        'title'           => 'Stripe',
        'description'     => 'Stripe Payment Gateway',
        'class'           => 'CustomVendor\Stripe\Payment\Stripe',
        'active'          => true,
        'sort'            => 5,
        'sandbox'         => true,
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret_key'      => env('STRIPE_SECRET_KEY'),
    ],
];
