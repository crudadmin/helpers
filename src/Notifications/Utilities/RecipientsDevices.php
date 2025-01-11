<?php

namespace AdminHelpers\Notifications\Utilities;

use AdminHelpers\Notifications\Models\NotificationsToken;

trait RecipientsDevices
{
    protected function attachDeviceTokens($notifications)
    {
        $tree = [];

        foreach ($notifications as $notification) {
            $this->attachRelatedDevices($tree, $notification);

            $this->attachDynamicDevices($tree, $notification);
        }

        if ( count($tree) == 0 ) {
            return collect();
        }

        $tokens = $this->fetchTokens($tree)->groupBy(function($token){
            return $token->app.':'.$token->table.':'.$token->row_id;
        });

        foreach ($notifications as $notification) {
            $pivots = [];

            foreach ($notification->recipientsKeys as $treeKey) {
                $recipientTokens = ($tokens[$treeKey] ?? collect())->pluck('token')->toArray();

                $pivots[] = array_merge($tree[$treeKey], [
                    'tokens' => $recipientTokens,
                ]);
            }

            $notification->recipientsPivot = $pivots;
        }

        return $notifications;
    }

    private function fetchTokens($tree)
    {
        $tokens = collect();

        foreach (array_chunk($tree, $this->chunkSize) as $filterGroups) {
            $groupTokens = (new NotificationsToken)
                    ->where('state', 'ok')
                    ->where(function($query) use ($filterGroups) {
                        foreach ($filterGroups as $where) {
                            $query->orWhere(function($query) use ($where) {
                                $query->where($where);
                            });
                        }
                    })->get();

            $tokens = $groupTokens->merge($tokens);
        }

        return $tokens;
    }

    private function attachRelatedDevices(&$tree, $notification)
    {
        $app = $notification->app;
        $columns = array_filter(config('admin_helpers.notifications.relations'));

        // Attach devices assigned directly to the notification with relation keys
        foreach ($columns as $key => $table) {
            if ( $id = $notification->{$key} ) {
                $this->attachTreeNotification($tree, $app, $table, $id, $notification);
            }
        }
    }

    private function attachDynamicDevices(&$tree, $notification)
    {
        $app = $notification->app;

        //Recipients from notification channel stream
        if ( ($settings = $notification->getByCode($notification->code)) && isset($settings['recipients']) ) {
            $dynamicRecipients = $settings['recipients']($notification) ?: [];

            foreach ($dynamicRecipients as $group) {
                $table = $group[0];
                $ids = collect($group[1] ?? [])->unique()->toArray();

                foreach ($ids as $id) {
                    $this->attachTreeNotification($tree, $app, $table, $id, $notification);
                }
            }
        }
    }

    private function attachTreeNotification(&$tree, $app, $table, $id, $notification)
    {
        $key = $app.':'.$table.':'.$id;

        if ( !array_key_exists($key, $tree) ) {
            $tree[$key] = array_merge(
                hasAppsSupport() ? ['app' => $app] : [],
                [
                    'table' => $table,
                    'row_id' => $id,
                ]
            );
        }

        $notification->recipientsKeys[] = $key;
    }

    protected function getNotificationRecipients($notification)
    {
        // Mark those notifications as delivered.
        return collect($notification->recipientsPivot)->map(function($token) use ($notification) {
            $whitelistedTokens = array_filter(array_map(function($token){
                return $this->isTokenWhitelisted($token) ? $token : null;
            }, $token['tokens']));

            return [
                'pivot' => [
                    'table' => $token['table'],
                    'row_id' => $token['row_id'],
                    'notification_id' => $notification->getKey(),
                ],
                'tokens' => $whitelistedTokens,
                'tokens_count' => count($token['tokens']),
            ];
        });
    }

    private function isTokenWhitelisted($token)
    {
        if ( !$token ){
            return false;
        }

        // Enable send only to whitelisted tokens
        if ( isTestEnvironment() ) {
            $whitelistedTokens = array_merge(
                config('notifications.whitelisted_tokens', []),
                NotificationsToken::where('debug', true)->pluck('token')->toArray()
            );

            return in_array($token, $whitelistedTokens);
        }

        return true;
    }

    protected function getRecipientSendingState($row, $validDeviceTokens)
    {
        // User has no tokens
        if ( $row['tokens_count'] === 0 ) {
            return 'unsent';
        }

        // No tokens has been whitelisted
        if ( count($row['tokens']) == 0 ) {
            return 'blocked';
        }

        foreach ($row['tokens'] as $token) {
            //If at least of one tokens has been sent, then mark as sent successfully.
            if ( in_array($token, $validDeviceTokens) ) {
                return 'sent';
            } else if ( in_array($token, $this->invalidTokens) ){
                $state = 'invalid';
            } else if ( in_array($token, $this->unknownTokens) ){
                $state = 'unknown';
            }
        }

        return ($state ?? null) ?: 'error';
    }
}
