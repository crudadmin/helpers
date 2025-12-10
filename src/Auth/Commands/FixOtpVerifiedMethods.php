<?php

namespace AdminHelpers\Auth\Commands;

use Admin;
use Illuminate\Console\Command;
use AdminHelpers\Auth\Concerns\HasUserAuth;

class FixOtpVerifiedMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:fix-verified-methods';

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
        if ( !$this->confirm('Are you sure you want to fix verified methods?')) {
            return;
        }

        $this->line('Fixing verified methods...');

        $models = Admin::getAdminModels();

        foreach ($models as $model) {
            if (in_array(HasUserAuth::class, class_uses($model)) == false) {
                continue;
            }

            $users = $model->get();

            $count = 0;
            foreach ($users as $user) {
                if ( $user->fixOldVerifiedFormat() ) {
                    $count++;
                    $user->save();
                }
            }

            $this->line('Verified methods fixed for ' . $model->getTable() . ' - ' . $count . ' users.');
        }

    }
}
