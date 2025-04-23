<?php

return [
    'express_shipping' => [
        'code'         => 'express_shipping',
        'title'        => 'ExpressShipping',
        'description'  => 'ExpressShipping',
        'active'       => true,
        'default_rate' => '10',
        'type'         => 'per_unit',
        'class'        => 'Webkul\ExpressShipping\Carriers\ExpressShipping',
    ]
];
