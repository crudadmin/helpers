<?php

use AdminHelpers\Notifications\Models\NotificationsToken;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

function notificationModel()
{
    return Admin::getModelByTable(
        (new (config()->get('admin_helpers.notifications.model')))->getTable()
    );
}

function getNotificationIdentifier($identifier)
{
    if ( $identifier instanceof AdminModel ){
        $parts = explode('_', $identifier->getTable());
        $parts = array_map(fn($p) => substr($p, 0, 2), $parts);

        return implode('_', $parts).'_'.$identifier->getKey();
    }

    return $identifier;
}

function createNotification($type, $data = [], $options = [])
{
    $model = notificationModel();

    //Recipients columns
    $columns = $options['columns'] ?? [];

    $code = $model->getByName($type)['code'] ?? 0;

    $identifier = getNotificationIdentifier($options['identifier'] ?? null);

    $payloadData = $options['data'] ?? $data;

    $notifyAt = $options['notifyAt'] ?? now()->addMinutes(
        isset($options['delay']) ? $options['delay'] : config('admin_helpers.notifications.push_notifications_delay')
    );

    /*
     * First we check if same type of notifications does exists, and then update previous one.
     * This is better use-case, because some events may have hundreds of users and we sent notification.
     * Thanks to this, we don't neet to create new ones. But push old one to the front.
     */
    if ( $identifier && $notification = $model->where($columns)->whereCode($code)->whereIdentifier($identifier)->first() ) {
        $data = [
            'sent' => 0,
            'created_at' => now(),
            ...$columns,
        ];

        //Update notifications data
        if ( $notification->data != $payloadData ){
            $data['data'] = $payloadData;
        }

        //Use throttle, not debounce method. So rewrite only past notify dates.
        //But not scheduled
        if ( $notification->notify_at < now() ) {
            $data['notify_at'] = $notifyAt;
        }

        $notification->update($data);
    } else {
        $notification = $model->create([
            'app' => $options['app'] ?? (config('admin_helpers.notifications.apps')[0] ?? null),
            'code' => $code,
            'identifier' => $identifier,
            'data' => $payloadData,
            'notify_at' => $notifyAt,
            'created_at' => now(),
            ...$columns,
        ]);
    }
}

function getRecipientTables()
{
    return array_values(array_filter(config('admin_helpers.notifications.relations')));
}

function notificationsTokensTab()
{
    return Group::tab(NotificationsToken::class)->where(function($query, $parent){
        $query->where('table', $parent->getTable())->where('row_id', $parent->getKey());
    });
}