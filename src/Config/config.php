<?php

return [
    'notifications' => [
        'enabled' => false,

        'model' => AdminHelpers\Contracts\Notifications\Models\AppNotification::class,

        'apps' => [],

        'platforms' => ['ios', 'android'],

        'relations' => [],

        'recipients_tables' => [],

        //Send notification with delay, to wait for other notifications...
        'push_notifications_delay' => 0,

        //Show notifications as unread for given minutage
        //after users read them, but not clicked on them.
        'unread_notifications_minutage' => 5,

        'whitelisted_tokens' => array_filter(explode(';', env('NOTIFICATIONS_TOKENS') ?: '')),
    ],
];