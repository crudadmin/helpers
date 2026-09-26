<?php

namespace AdminHelpers\Notifications\Providers;

use Admin\Providers\AdminPackageServiceProvider;
use AdminHelpers\Notifications\Commands\CleanupNotificationTokensCommand;
use AdminHelpers\Notifications\Commands\DeleteOldNotificationsCommand;
use AdminHelpers\Notifications\Commands\SendNotificationsCommand;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Notifications module, enabled by admin_helpers.notifications.enabled.
 */
class NotificationsServiceProvider extends AdminPackageServiceProvider
{
    protected $models = [
        __DIR__ . '/../Models/**' => 'AdminHelpers\Notifications\Models',
    ];

    protected $commands = [
        SendNotificationsCommand::class,
        DeleteOldNotificationsCommand::class,
        CleanupNotificationTokensCommand::class,
    ];

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

        parent::register();

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

        parent::boot();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $this->registerSchedule($schedule);
        });
    }

    /**
     * Add the notification channel of the log, unless the project defined its own.
     *
     * @return void
     */
    protected function configure()
    {
        if ( ! config()->has('logging.channels.notification') ) {
            config()->set('logging.channels.notification', [
                'driver' => 'single',
                'path' => storage_path('logs/notification.log'),
                'level' => config('logging.channels.single.level', 'debug'),
                'replace_placeholders' => true,
            ]);
        }
    }

    /**
     * Register the cleanup commands in the scheduler.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    private function registerSchedule(Schedule $schedule)
    {
        //Regularly delete old notifications, by default every night at 2:00.
        $cleanupAt = config('admin_helpers.notifications.cleanup.schedule_at', '02:00');

        $schedule->command('app:notifications:cleanup')
            ->dailyAt($cleanupAt)
            ->onOneServer();

        //Regularly delete dead device tokens, right after the notifications cleanup.
        $offset = (int) config('admin_helpers.notifications.cleanup.tokens.schedule_offset_minutes', 15);

        $schedule->command('app:notifications:cleanup-tokens')
            ->dailyAt(Carbon::createFromFormat('H:i', $cleanupAt)->addMinutes($offset)->format('H:i'))
            ->onOneServer();
    }
}
