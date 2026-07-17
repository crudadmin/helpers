<?php

namespace AdminHelpers\Notifications\Commands;

use AdminHelpers\Notifications\Models\NotificationsRecipient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteOldNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notifications:cleanup {months?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all notifications older than given period of months';

    /**
     * How many notifications delete per iteration.
     *
     * @var int
     */
    protected $chunkSize = 500;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $months = (int) ($this->argument('months') ?: config('admin_helpers.notifications.cleanup.older_than_months', 3));

        $cutoff = now()->subMonths($months);

        $this->info('Deleting notifications older than '.$months.' month(s) (before '.$cutoff->toDateTimeString().')...');

        $model = notificationModel();

        $total = $model->newQuery()->where('created_at', '<', $cutoff)->count();

        if ( $total === 0 ) {
            $this->info('There are no notifications to delete.');

            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $deleted = 0;

        do {
            $ids = $model->newQuery()
                ->where('created_at', '<', $cutoff)
                ->limit($this->chunkSize)
                ->pluck('id');

            if ( $ids->isEmpty() ) {
                break;
            }

            //Remove recipients first, because of the "no action" foreign key constraint.
            NotificationsRecipient::whereIn('notification_id', $ids)->delete();

            $deleted += $model->newQuery()->whereIn('id', $ids)->delete();

            $bar->advance($ids->count());
        } while ( $ids->count() === $this->chunkSize );

        $bar->finish();
        $this->newLine(2);

        $this->info($message = 'Deleted '.$deleted.' notifications older than '.$months.' month(s).');

        Log::channel('notification')->info($message);
    }
}
