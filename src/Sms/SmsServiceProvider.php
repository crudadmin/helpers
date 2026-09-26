<?php

namespace AdminHelpers\Sms;

use Admin\Providers\AdminPackageServiceProvider;
use Illuminate\Notifications\ChannelManager;

class SmsServiceProvider extends AdminPackageServiceProvider
{
    /**
     * Register the "sms" notification channel, used by the CrudAdmin login verification.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->resolving(ChannelManager::class, function (ChannelManager $channels) {
            $channels->extend('sms', function () {
                return new SmartSmsChannel;
            });
        });
    }
}
