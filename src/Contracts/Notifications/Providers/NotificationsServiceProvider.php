<?php

namespace AdminHelpers\Contracts\Notifications\Providers;

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

        Admin::registerAdminModels(__dir__ . '/../Models/**', 'AdminHelpers\Contracts\Notifications\Models');

        $this->commands([
            \AdminHelpers\Contracts\Notifications\Commands\SendNotificationsCommand::class,
        ]);
    }
}