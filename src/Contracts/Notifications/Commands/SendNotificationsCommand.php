<?php

namespace AdminHelpers\Contracts\Notifications\Commands;

use AdminHelpers\Contracts\Notifications\Utilities\NotificationManager;
use Illuminate\Console\Command;

class SendNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        (new NotificationManager($this))->process();
    }
}