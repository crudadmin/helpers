<?php

namespace AdminHelpers\Auth\Commands;

use AdminHelpers\Auth\Models\OtpToken;
use Illuminate\Console\Command;

class CleanOtpTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:clean';

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
        $this->line('Cleaning OTP tokens...');

        otpModel()->where('valid_to', '<', now())->delete();

        $this->line('OTP tokens cleaned.');
    }
}
