<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Support\Facades\Route;
use AdminHelpers\Auth\Controllers\OTPController;

class AdminAuth
{
    public function otp()
    {
        Route::post('auth/otp/resend', [OTPController::class, 'resend']);
        Route::post('auth/otp/verify', [OTPController::class, 'verify']);
    }
}