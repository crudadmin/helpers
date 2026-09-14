<?php

namespace AdminHelpers\Notifications\Providers;

use Admin\Providers\AdminHelperServiceProvider;
use Illuminate\Support\Facades\Schedule;
use Admin;
use Carbon\Carbon;

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

        require_once __DIR__.'/../notifications.php';
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
            \AdminHelpers\Notifications\Commands\DeleteOldNotificationsCommand::class,
            \AdminHelpers\Notifications\Commands\CleanupNotificationTokensCommand::class,
        ]);

        //Regularly delete old notifications, by default every night at 2:00.
        $cleanupAt = config('admin_helpers.notifications.cleanup.schedule_at', '02:00');

        Schedule::command('app:notifications:cleanup')
            ->dailyAt($cleanupAt)
            ->onOneServer();

        //Regularly delete dead device tokens, right after the notifications cleanup.
        $offset = (int) config('admin_helpers.notifications.cleanup.tokens.schedule_offset_minutes', 15);

        Schedule::command('app:notifications:cleanup-tokens')
            ->dailyAt(Carbon::createFromFormat('H:i', $cleanupAt)->addMinutes($offset)->format('H:i'))
            ->onOneServer();

        $this->app['config']->set('logging.channels.notification', [
            'driver' => 'single',
            'path' => storage_path('logs/notification.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ]);
    }
}