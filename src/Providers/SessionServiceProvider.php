<?php

namespace AdminHelpers\Providers;

use Illuminate\Support\Facades\Schedule;
use Admin\Providers\AdminHelperServiceProvider;
use AdminHelpers\Commands\CleanSessionsCommand;

class SessionServiceProvider extends AdminHelperServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Disable session lottery,
        config()->set('session.lottery', [0, 100]);
    }

    public function boot()
    {
        $this->commands([
            CleanSessionsCommand::class,
        ]);

        Schedule::command('session:prune')->dailyAt('01:00')->onOneServer();
    }
}
