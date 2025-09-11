<?php

namespace AdminHelpers\Importer\Providers;

use Admin\Providers\AdminHelperServiceProvider;
use Admin;

class ImporterServiceProvider extends AdminHelperServiceProvider
{
    private function isEnabled()
    {
        return config('admin_helpers.importer.enabled') === true;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // if ( $this->isEnabled() === false ) {
        //     return;
        // }

        // require __DIR__.'/../helpers.php';
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

        Admin::registerAdminModels(__dir__ . '/../Models/**', 'AdminHelpers\Importer\Models');

        // $this->commands([
        //     \AdminHelpers\Notifications\Commands\SendNotificationsCommand::class,
        // ]);

        // $this->app['config']->set('logging.channels.notification', [
        //     'driver' => 'single',
        //     'path' => storage_path('logs/notification.log'),
        //     'level' => env('LOG_LEVEL', 'debug'),
        //     'replace_placeholders' => true,
        // ]);
    }
}