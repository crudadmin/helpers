<?php

namespace AdminHelpers\Notifications\Utilities;

use AdminHelpers\Notifications\Models\NotificationsToken;

trait HasUserNotifications
{
    public function notifications()
    {
        return $this->hasMany(notificationModel()::class);
    }

    public function notificationTokens()
    {
        return $this->morphMany(NotificationsToken::class, 'tokenable', 'table', 'row_id');
    }

    public function addDeviceToken($deviceId, $platform = null, $app = null)
    {
        if ( !$deviceId ){
            return;
        }

        $accessTokenId = app()->runningInConsole()
                            ? null
                            : client()?->currentAccessToken()?->getKey();

        // Do not add duplicite tokens
        if ( $token = $this->notificationTokens()->where('token', $deviceId)->first() ){
            //Update device token assignemt to auth session
            if ( $token->access_token_id != $accessTokenId ) {
                $token->update([ 'access_token_id' => $accessTokenId ]);
            }

            return;
        }

        $data = [
            'access_token_id' => $accessTokenId,
            'platform' => $platform ?: getPlatform(),
            'token' => $deviceId,
            'debug' => isTestEnvironment() ? 1 : 0,
        ];

        if ( hasAppsSupport() ) {
            $data['app'] = $app ?: $this->defaultNotificationApp;
        }

        $this->notificationTokens()->create($data);
    }

    public function getDefaultNotificationAppAttribute()
    {
        return (config('admin_helpers.notifications.apps')[0] ?? null);
    }

    public function createNotification($type, $data = [], $options = [])
    {
        $options['columns'][notificationModel()->getForeignColumn($this->getTable())] = $this->getKey();

        return createNotification($type, $data, $options);
    }

    public function setNotificationsAttribute($value)
    {
        $data = $this->notifications ?: [];

        foreach (array_wrap($value) as $key => $state) {
            $data[$key] = $state == 'true' || $state == 1 ? true : false;
        }

        $this->attributes['notifications'] = json_encode($data);
    }

    public function getNotificationsAttribute($value)
    {
        $notifications = json_decode($value ?: '[]', true);

        foreach (getNotificationsTypes() as $type) {
            if ( array_key_exists($type['key'], $notifications) == false ){
                $notifications[$type['key']] = true;
            }
        }

        return $notifications;
    }
}