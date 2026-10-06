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

        //Automatic cleanup of old notifications.
        'cleanup' => [
            //Delete notifications older than given number of months.
            'older_than_months' => 3,

            //Time when the daily cleanup schedule runs (24h format).
            'schedule_at' => '02:00',

            //Automatic cleanup of old device tokens.
            'tokens' => [
                //Delete invalid/unknown device tokens older than given number of months.
                'dead_older_than_months' => 1,

                //How many working tokens may be assigned to one recipient.
                'max_per_recipient' => 10,

                //How many minutes after the notifications cleanup the tokens cleanup runs.
                'schedule_offset_minutes' => 15,
            ],
        ],

        'whitelisted_tokens' => array_filter(explode(';', env('NOTIFICATIONS_TOKENS') ?: '')),
    ],

    // Paths of the client app, its build version is read from the bundle
    'bundle' => [
        'nuxt_path' => env('NUXT_PATH'),
        'ionic_path' => env('IONIC_PATH'),
    ],

    // Bootstrap request of the client application (BootstrapResolver, bootstrapRequest())
    'bootstrap' => [
        // Default AppRequest class of the project, the helpers BootstrapRequest when empty
        'class' => null,

        // Header with the application type, null disables the selection
        'header' => 'app-type',

        // Application type => AppRequest class, usually extending the default class.
        // Unknown types use the default class.
        'app_types' => [
            // 'courier' => App\Utilities\Bootstrap\CourierAppRequest::class,
        ],
    ],

    // SmartSms credentials, config/smartsms.php of the project wins
    'smartsms' => [
        'from' => env('SMARTSMS_FROM', true),
        'test' => env('SMARTSMS_TEST', true),
        'username' => env('SMARTSMS_USERNAME'),
        'password' => env('SMARTSMS_PASSWORD_MD5') ?: md5((string) env('SMARTSMS_PASSWORD')),
    ],

    'auth' => [
        // Default verificator, email or phone
        'verificator' => env('AUTH_VERIFICATOR', 'email'),

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
            'length' => [ 'chars' => 2, 'numbers' => 3 ],

            //Verification test numbers
            'test_identifiers' => [
                // '+421900000000',
            ],

            'whitelisted_tokens' => [
                // 12345
            ],
        ]
    ],

    'importer' => [
        'enabled' => false,

        // Available imports
        'imports' => [
            // [
            //     'name' => 'Products import',
            //     'class' => App\Utilities\Import\ProductsImport::class,
            //     'extensions' => ['csv', 'xls', 'xlsx'],
            //     'autoimport' => false,
            // ],
        ],

        // Extensions allowed for every import, next to the extensions of each import
        'extensions' => [],
    ],
];