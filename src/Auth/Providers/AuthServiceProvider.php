<?php

namespace AdminHelpers\Auth\Providers;

use Admin\Providers\AdminHelperServiceProvider;
use Admin;

class AuthServiceProvider extends AdminHelperServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require __DIR__.'/../auth.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ( hasOtpEnabled() ) {
            Admin::registerAdminModels(__dir__ . '/../Models/Otp/**', 'AdminHelpers\Auth\Models\Otp');
        }

        $this->commands([
            // \AdminHelpers\Notifications\Commands\SendNotificationsCommand::class,
        ]);
    }
}