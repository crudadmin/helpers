<?php

return [
    'notifications' => [
        'enabled' => false,

        'model' => AdminHelpers\Notifications\Models\AppNotification::class,

        'apps' => [],

        'platforms' => ['ios', 'android'],

        //Pass table name only when table has device tokens assigned with relation
        'relations' => [
            // 'column_id' => 'table_name' //or null
        ],

        //Send notification with delay, to wait for other notifications...
        'push_notifications_delay' => 0,

        //Show notifications as unread for given minutage
        //after users read them, but not clicked on them.
        'unread_notifications_minutage' => 5,

        'whitelisted_tokens' => array_filter(explode(';', env('NOTIFICATIONS_TOKENS') ?: '')),
    ],

    'auth' => [
        'oauth' => [
            // Registered Oauth app ids
            'apps' => [
                // 'appid_key' => [ 'name' => 'App name' ]
            ],
        ],

        'throttle' => [
            'auth' => 15,
            'otp' => 5,
        ],

        'otp' => [
            'enabled' => false,
            'debug' => env('AUTH_TOKEN_DEBUG', false),

            //Verification test numbers
            'test_identifiers' => [
                // '+421900000000',
            ],

            'whitelisted_tokens' => [
                // 12345
            ],
        ]
    ]
];