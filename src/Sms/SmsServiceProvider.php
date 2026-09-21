<?php

namespace AdminHelpers\Sms;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register the "sms" notification channel, used by the CrudAdmin login verification.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving(ChannelManager::class, function (ChannelManager $channels) {
            $channels->extend('sms', function () {
                return new SmartSmsChannel;
            });
        });
    }
}
