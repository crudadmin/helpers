<?php

namespace AdminHelpers\Notifications\Commands;

use AdminHelpers\Notifications\Models\NotificationsToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupNotificationTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notifications:cleanup-tokens {--dry-run : Only print what would be deleted, without touching the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete dead, duplicite and over-limit notification device tokens';

    /**
     * How many tokens delete per iteration.
     *
     * @var int
     */
    protected $chunkSize = 500;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ( $dryRun ) {
            $this->warn('Dry run mode, no tokens will be deleted.');
        }

        $deleted = 0;

        $deleted += $this->deleteDeadTokens($dryRun);
        $deleted += $this->deleteDuplicateTokens($dryRun);
        $deleted += $this->deleteOverLimitTokens($dryRun);

        $message = ($dryRun ? 'Would delete ' : 'Deleted ').$deleted.' notification tokens.';

        $this->info($message);

        if ( $dryRun === false ) {
            Log::channel('notification')->info($message);
        }
    }

    /**
     * Tokens which are not in the "ok" state are never used for sending,
     * because the device is not reachable anymore.
     */
    private function deleteDeadTokens($dryRun)
    {
        $months = (int) config('admin_helpers.notifications.cleanup.tokens.dead_older_than_months', 1);

        $cutoff = now()->subMonths($months);

        $this->line('Deleting unreachable tokens older than '.$months.' month(s) (before '.$cutoff->toDateTimeString().')...');

        $query = fn() => NotificationsToken::whereIn('state', ['invalid', 'unknown'])
                            ->where('created_at', '<', $cutoff);

        return $this->deleteByQuery($query, $dryRun);
    }

    /**
     * The same device token may be registered under multiple recipients,
     * when more users has been logged in on the same device. Only the
     * newest owner should receive notifications for such device.
     */
    private function deleteDuplicateTokens($dryRun)
    {
        $this->line('Deleting device tokens assigned to multiple recipients...');

        $duplicates = NotificationsToken::select('token')
                        ->groupBy('token')
                        ->havingRaw('count(*) > 1')
                        ->pluck('token');

        if ( $duplicates->isEmpty() ) {
            $this->line('There are no duplicite tokens.');

            return 0;
        }

        $ids = [];

        $bar = $this->output->createProgressBar($duplicates->count());
        $bar->start();

        foreach ( $duplicates->chunk($this->chunkSize) as $tokens ) {
            $rows = NotificationsToken::select('id', 'token')
                        ->whereIn('token', $tokens)
                        ->orderBy('created_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->get()
                        ->groupBy('token');

            foreach ( $rows as $group ) {
                $ids = array_merge($ids, $group->skip(1)->pluck('id')->toArray());
            }

            $bar->advance($tokens->count());
        }

        $bar->finish();
        $this->newLine();

        return $this->deleteByIds($ids, $dryRun);
    }

    /**
     * Devices rotate their push token regularly, so an old account may
     * collect hundreds of valid looking tokens. Keep only the newest ones.
     */
    private function deleteOverLimitTokens($dryRun)
    {
        $limit = (int) config('admin_helpers.notifications.cleanup.tokens.max_per_recipient', 10);

        if ( $limit < 1 ) {
            return 0;
        }

        $this->line('Deleting tokens above the limit of '.$limit.' token(s) per recipient...');

        $recipients = NotificationsToken::select('table', 'row_id')
                        ->where('state', 'ok')
                        ->groupBy('table', 'row_id')
                        ->havingRaw('count(*) > ?', [$limit])
                        ->get();

        if ( $recipients->isEmpty() ) {
            $this->line('There are no recipients above the limit.');

            return 0;
        }

        $ids = [];

        $bar = $this->output->createProgressBar($recipients->count());
        $bar->start();

        foreach ( $recipients as $recipient ) {
            $recipientIds = NotificationsToken::where('state', 'ok')
                        ->where('table', $recipient->table)
                        ->where('row_id', $recipient->row_id)
                        ->orderBy('created_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->pluck('id')
                        ->toArray();

            $ids = array_merge($ids, array_slice($recipientIds, $limit));

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $this->deleteByIds($ids, $dryRun);
    }

    /**
     * Delete all tokens matching given query in chunks.
     */
    private function deleteByQuery($query, $dryRun)
    {
        $total = $query()->count();

        if ( $total === 0 ) {
            $this->line('There are no tokens to delete.');

            return 0;
        }

        if ( $dryRun ) {
            $this->line('Would delete '.$total.' token(s).');

            return $total;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $deleted = 0;

        do {
            $ids = $query()->limit($this->chunkSize)->pluck('id');

            if ( $ids->isEmpty() ) {
                break;
            }

            $deleted += NotificationsToken::whereIn('id', $ids)->delete();

            $bar->advance($ids->count());
        } while ( $ids->count() === $this->chunkSize );

        $bar->finish();
        $this->newLine();

        return $deleted;
    }

    /**
     * Delete given token ids in chunks.
     */
    private function deleteByIds($ids, $dryRun)
    {
        if ( count($ids) === 0 ) {
            $this->line('There are no tokens to delete.');

            return 0;
        }

        if ( $dryRun ) {
            $this->line('Would delete '.count($ids).' token(s).');

            return count($ids);
        }

        $bar = $this->output->createProgressBar(count($ids));
        $bar->start();

        $deleted = 0;

        foreach ( array_chunk($ids, $this->chunkSize) as $chunk ) {
            $deleted += NotificationsToken::whereIn('id', $chunk)->delete();

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();

        return $deleted;
    }
}
