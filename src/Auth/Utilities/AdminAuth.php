<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Support\Facades\Route;
use Admin\Controllers\Auth\LoginController;
use AdminHelpers\Auth\Controllers\OTPController;
use AdminHelpers\Auth\Controllers\RegisterController;

class AdminAuth
{
    public function login($controller = LoginController::class)
    {
        Route::middleware(['throttle:otp'])->group(function () use ($controller) {
            Route::post('auth/login', [$controller, 'login']);
            Route::post('auth/login/otp', [$controller, 'loginOTP']);
            Route::post('auth/login/otp-verify', [$controller, 'loginOTPVerify']);
            Route::post('auth/login/socialite/{driver}', [$controller, 'loginBySocialiteToken']);
        });
    }

    public function otp($controller = OTPController::class)
    {
        Route::middleware(['throttle:otp'])->group(function () use ($controller) {
            Route::post('auth/otp/resend', [$controller, 'resend']);
            Route::post('auth/otp/verify', [$controller, 'verify']);
        });
    }

    public function register($controller = RegisterController::class)
    {
        Route::middleware(['throttle:otp'])->group(function () use ($controller) {
            Route::post('auth/register/otp', [$controller, 'registerOTP']);
            Route::post('auth/register/otp-verify', [$controller, 'registerOTPVerify']);
        });
    }
}