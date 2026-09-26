<?php

namespace AdminHelpers\Providers;

use Admin\Providers\AdminPackageServiceProvider;
use AdminHelpers\Commands\CleanSessionsCommand;
use Illuminate\Console\Scheduling\Schedule;

class SessionServiceProvider extends AdminPackageServiceProvider
{
    protected $commands = [
        CleanSessionsCommand::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Expired sessions are pruned by the scheduler instead of the session lottery
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('session:prune')->dailyAt('01:00')->onOneServer();
        });
    }

    /**
     * Disable the session lottery, unless the project set its own.
     *
     * @return void
     */
    protected function configure()
    {
        $lottery = config('session.lottery');

        // [2, 100] is the default of Laravel, a project which did not change it gets [0, 100]
        if ( $lottery === null || $lottery === [2, 100] ) {
            config()->set('session.lottery', [0, 100]);
        }
    }
}
