<?php

namespace AdminHelpers\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:prune {minutes?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired old sessions after given period of time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $session = app('session');

        $minutes = (int) $this->argument('minutes') ?: $session->getSessionConfig()['lifetime'];

        $this->info('Pruning expired sessions older than ' . $minutes . ' minutes...');

        $this->info($message = 'Pruned ' . $session->driver()->getHandler()->gc($minutes) . ' sessions older than ' . $minutes . ' minutes.');

        Log::info($message);
    }
}