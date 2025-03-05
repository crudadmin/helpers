<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Support\Facades\Route;
use AdminHelpers\Auth\Controllers\OTPController;
use AdminHelpers\Auth\Controllers\LoginController;
use AdminHelpers\Auth\Controllers\RegisterController;

class AdminAuth
{
    public function login($controller = LoginController::class)
    {
        Route::middleware(['throttle:auth'])->group(function () use ($controller) {
            Route::post('auth/login', [$controller, 'login']);
            Route::post('auth/login/otp-verify', [$controller, 'loginOTPVerify']);
            Route::post('auth/login/socialite/{driver}', [$controller, 'loginBySocialiteToken']);
        });

        // OTP throttle on sending code
        Route::middleware(['throttle:otp'])->group(function () use ($controller) {
            Route::post('auth/login/otp', [$controller, 'loginOTP']);
        });
    }

    public function logout($controller = LoginController::class)
    {
        Route::any('auth/logout', [$controller, 'logout']);
    }

    public function otp($controller = OTPController::class)
    {
        Route::middleware(['throttle:auth'])->group(function () use ($controller) {
            Route::post('auth/otp/verify', [$controller, 'verify']);
        });

        // OTP throttle on sending code
        Route::middleware(['throttle:otp'])->group(function () use ($controller) {
            Route::post('auth/otp/resend', [$controller, 'resend']);
        });
    }

    public function register($controller = RegisterController::class)
    {
        Route::middleware(['throttle:auth'])->group(function () use ($controller) {
            Route::post('auth/register/otp-verify', [$controller, 'registerOTPVerify']);
        });

        // OTP throttle on sending code
        Route::middleware(['throttle:otp'])->group(function () use ($controller) {
            Route::post('auth/register/otp', [$controller, 'registerOTP']);
        });
    }
}