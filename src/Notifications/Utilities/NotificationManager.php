<?php

namespace AdminHelpers\Notifications\Utilities;

use AdminHelpers\Notifications\Models\NotificationsRecipient;
use AdminHelpers\Notifications\Models\NotificationsToken;
use AdminHelpers\Notifications\Utilities\RecipientsDevices;
use Arr;
use Exception;
use Google\GuzzleClient;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Log;
use Throwable;

class NotificationManager
{
    use RecipientsDevices;

    private $cmd;
    private $firebase;
    private $start;

    protected $invalidTokens = [];
    protected $unknownTokens = [];

    protected $chunkSize = 500;
    protected $recipientsPivot = [];

    public function __construct($cmd)
    {
        $this->cmd = $cmd;

        $this->firebase = (new Factory)->withServiceAccount(base_path('credentials/firebase_credentials.json'));

        $this->start = now();

        $this->recipientsPivot = collect([]);
    }

    public function log($message)
    {
        if ( $this->cmd ){
            $this->cmd->info($message);
        }

        Log::channel('notification')->info($message);
    }

    public function error($message)
    {
        if ( $this->cmd ){
            $this->cmd->error($message);
        }

        Log::channel('notification')->error($message);
    }

    public function process()
    {
        $notifications = $this->getUnsentNotifications();

        $this->attachDeviceTokens($notifications);

        $this->log('Found '.$notifications->count().' notifications.');

        foreach ($notifications as $notification) {
            try {
                $recipients = $this->getNotificationRecipients($notification);

                $tokens = $recipients->pluck('tokens')->flatten()->toArray();

                $validDeviceTokens = $this->sendFCMMessage($notification, $tokens);

                // Save recipients for this notification.
                // So this notification may be viewed in notification center.
                if ( $notification->isPersistent ) {
                    $this->addRecipientsToSave($notification, $recipients, $validDeviceTokens);
                }
            } catch (Exception $e){
                $this->error('Notification ID #'.$notification->getKey().': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());

                Log::error($e);
            } catch (Throwable $e){
                $this->error('Notification ID #'.$notification->getKey().': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());

                Log::error($e);
            }
        }

        $this->attachSendRecipients();

        // Mark notifications as sent
        $this->markNotificationsAsSent($notifications);

        // Invalidate tokens which are not working anymore
        $this->invalidateTokens();
    }

    private function addRecipientsToSave($notification, $recipients, $validDeviceTokens)
    {
        $pivot = $recipients->map(function($row) use ($validDeviceTokens) {
            return array_merge($row['pivot'], [
                'state' => $this->getRecipientSendingState($row, $validDeviceTokens),
            ]);
        })->filter(fn($row) => $row['row_id'])->unique();

        $this->recipientsPivot = $this->recipientsPivot->merge($pivot);
    }

    private function attachSendRecipients()
    {
        $sentNotifications = $this->recipientsPivot->pluck('notification_id')->unique()->values();

        // Refresh previously sent notifications
        if ( count($sentNotifications) ) {
            $sentNotifications->chunk($this->chunkSize)->each(function($group){
                (new NotificationsRecipient)->whereIn('notification_id', $group->toArray())->delete();
            });
        }

        // Save recipients with chunks
        $this->recipientsPivot->chunk($this->chunkSize)->each(function($group){
            NotificationsRecipient::insert($group->toArray());
        });

    }

    private function markNotificationsAsSent($notifications)
    {
        $notifications->pluck('id')->chunk($this->chunkSize)->each(function($processedIds){
            notificationModel()
                ->whereIn('id', $processedIds)
                ->where('sent', 0)
                ->update([ 'sent' => 1 ]);
        });
    }

    /**
     * Invalidate users with wrong tokens
     */
    private function invalidateTokens()
    {
        if ( count($this->invalidTokens) ) {
            foreach (array_chunk($this->invalidTokens, $this->chunkSize) as $tokens) {
                NotificationsToken::whereIn('token', $tokens)->update([ 'state' => 'invalid' ]);
            }
        }

        if ( count($this->unknownTokens) ) {
            foreach (array_chunk($this->unknownTokens, $this->chunkSize) as $tokens) {
                NotificationsToken::whereIn('token', $tokens)->update([ 'state' => 'unknown' ]);
            }
        }
    }

    private function getUnsentNotifications()
    {
        $table = notificationModel()->getTable();

        $relatedKeys = array_keys(config('admin_helpers.notifications.relations'));

        $userColumns = implode(', ', array_map(function($column) use ($table) {
            return $table.'.'.$column;
        }, $relatedKeys));

        return notificationModel()
                ->selectRaw('
                    '.$table.'.id,
                    '.(hasAppsSupport() ? $table.'.app,' : '').'
                    '.$table.'.data,
                    '.$userColumns.',
                    '.$table.'.code
                ')
                ->where('sent', 0)
                ->where('notify_at', '<=', $this->start) //Process all notification which are scheduled already
                ->where('notify_at', '>=', now()->addHours(-12)) //Process only past 12 hours.
                ->orderBy('id', 'ASC')
                //TODO: add check for activity + week to send notifications.
                //Or other user settings to whitelist certain types of notifications.
                ->get();
    }

    private function sendFCMMessage($notification, $targets) : array
    {
        // If some tokens were invalidated already, skip them in upcoming notification
        $targets = $this->throwAwayInvalidTokens($targets);

        if ( count($targets) == 0 ){
            return [];
        }

        $messaging = $this->firebase->createMessaging();

        try {
            $data = [
                'title' => $notification->pushTitle ?: _('Nové hlásenie'),
                'body' => $notification->pushMessage,
                'image' => $notification->pushImage,
                'data' => ($notification->data ?: []) + [
                    'type' => $notification->type,
                ],
            ];

            $message = CloudMessage::new();

            // Set high priority
            if ( $notification->priority === true ) {
                $message = $this->setNotificationPriority($message);
            }

            $message = $message->withNotification($data)->withDefaultSounds()->withData($data['data'] ?? []);

            $report = $messaging->sendMulticast($message, $targets);

            //Merge invalid tokens to the buffer
            $this->invalidTokens = array_unique(array_merge($this->invalidTokens, $report->invalidTokens()));
            $this->unknownTokens = array_unique(array_merge($this->unknownTokens, $report->unknownTokens()));

            $this->log('Sending to '.count($targets).' devices. Success: '.$report->successes()->count().', errors: '.$report->failures()->count());

            return $report->validTokens();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        return [];
    }

    private function throwAwayInvalidTokens($targets)
    {
        return array_values(array_filter(array_wrap($targets), function($token){
            if ( in_array($token, $this->invalidTokens) ){
                return false;
            }

            if ( in_array($token, $this->unknownTokens) ){
                return false;
            }

            return true;
        }));
    }

    private function setNotificationPriority($message)
    {
        //Set hight priority for all platforms
        $message = $message->withHighestPossiblePriority();

        // Set highest priority for IOS with background support.
        $message = $message->withApnsConfig(ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    // Enables background processes
                    'content-available' => 1,
                ],
            ],
        ]));

        return $message;
    }
}
