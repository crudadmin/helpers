<?php

namespace AdminHelpers\Notifications\Providers;

use Admin\Providers\AdminHelperServiceProvider;
use Admin;

class NotificationsServiceProvider extends AdminHelperServiceProvider
{
    private function isEnabled()
    {
        return config('admin_helpers.notifications.enabled') === true;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ( $this->isEnabled() === false ) {
            return;
        }

        require __DIR__.'/../notifications.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ( $this->isEnabled() === false ) {
            return;
        }

        Admin::registerAdminModels(__dir__ . '/../Models/**', 'AdminHelpers\Notifications\Models');

        $this->commands([
            \AdminHelpers\Notifications\Commands\SendNotificationsCommand::class,
        ]);
    }
}