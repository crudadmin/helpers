<?php

namespace AdminHelpers\Sms;

use Illuminate\Notifications\Notification;

class SmartSmsChannel
{
    /**
     * Send the given notification as an SMS to the phone of the notifiable.
     *
     * @param  mixed  $notifiable
     * @param  Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = method_exists($notification, 'toSms')
            ? $notification->toSms($notifiable)
            : $notification->getMessage($notifiable);

        $phone = method_exists($notifiable, 'routeNotificationFor')
            ? ($notifiable->routeNotificationFor('sms', $notification) ?: $notifiable->phone)
            : $notifiable->phone;

        (new SmartSms)->sendSMS($phone, $message);
    }
}
