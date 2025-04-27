<?php

return [

    
    'connection' => 'default',

    
    'connections' => [
        'default' => [
            'hosts' => [
                env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
            ],

            'user'   => env('ELASTICSEARCH_USER', null),
            'pass'   => env('ELASTICSEARCH_PASS', null),
        ],

        
        'api' => [
            'hosts' => [
                env('ELASTICSEARCH_HOST', null),
            ],

            'key'   => env('ELASTICSEARCH_API_KEY', null),
        ],

        
        'cloud' => [
            'id'      => env('ELASTICSEARCH_CLOUD_ID', null),

            
            'api_key' => env('ELASTICSEARCH_API_KEY', null),

            
            'user'    => env('ELASTICSEARCH_USER', null),
            'pass'    => env('ELASTICSEARCH_PASS', null),
        ],
    ],

    
    'caBundle' => null,

    
    'retries' => null,
];
